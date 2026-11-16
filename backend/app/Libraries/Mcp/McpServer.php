<?php

declare(strict_types=1);

namespace App\Libraries\Mcp;

use Throwable;

/**
 * Servidor MCP (Model Context Protocol) mínimo sobre JSON-RPC 2.0.
 *
 * Implementa o transporte "Streamable HTTP" no modo mais simples (uma
 * resposta JSON por requisição, sem SSE), suficiente para clientes remotos
 * como Claude (conectores personalizados) e Gemini (CLI / SDKs com MCP).
 * Suporta: initialize, ping, tools/list e tools/call.
 *
 * Cada ferramenta é registrada com nome, descrição, JSON Schema de entrada e
 * um callable que recebe o array de argumentos e devolve um array serializável.
 */
class McpServer
{
    /** Versões do protocolo aceitas, da mais nova para a mais antiga. */
    public const PROTOCOL_VERSIONS = ['2025-06-18', '2025-03-26', '2024-11-05'];

    public const ERR_PARSE            = -32700;
    public const ERR_INVALID_REQUEST  = -32600;
    public const ERR_METHOD_NOT_FOUND = -32601;
    public const ERR_INVALID_PARAMS   = -32602;
    public const ERR_INTERNAL         = -32603;

    /** @var array<string, array{description: string, inputSchema: array, handler: callable}> */
    private array $tools = [];

    public function __construct(
        private readonly string $name = 'clinicas',
        private readonly string $version = '1.0.0',
        private readonly string $instructions = ''
    ) {
    }

    public function registerTool(string $name, string $description, array $inputSchema, callable $handler): void
    {
        $this->tools[$name] = [
            'description' => $description,
            'inputSchema' => $inputSchema,
            'handler'     => $handler,
        ];
    }

    /**
     * Processa o corpo bruto de uma requisição HTTP.
     *
     * @return array|null Resposta JSON-RPC (objeto ou batch), ou null quando
     *                    só havia notificações (HTTP 202, sem corpo).
     */
    public function handleRaw(string $body): ?array
    {
        $payload = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
            return $this->errorResponse(null, self::ERR_PARSE, 'JSON inválido.');
        }

        // Batch: lista de requisições.
        if (array_is_list($payload) && $payload !== []) {
            $responses = [];
            foreach ($payload as $item) {
                $response = $this->handleMessage($item);
                if ($response !== null) {
                    $responses[] = $response;
                }
            }

            return $responses === [] ? null : $responses;
        }

        return $this->handleMessage($payload);
    }

    public function handleMessage(mixed $message): ?array
    {
        if (!is_array($message) || ($message['jsonrpc'] ?? null) !== '2.0' || !isset($message['method']) || !is_string($message['method'])) {
            return $this->errorResponse($message['id'] ?? null, self::ERR_INVALID_REQUEST, 'Requisição JSON-RPC inválida.');
        }

        $isNotification = !array_key_exists('id', $message);
        $id             = $message['id'] ?? null;
        $params         = is_array($message['params'] ?? null) ? $message['params'] : [];

        try {
            $result = match ($message['method']) {
                'initialize'  => $this->initialize($params),
                'ping'        => new \stdClass(),
                'tools/list'  => ['tools' => $this->listTools()],
                'tools/call'  => $this->callTool($params),
                default       => null,
            };
        } catch (McpException $e) {
            return $isNotification ? null : $this->errorResponse($id, $e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', '[MCP] ' . $e->getMessage());

            return $isNotification ? null : $this->errorResponse($id, self::ERR_INTERNAL, 'Erro interno do servidor.');
        }

        // Notificações (notifications/initialized, notifications/cancelled...) não têm resposta.
        if ($isNotification) {
            return null;
        }

        if ($result === null) {
            return $this->errorResponse($id, self::ERR_METHOD_NOT_FOUND, 'Método não encontrado: ' . $message['method']);
        }

        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    private function initialize(array $params): array
    {
        $requested = (string) ($params['protocolVersion'] ?? '');
        $version   = in_array($requested, self::PROTOCOL_VERSIONS, true) ? $requested : self::PROTOCOL_VERSIONS[0];

        $result = [
            'protocolVersion' => $version,
            'capabilities'    => ['tools' => ['listChanged' => false]],
            'serverInfo'      => ['name' => $this->name, 'version' => $this->version],
        ];

        if ($this->instructions !== '') {
            $result['instructions'] = $this->instructions;
        }

        return $result;
    }

    private function listTools(): array
    {
        $list = [];
        foreach ($this->tools as $name => $tool) {
            $list[] = [
                'name'        => $name,
                'description' => $tool['description'],
                'inputSchema' => $tool['inputSchema'],
            ];
        }

        return $list;
    }

    private function callTool(array $params): array
    {
        $name = $params['name'] ?? null;
        if (!is_string($name) || !isset($this->tools[$name])) {
            throw new McpException('Ferramenta desconhecida: ' . (is_string($name) ? $name : '(vazio)'), self::ERR_INVALID_PARAMS);
        }

        $arguments = $params['arguments'] ?? [];
        if (!is_array($arguments)) {
            throw new McpException('"arguments" deve ser um objeto.', self::ERR_INVALID_PARAMS);
        }

        try {
            $output = ($this->tools[$name]['handler'])($arguments);
        } catch (ToolException $e) {
            // Erros de negócio/validação voltam como resultado (isError) para o modelo poder corrigir e tentar de novo.
            return $this->toolResult($e->payload(), true);
        } catch (Throwable $e) {
            log_message('error', "[MCP] ferramenta {$name}: " . $e->getMessage());

            return $this->toolResult(['message' => 'Erro interno ao executar a ferramenta.'], true);
        }

        return $this->toolResult($output, false);
    }

    private function toolResult(array $data, bool $isError): array
    {
        return [
            'content' => [[
                'type' => 'text',
                'text' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR),
            ]],
            'isError' => $isError,
        ];
    }

    private function errorResponse(mixed $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }
}
