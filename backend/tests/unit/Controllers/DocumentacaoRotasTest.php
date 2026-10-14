<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Filters\ApiPermission;
use CodeIgniter\Router\RouteCollection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\AuthGroups;

/**
 * Rotas e permissões da documentação interna: a leitura exige só login
 * (`apiauth`); a edição exige também `documentacao.editar`.
 *
 * @internal
 */
final class DocumentacaoRotasTest extends CIUnitTestCase
{
    private static function rotas(): RouteCollection
    {
        $routes = new RouteCollection(service('locator'), config('Modules'), config('Routing'));
        require APPPATH . 'Config/Routes.php';

        return $routes;
    }

    /** @return list<string> */
    private static function filtros(RouteCollection $rotas, string $caminho, string $verbo): array
    {
        return (array) ($rotas->getRoutesOptions($caminho, $verbo)['filter'] ?? []);
    }

    public function testRotasDeLeituraExigemLogin(): void
    {
        $esperadas = [
            'api/v1/documentacao'              => '\App\Controllers\V1\Documentacao::index',
            'api/v1/documentacao/busca'        => '\App\Controllers\V1\Documentacao::busca',
            'api/v1/documentacao/paginas/(.*)' => '\App\Controllers\V1\Documentacao::pagina/$1',
            'api/v1/documentacao/tela/([^/]+)' => '\App\Controllers\V1\Documentacao::tela/$1',
            'api/v1/documentacao/imagens/(.*)' => '\App\Controllers\V1\Documentacao::imagem/$1',
        ];
        $rotas = self::rotas();
        $get = $rotas->getRoutes('GET');

        foreach ($esperadas as $caminho => $destino) {
            $this->assertSame($destino, $get[$caminho] ?? null, "GET {$caminho}");
            $this->assertContains('apiauth', self::filtros($rotas, $caminho, 'GET'), $caminho);
        }
    }

    public function testRotasDeEdicaoExigemDocumentacaoEditar(): void
    {
        $esperadas = [
            ['GET', 'api/v1/documentacao/admin/paginas', 'index'],
            ['POST', 'api/v1/documentacao/admin/paginas', 'create'],
            ['GET', 'api/v1/documentacao/admin/paginas/([0-9]+)', 'show/$1'],
            ['PUT', 'api/v1/documentacao/admin/paginas/([0-9]+)', 'update/$1'],
            ['DELETE', 'api/v1/documentacao/admin/paginas/([0-9]+)', 'delete/$1'],
            ['POST', 'api/v1/documentacao/admin/paginas/([0-9]+)/publicar', 'publicar/$1'],
            ['POST', 'api/v1/documentacao/admin/paginas/([0-9]+)/despublicar', 'despublicar/$1'],
            ['POST', 'api/v1/documentacao/admin/paginas/de-arquivo/(.*)', 'deArquivo/$1'],
            ['POST', 'api/v1/documentacao/admin/preview', 'preview'],
            ['POST', 'api/v1/documentacao/admin/imagens', 'imagem'],
        ];
        $rotas = self::rotas();

        foreach ($esperadas as [$verbo, $caminho, $metodo]) {
            $this->assertSame('\\App\\Controllers\\V1\\DocumentacaoAdmin::' . $metodo, $rotas->getRoutes($verbo)[$caminho] ?? null, "{$verbo} {$caminho}");
            $filtros = self::filtros($rotas, $caminho, $verbo);
            $this->assertContains('apiauth', $filtros, $caminho);
            $this->assertContains('apipermission:documentacao.editar', $filtros, $caminho);
        }
    }

    public function testFiltroDePermissaoRegistrado(): void
    {
        $this->assertSame(ApiPermission::class, config('Filters')->aliases['apipermission']);
    }

    public function testQuemEditaADocumentacao(): void
    {
        $auth = new AuthGroups();
        $this->assertArrayHasKey('documentacao.editar', $auth->permissions);

        foreach (['superadmin', 'admin', 'developer'] as $grupo) {
            $this->assertNotEmpty(array_intersect(['documentacao.*', 'documentacao.editar'], $auth->matrix[$grupo]), $grupo);
        }
        foreach (['user', 'beta'] as $grupo) {
            $this->assertSame([], preg_grep('/^documentacao\./', $auth->matrix[$grupo]), "{$grupo} só lê");
        }
    }
}
