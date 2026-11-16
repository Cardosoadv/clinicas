<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Autenticação do endpoint MCP por token estático (Bearer).
 *
 * O servidor MCP é consumido por clientes externos (Claude, Gemini) que não
 * possuem sessão do Shield. O token é definido em `mcp.apiToken` no .env; sem
 * ele o endpoint fica desabilitado (503), nunca aberto.
 */
class McpAuth implements FilterInterface
{
    /** Tamanho mínimo aceito para o token configurado. */
    public const MIN_TOKEN_LENGTH = 32;

    public function before(RequestInterface $request, $arguments = null)
    {
        $configured = (string) (getenv('mcp.apiToken') ?: '');

        if (strlen($configured) < self::MIN_TOKEN_LENGTH) {
            return service('response')
                ->setStatusCode(503)
                ->setJSON(['status' => 'error', 'message' => 'Servidor MCP desabilitado: defina mcp.apiToken (mín. 32 caracteres) no .env.']);
        }

        $header = $request->getHeaderLine('Authorization');
        $token  = preg_match('/^Bearer\s+(.+)$/i', $header, $m) === 1 ? trim($m[1]) : '';

        if ($token === '' || !hash_equals($configured, $token)) {
            return service('response')
                ->setStatusCode(401)
                ->setHeader('WWW-Authenticate', 'Bearer')
                ->setJSON(['status' => 'error', 'message' => 'Token MCP inválido.']);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
