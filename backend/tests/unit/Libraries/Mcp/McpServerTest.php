<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Mcp;

use App\Libraries\Mcp\McpServer;
use App\Libraries\Mcp\ToolException;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class McpServerTest extends CIUnitTestCase
{
    private McpServer $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server = new McpServer('teste', '9.9.9', 'Instruções');
        $this->server->registerTool('soma', 'Soma', ['type' => 'object'], static fn (array $a): array => ['r' => ($a['a'] ?? 0) + ($a['b'] ?? 0)]);
        $this->server->registerTool('falha', 'Falha', ['type' => 'object'], static function (): array {
            throw new ToolException('Dados inválidos.', ['x' => 'obrigatório']);
        });
        $this->server->registerTool('explode', 'Explode', ['type' => 'object'], static function (): array {
            throw new \RuntimeException('segredo interno');
        });
    }

    private function rpc(array $msg): ?array
    {
        return $this->server->handleRaw(json_encode($msg));
    }

    public function testInitializeNegociaVersaoEExpoeCapacidades(): void
    {
        $r = $this->rpc(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => '2025-03-26']]);

        $this->assertSame(1, $r['id']);
        $this->assertSame('2025-03-26', $r['result']['protocolVersion']);
        $this->assertArrayHasKey('tools', $r['result']['capabilities']);
        $this->assertSame('teste', $r['result']['serverInfo']['name']);
        $this->assertSame('Instruções', $r['result']['instructions']);
    }

    public function testInitializeComVersaoDesconhecidaUsaAMaisNova(): void
    {
        $r = $this->rpc(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => '1999-01-01']]);

        $this->assertSame(McpServer::PROTOCOL_VERSIONS[0], $r['result']['protocolVersion']);
    }

    public function testNotificacaoNaoTemResposta(): void
    {
        $this->assertNull($this->rpc(['jsonrpc' => '2.0', 'method' => 'notifications/initialized']));
    }

    public function testToolsListRetornaFerramentasRegistradas(): void
    {
        $r = $this->rpc(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list']);

        $names = array_column($r['result']['tools'], 'name');
        $this->assertSame(['soma', 'falha', 'explode'], $names);
        $this->assertArrayHasKey('inputSchema', $r['result']['tools'][0]);
    }

    public function testToolsCallExecutaFerramenta(): void
    {
        $r = $this->rpc(['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call', 'params' => ['name' => 'soma', 'arguments' => ['a' => 2, 'b' => 3]]]);

        $this->assertFalse($r['result']['isError']);
        $this->assertSame(['r' => 5], json_decode($r['result']['content'][0]['text'], true));
    }

    public function testErroDeNegocioViraResultadoComIsError(): void
    {
        $r = $this->rpc(['jsonrpc' => '2.0', 'id' => 4, 'method' => 'tools/call', 'params' => ['name' => 'falha']]);

        $this->assertTrue($r['result']['isError']);
        $payload = json_decode($r['result']['content'][0]['text'], true);
        $this->assertSame('Dados inválidos.', $payload['message']);
        $this->assertSame(['x' => 'obrigatório'], $payload['errors']);
    }

    public function testExcecaoInesperadaNaoVazaDetalhesInternos(): void
    {
        $r = $this->rpc(['jsonrpc' => '2.0', 'id' => 5, 'method' => 'tools/call', 'params' => ['name' => 'explode']]);

        $this->assertTrue($r['result']['isError']);
        $this->assertStringNotContainsString('segredo interno', $r['result']['content'][0]['text']);
    }

    public function testFerramentaDesconhecidaERetornaErroDeParametros(): void
    {
        $r = $this->rpc(['jsonrpc' => '2.0', 'id' => 6, 'method' => 'tools/call', 'params' => ['name' => 'nada']]);

        $this->assertSame(McpServer::ERR_INVALID_PARAMS, $r['error']['code']);
    }

    public function testMetodoDesconhecido(): void
    {
        $r = $this->rpc(['jsonrpc' => '2.0', 'id' => 7, 'method' => 'foo/bar']);

        $this->assertSame(McpServer::ERR_METHOD_NOT_FOUND, $r['error']['code']);
    }

    public function testJsonInvalidoERequisicaoInvalida(): void
    {
        $this->assertSame(McpServer::ERR_PARSE, $this->server->handleRaw('{oops')['error']['code']);
        $this->assertSame(McpServer::ERR_INVALID_REQUEST, $this->rpc(['id' => 1, 'method' => 'ping'])['error']['code']);
    }

    public function testBatchRetornaSomenteRespostasDeRequisicoes(): void
    {
        $r = $this->server->handleRaw(json_encode([
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'],
            ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'],
        ]));

        $this->assertCount(1, $r);
        $this->assertSame(1, $r[0]['id']);
    }
}
