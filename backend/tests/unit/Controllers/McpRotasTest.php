<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Filters\McpAuth;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Router\RouteCollection;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class McpRotasTest extends CIUnitTestCase
{
    private const TOKEN = 'abcdefghijklmnopqrstuvwxyz0123456789ABCD';

    protected function tearDown(): void
    {
        putenv('mcp.apiToken');
        parent::tearDown();
    }

    public function testRotaMcpUsaFiltroMcpAuthENaoApiAuth(): void
    {
        $routes = new RouteCollection(service('locator'), config('Modules'), config('Routing'));
        require APPPATH . 'Config/Routes.php';

        $this->assertSame(['mcpauth'], (array) $routes->getRoutesOptions('mcp', 'POST')['filter']);
        $this->assertSame('\App\Controllers\V1\Mcp::handle', $routes->getRoutes('POST')['mcp']);
    }

    private function request(?string $authorization): IncomingRequest
    {
        $request = service('incomingrequest', null, false);
        if ($authorization !== null) {
            $request->setHeader('Authorization', $authorization);
        }

        return $request;
    }

    public function testSemTokenConfiguradoFicaDesabilitado(): void
    {
        putenv('mcp.apiToken');

        $res = (new McpAuth())->before($this->request('Bearer ' . self::TOKEN));

        $this->assertSame(503, $res->getStatusCode());
    }

    public function testTokenCurtoDemaisNaoEhAceitoComoConfiguracao(): void
    {
        putenv('mcp.apiToken=curto');

        $this->assertSame(503, (new McpAuth())->before($this->request('Bearer curto'))->getStatusCode());
    }

    public function testRejeitaTokenAusenteOuErrado(): void
    {
        putenv('mcp.apiToken=' . self::TOKEN);

        $this->assertSame(401, (new McpAuth())->before($this->request(null))->getStatusCode());
        $this->assertSame(401, (new McpAuth())->before($this->request('Bearer errado'))->getStatusCode());
        $this->assertSame(401, (new McpAuth())->before($this->request('Basic ' . self::TOKEN))->getStatusCode());
    }

    public function testAceitaTokenCorreto(): void
    {
        putenv('mcp.apiToken=' . self::TOKEN);

        $this->assertNull((new McpAuth())->before($this->request('Bearer ' . self::TOKEN)));
    }
}
