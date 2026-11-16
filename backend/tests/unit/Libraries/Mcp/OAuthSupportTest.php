<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Mcp;

use App\Libraries\Mcp\OAuthSupport;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class OAuthSupportTest extends CIUnitTestCase
{
    public function testPkceChallengeConfereComVetorDaRfc7636(): void
    {
        $this->assertSame(
            'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            OAuthSupport::pkceChallenge('dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk')
        );
    }

    /** @dataProvider redirectsProvider */
    public function testRedirectUriPermitida(string $uri, bool $esperado): void
    {
        $this->assertSame($esperado, OAuthSupport::redirectUriPermitida($uri));
    }

    public static function redirectsProvider(): iterable
    {
        yield 'claude https'      => ['https://claude.ai/api/mcp/auth_callback', true];
        yield 'localhost http'    => ['http://localhost:8080/callback', true];
        yield 'loopback ip'       => ['http://127.0.0.1/cb', true];
        yield 'http externo'      => ['http://evil.example/cb', false];
        yield 'com fragmento'     => ['https://x.example/cb#a', false];
        yield 'esquema perigoso'  => ['javascript:alert(1)', false];
        yield 'sem host'          => ['https://', false];
    }

    public function testPkceObrigatorioParaClientePublico(): void
    {
        $this->assertNotNull(OAuthSupport::validarPkce(false, '', 'S256'));
        $this->assertNull(OAuthSupport::validarPkce(true, '', 'S256'));
        $this->assertNull(OAuthSupport::validarPkce(false, 'abc', 's256'));
        $this->assertNotNull(OAuthSupport::validarPkce(false, 'abc', 'plain'));
    }
}
