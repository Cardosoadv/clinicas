<?php

declare(strict_types=1);

namespace App\Filters;

use App\Libraries\Mcp\OAuthSupport;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Autenticação do endpoint MCP por `Authorization: Bearer <token>`. Aceita:
 *
 *  1. Personal Access Token do Shield com escopo `mcp` — emitido pelo fluxo
 *     OAuth 2.1 (App\Controllers\OAuth) para Claude/Gemini; as ações valem como o usuário que autorizou.
 *  2. Token estático opcional `mcp.apiToken` do .env (mín. 32 caracteres), para clientes sem OAuth
 *     (ex.: Gemini CLI/Claude Code com --header). Sem ele, só vale o OAuth.
 *
 * Nos 401 o header WWW-Authenticate aponta para o Protected Resource Metadata (RFC 9728),
 * por onde o cliente descobre o Authorization Server.
 */
class McpAuth implements FilterInterface
{
    /** Tamanho mínimo aceito para o token estático configurado. */
    public const MIN_TOKEN_LENGTH = 32;

    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');
        $token  = preg_match('/^Bearer\s+(.+)$/i', $header, $m) === 1 ? trim($m[1]) : '';

        if ($token === '') {
            return $this->nao401('Token de acesso ao MCP ausente.');
        }

        $estatico = (string) (getenv('mcp.apiToken') ?: '');
        if (strlen($estatico) >= self::MIN_TOKEN_LENGTH && hash_equals($estatico, $token)) {
            return null;
        }

        if ($this->tokenShieldValido($token)) {
            return null;
        }

        return $this->nao401('Token de acesso ao MCP inválido, expirado ou revogado.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }

    private function tokenShieldValido(string $token): bool
    {
        try {
            $authenticator = auth('tokens')->getAuthenticator();

            if (!$authenticator->attempt(['token' => $token])->isOK()) {
                return false;
            }

            $usuario = $authenticator->getUser();

            return $usuario !== null
                && !$usuario->isBanned()
                && $usuario->tokenCan(OAuthSupport::SCOPE);
        } catch (\Throwable $e) {
            log_message('error', '[MCP] falha ao validar token: ' . $e->getMessage());

            return false;
        }
    }

    private function nao401(string $mensagem): ResponseInterface
    {
        return service('response')
            ->setStatusCode(401)
            ->setHeader('WWW-Authenticate', 'Bearer resource_metadata="' . OAuthSupport::metadataUrl() . '"')
            ->setJSON(['status' => 'error', 'message' => $mensagem]);
    }
}
