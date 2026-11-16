<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\Mcp\OAuthSupport;
use App\Models\McpOAuthClientModel;
use App\Models\McpOAuthCodeModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Authorization Server mínimo (OAuth 2.1 + PKCE + Dynamic Client Registration)
 * que permite ao endpoint /mcp ser usado como conector personalizado no
 * Claude e no Gemini. Mesmo desenho do OrdoJuris:
 *
 *  - /.well-known/oauth-protected-resource e /oauth-authorization-server: descoberta (RFC 9728 / 8414)
 *  - POST /oauth/register: registro dinâmico do cliente (RFC 7591)
 *  - GET|POST /oauth/authorize: login do usuário do sistema + consentimento
 *  - POST /oauth/token: troca o código (PKCE) por um Personal Access Token do
 *    Shield com escopo `mcp`, que o filtro McpAuth já aceita.
 *
 * A tela de login/consentimento é própria do backend (não depende do SPA nem
 * de cookie entre origens): as credenciais são conferidas com o Shield sem
 * abrir sessão.
 */
class OAuth extends BaseController
{
    private const AUTH_METHODS = ['client_secret_basic', 'client_secret_post', 'none'];

    public function protectedResourceMetadata(): ResponseInterface
    {
        $base = rtrim(site_url(), '/');

        return $this->json(200, [
            'resource'                 => $base . '/mcp',
            'resource_name'            => 'Clínicas',
            'authorization_servers'    => [$base],
            'scopes_supported'         => [OAuthSupport::SCOPE],
            'bearer_methods_supported' => ['header'],
        ], ['Cache-Control' => 'max-age=3600']);
    }

    public function authorizationServerMetadata(): ResponseInterface
    {
        $base = rtrim(site_url(), '/');

        return $this->json(200, [
            'issuer'                                => $base,
            'authorization_endpoint'                => $base . '/oauth/authorize',
            'token_endpoint'                        => $base . '/oauth/token',
            'registration_endpoint'                 => $base . '/oauth/register',
            'scopes_supported'                      => [OAuthSupport::SCOPE],
            'response_types_supported'              => ['code'],
            'grant_types_supported'                 => ['authorization_code'],
            'code_challenge_methods_supported'      => ['S256'],
            'token_endpoint_auth_methods_supported' => self::AUTH_METHODS,
        ], ['Cache-Control' => 'max-age=3600']);
    }

    /** RFC 7591 — o Claude chama sozinho na primeira conexão. */
    public function register(): ResponseInterface
    {
        if (stripos($this->request->getHeaderLine('Content-Type'), 'application/json') !== 0) {
            return $this->erro(400, 'invalid_client_metadata', 'Content-Type deve ser application/json.');
        }

        $dados = json_decode((string) $this->request->getBody(), true);
        if (!is_array($dados)) {
            return $this->erro(400, 'invalid_client_metadata', 'Corpo da requisição deve ser um JSON válido.');
        }

        $redirectUris = $dados['redirect_uris'] ?? null;
        if (!is_array($redirectUris) || $redirectUris === []) {
            return $this->erro(400, 'invalid_redirect_uri', 'É necessário informar ao menos um redirect_uri.');
        }
        foreach ($redirectUris as $uri) {
            if (!is_string($uri) || !OAuthSupport::redirectUriPermitida($uri)) {
                return $this->erro(400, 'invalid_redirect_uri', 'redirect_uri inválido (exige https, exceto em localhost).');
            }
        }
        $redirectUris = array_values($redirectUris);

        $method = $dados['token_endpoint_auth_method'] ?? 'client_secret_post';
        if (!in_array($method, self::AUTH_METHODS, true)) {
            $method = 'client_secret_post';
        }

        $name = isset($dados['client_name']) && is_string($dados['client_name']) ? substr(trim($dados['client_name']), 0, 255) : null;

        [$clientId, $secret] = $this->criarCliente($name, $redirectUris, $method);

        $resposta = [
            'client_id'                  => $clientId,
            'client_id_issued_at'        => time(),
            'client_name'                => $name,
            'redirect_uris'              => $redirectUris,
            'token_endpoint_auth_method' => $method,
            'grant_types'                => ['authorization_code'],
            'response_types'             => ['code'],
        ];
        if ($secret !== null) {
            $resposta['client_secret'] = $secret;
        }

        return $this->json(201, $resposta, ['Cache-Control' => 'no-store']);
    }

    /**
     * Cria um cliente OAuth. Retorna [client_id, client_secret|null].
     *
     * @return array{0: string, 1: ?string}
     */
    public static function criarCliente(?string $nome, array $redirectUris, string $method): array
    {
        $clientId = bin2hex(random_bytes(16));
        $secret   = $method !== 'none' ? bin2hex(random_bytes(32)) : null;

        model(McpOAuthClientModel::class)->insert([
            'client_id'                  => $clientId,
            'client_secret_hash'         => $secret !== null ? hash('sha256', $secret) : null,
            'client_name'                => $nome,
            'redirect_uris'              => json_encode($redirectUris, JSON_UNESCAPED_SLASHES),
            'token_endpoint_auth_method' => $method,
            'created_at'                 => date('Y-m-d H:i:s'),
        ]);

        return [$clientId, $secret];
    }

    /** GET /oauth/authorize — valida o pedido e mostra login + consentimento. */
    public function authorize(): ResponseInterface
    {
        $p = [
            'client_id'             => (string) $this->request->getGet('client_id'),
            'redirect_uri'          => (string) $this->request->getGet('redirect_uri'),
            'state'                 => (string) ($this->request->getGet('state') ?? ''),
            'code_challenge'        => (string) $this->request->getGet('code_challenge'),
            'code_challenge_method' => (string) ($this->request->getGet('code_challenge_method') ?? 'S256'),
        ];

        $client = $this->buscarCliente($p['client_id']);
        if ($client === null) {
            return $this->paginaErro('Cliente OAuth desconhecido. Reconecte o conector a partir do Claude/Gemini.');
        }
        if (!in_array($p['redirect_uri'], $this->redirectUris($client), true)) {
            return $this->paginaErro('O redirect_uri informado não corresponde ao cadastrado para este cliente.');
        }
        if ((string) $this->request->getGet('response_type') !== 'code') {
            return $this->redirecionarComErro($p['redirect_uri'], $p['state'], 'unsupported_response_type', 'Apenas response_type=code é suportado.');
        }
        $erroPkce = OAuthSupport::validarPkce($client['client_secret_hash'] !== null, $p['code_challenge'], $p['code_challenge_method']);
        if ($erroPkce !== null) {
            return $this->redirecionarComErro($p['redirect_uri'], $p['state'], 'invalid_request', $erroPkce);
        }

        return $this->pagina($client, $p, null);
    }

    /** POST /oauth/authorize — confere e-mail/senha, registra a decisão e emite o código. */
    public function consent(): ResponseInterface
    {
        $p = [
            'client_id'             => (string) $this->request->getPost('client_id'),
            'redirect_uri'          => (string) $this->request->getPost('redirect_uri'),
            'state'                 => (string) ($this->request->getPost('state') ?? ''),
            'code_challenge'        => (string) $this->request->getPost('code_challenge'),
            'code_challenge_method' => (string) ($this->request->getPost('code_challenge_method') ?: 'S256'),
        ];

        $client = $this->buscarCliente($p['client_id']);
        if ($client === null) {
            return $this->paginaErro('Cliente OAuth desconhecido.');
        }
        if (!in_array($p['redirect_uri'], $this->redirectUris($client), true)) {
            return $this->paginaErro('O redirect_uri informado não corresponde ao cadastrado para este cliente.');
        }

        if ((string) $this->request->getPost('decisao') !== 'permitir') {
            return $this->redirecionarComErro($p['redirect_uri'], $p['state'], 'access_denied', 'O usuário negou a autorização.');
        }

        $erroPkce = OAuthSupport::validarPkce($client['client_secret_hash'] !== null, $p['code_challenge'], $p['code_challenge_method']);
        if ($erroPkce !== null) {
            return $this->redirecionarComErro($p['redirect_uri'], $p['state'], 'invalid_request', $erroPkce);
        }

        // Freio a tentativas de senha (10 por minuto por IP).
        if (!service('throttler')->check('mcp-oauth-' . md5($this->request->getIPAddress()), 10, MINUTE)) {
            return $this->pagina($client, $p, 'Muitas tentativas. Aguarde um minuto e tente novamente.', 429);
        }

        $usuario = $this->autenticar((string) $this->request->getPost('email'), (string) $this->request->getPost('password'));
        if ($usuario === null) {
            return $this->pagina($client, $p, 'E-mail ou senha inválidos.', 401);
        }

        $codigo = bin2hex(random_bytes(32));
        model(McpOAuthCodeModel::class)->insert([
            'code_hash'             => hash('sha256', $codigo),
            'client_id'             => $p['client_id'],
            'usuario_id'            => (int) $usuario->id,
            'redirect_uri'          => $p['redirect_uri'],
            'code_challenge'        => $p['code_challenge'] !== '' ? $p['code_challenge'] : null,
            'code_challenge_method' => 'S256',
            'scope'                 => OAuthSupport::SCOPE,
            'expires_at'            => date('Y-m-d H:i:s', time() + OAuthSupport::CODE_TTL),
            'created_at'            => date('Y-m-d H:i:s'),
        ]);

        $query = ['code' => $codigo];
        if ($p['state'] !== '') {
            $query['state'] = $p['state'];
        }

        return redirect()->to($this->comQuery($p['redirect_uri'], $query));
    }

    /** POST /oauth/token — troca o código (validando PKCE) por um token de acesso. */
    public function token(): ResponseInterface
    {
        $params = $this->request->getPost();
        if ($params === []) {
            $json   = json_decode((string) $this->request->getBody(), true);
            $params = is_array($json) ? $json : [];
        }

        if (($params['grant_type'] ?? null) !== 'authorization_code') {
            return $this->erro(400, 'unsupported_grant_type', 'Apenas grant_type=authorization_code é suportado.');
        }

        $code         = (string) ($params['code'] ?? '');
        $redirectUri  = (string) ($params['redirect_uri'] ?? '');
        $verifier     = (string) ($params['code_verifier'] ?? '');
        $clientId     = (string) ($params['client_id'] ?? '');
        $clientSecret = $params['client_secret'] ?? null;

        // client_secret_basic: credenciais no header Authorization.
        $basic = $this->extrairBasicAuth();
        if ($basic !== null && ($clientId === '' || $clientId === $basic[0])) {
            [$clientId, $clientSecret] = $basic;
        }

        if ($code === '' || $redirectUri === '' || $clientId === '') {
            return $this->erro(400, 'invalid_request', 'Parâmetros obrigatórios ausentes (code, redirect_uri, client_id).');
        }

        $client = $this->buscarCliente($clientId);
        if ($client === null) {
            return $this->erro(401, 'invalid_client', 'Cliente OAuth desconhecido.');
        }
        if ($client['client_secret_hash'] !== null
            && (!is_string($clientSecret) || !hash_equals($client['client_secret_hash'], hash('sha256', $clientSecret)))) {
            return $this->erro(401, 'invalid_client', 'client_secret inválido.');
        }

        $codes    = model(McpOAuthCodeModel::class);
        $registro = $codes->where('code_hash', hash('sha256', $code))->first();

        if ($registro === null || $registro['used_at'] !== null || $registro['client_id'] !== $clientId) {
            return $this->erro(400, 'invalid_grant', 'Código de autorização inválido, expirado ou já utilizado.');
        }
        if (strtotime($registro['expires_at']) < time()) {
            return $this->erro(400, 'invalid_grant', 'Código de autorização expirado.');
        }
        if (!hash_equals($registro['redirect_uri'], $redirectUri)) {
            return $this->erro(400, 'invalid_grant', 'redirect_uri não corresponde ao usado em /oauth/authorize.');
        }
        if ($registro['code_challenge'] !== null
            && ($verifier === '' || !hash_equals($registro['code_challenge'], OAuthSupport::pkceChallenge($verifier)))) {
            return $this->erro(400, 'invalid_grant', 'code_verifier não corresponde ao code_challenge original (PKCE).');
        }

        // Uso único: marca antes de emitir o token (UPDATE condicional evita corrida entre duas trocas).
        $codes->builder()->where('id', $registro['id'])->where('used_at', null)->update(['used_at' => date('Y-m-d H:i:s')]);
        if (db_connect()->affectedRows() !== 1) {
            return $this->erro(400, 'invalid_grant', 'Código de autorização inválido, expirado ou já utilizado.');
        }

        $usuario = auth()->getProvider()->findById((int) $registro['usuario_id']);
        if ($usuario === null || $usuario->isBanned()) {
            return $this->erro(400, 'invalid_grant', 'Usuário associado ao código não existe ou está bloqueado.');
        }

        $accessToken = $usuario->generateAccessToken(($client['client_name'] ?: 'Conector') . ' (OAuth)', [OAuthSupport::SCOPE]);

        return $this->json(200, [
            'access_token' => $accessToken->raw_token,
            'token_type'   => 'Bearer',
            'scope'        => OAuthSupport::SCOPE,
        ], ['Cache-Control' => 'no-store', 'Pragma' => 'no-cache']);
    }

    // ------------------------------------------------------------------

    /** Confere credenciais com o Shield sem abrir sessão. */
    private function autenticar(string $email, string $senha): ?object
    {
        if ($email === '' || $senha === '') {
            return null;
        }

        $result = auth('session')->getAuthenticator()->check(['email' => $email, 'password' => $senha]);
        if (!$result->isOK()) {
            return null;
        }

        $usuario = $result->extraInfo();
        if ($usuario->isBanned() || !$usuario->isActivated()) {
            return null;
        }

        return $usuario;
    }

    private function buscarCliente(string $clientId): ?array
    {
        return $clientId === '' ? null : model(McpOAuthClientModel::class)->where('client_id', $clientId)->first();
    }

    private function redirectUris(array $client): array
    {
        return json_decode((string) $client['redirect_uris'], true) ?: [];
    }

    private function pagina(array $client, array $p, ?string $erro, int $status = 200): ResponseInterface
    {
        return $this->response->setStatusCode($status)
            ->setHeader('Cache-Control', 'no-store')
            ->setHeader('X-Frame-Options', 'DENY')
            ->setContentType('text/html')
            ->setBody(view('oauth_authorize', ['client' => $client, 'p' => $p, 'erro' => $erro]));
    }

    private function paginaErro(string $mensagem): ResponseInterface
    {
        return $this->response->setStatusCode(400)->setContentType('text/html')
            ->setBody('<!doctype html><meta charset="utf-8"><h1>Erro de autorização</h1><p>' . esc($mensagem) . '</p>');
    }

    private function redirecionarComErro(string $redirectUri, string $state, string $erro, string $descricao): ResponseInterface
    {
        $query = ['error' => $erro, 'error_description' => $descricao];
        if ($state !== '') {
            $query['state'] = $state;
        }

        return redirect()->to($this->comQuery($redirectUri, $query));
    }

    private function comQuery(string $uri, array $query): string
    {
        return $uri . (str_contains($uri, '?') ? '&' : '?') . http_build_query($query);
    }

    private function erro(int $status, string $erro, string $descricao): ResponseInterface
    {
        return $this->json($status, ['error' => $erro, 'error_description' => $descricao], ['Cache-Control' => 'no-store']);
    }

    private function json(int $status, array $body, array $headers = []): ResponseInterface
    {
        $this->response->setStatusCode($status)->setContentType('application/json');
        foreach ($headers as $k => $v) {
            $this->response->setHeader($k, $v);
        }

        return $this->response->setBody(json_encode($body, JSON_UNESCAPED_SLASHES));
    }

    /** client_secret_basic (RFC 6749 §2.3.1): id e segredo vêm form-urlencoded antes do Base64. */
    private function extrairBasicAuth(): ?array
    {
        $header = $this->request->getHeaderLine('Authorization');
        if (stripos($header, 'Basic ') !== 0) {
            return null;
        }

        $decoded = base64_decode(trim(substr($header, 6)), true);
        if ($decoded === false || !str_contains($decoded, ':')) {
            return null;
        }

        return array_map('urldecode', explode(':', $decoded, 2));
    }
}
