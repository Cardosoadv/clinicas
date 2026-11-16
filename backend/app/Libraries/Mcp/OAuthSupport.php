<?php

declare(strict_types=1);

namespace App\Libraries\Mcp;

/** Funções puras do fluxo OAuth 2.1 do MCP (PKCE, redirect_uri, URLs). */
final class OAuthSupport
{
    public const SCOPE = 'mcp';

    /** Tempo de vida do código de autorização, em segundos. */
    public const CODE_TTL = 300;

    /** Challenge PKCE S256 de um code_verifier (RFC 7636). */
    public static function pkceChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /** Exige https, exceto em loopback (clientes locais / testes). */
    public static function redirectUriPermitida(string $uri): bool
    {
        $partes = parse_url($uri);
        if ($partes === false || empty($partes['scheme']) || empty($partes['host']) || isset($partes['fragment'])) {
            return false;
        }

        if ($partes['scheme'] === 'https') {
            return true;
        }

        return $partes['scheme'] === 'http' && in_array($partes['host'], ['localhost', '127.0.0.1', '[::1]', '::1'], true);
    }

    /**
     * PKCE é obrigatório para clientes públicos (sem segredo); clientes confidenciais podem dispensá-lo.
     *
     * @return string|null Mensagem de erro ou null se válido.
     */
    public static function validarPkce(bool $clienteConfidencial, string $challenge, string $method): ?string
    {
        if ($challenge === '') {
            return $clienteConfidencial ? null : 'PKCE (code_challenge com method S256) é obrigatório para clientes públicos.';
        }

        return strtoupper($method) === 'S256' ? null : 'Apenas code_challenge_method=S256 é suportado.';
    }

    /** URL de metadados do recurso protegido (RFC 9728), anunciada no WWW-Authenticate. */
    public static function metadataUrl(): string
    {
        return rtrim(site_url(), '/') . '/.well-known/oauth-protected-resource';
    }
}
