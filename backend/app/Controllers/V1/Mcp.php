<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Libraries\Mcp\ClinicaTools;
use App\Libraries\Mcp\McpServer;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Endpoint MCP (Model Context Protocol) para assistentes externos como
 * Claude Chat e Gemini Chat. Transporte Streamable HTTP: POST com JSON-RPC.
 * Autenticado pelo filtro `mcpauth` (ver App\Filters\McpAuth).
 */
class Mcp extends BaseController
{
    protected McpServer $server;

    public function __construct(?McpServer $server = null)
    {
        if ($server === null) {
            $server = new McpServer('clinicas', '1.0.0', ClinicaTools::instrucoes());
            (new ClinicaTools())->register($server);
        }
        $this->server = $server;
    }

    public function handle(): ResponseInterface
    {
        $response = $this->server->handleRaw((string) $this->request->getBody());

        // Somente notificações/respostas: 202 sem corpo, conforme a especificação.
        if ($response === null) {
            return $this->response->setStatusCode(202)->setBody('');
        }

        return $this->response->setStatusCode(200)->setJSON($response);
    }

    /**
     * GET não é usado (não há stream SSE servidor→cliente): 405 permite ao cliente seguir só com POST.
     */
    public function notAllowed(): ResponseInterface
    {
        return $this->response->setStatusCode(405)->setHeader('Allow', 'POST')
            ->setJSON(['status' => 'error', 'message' => 'Use POST com JSON-RPC.']);
    }
}
