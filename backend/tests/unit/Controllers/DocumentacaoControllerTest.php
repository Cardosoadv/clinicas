<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\V1\Documentacao;
use App\Services\Documentacao\CatalogoDocumentacao;
use App\Services\Documentacao\DocumentacaoService;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;

/**
 * Respostas da API de leitura da documentação sobre as páginas de app/Docs.
 *
 * @internal
 */
final class DocumentacaoControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        CatalogoDocumentacao::limparCache();

        // Só os arquivos (sem banco) e um usuário sem nenhuma permissão.
        Services::injectMock('documentacao', new DocumentacaoService(
            new CatalogoDocumentacao(null, null, false),
            static fn (string $p): bool => false,
        ));
    }

    protected function tearDown(): void
    {
        Services::resetSingle('documentacao');
        parent::tearDown();
    }

    private function json($resultado): array
    {
        return json_decode($resultado->response()->getBody(), true);
    }

    public function testIndice(): void
    {
        $r = $this->controller(Documentacao::class)->execute('index');

        $this->assertSame(200, $r->response()->getStatusCode());
        $corpo = $this->json($r);
        $this->assertSame('success', $corpo['status']);
        $this->assertSame('primeiros-passos', $corpo['data'][0]['slug']);
    }

    public function testPaginaComSlugEmSegmentos(): void
    {
        $r = $this->controller(Documentacao::class)->execute('pagina', 'agenda', 'faturar');

        $this->assertSame(200, $r->response()->getStatusCode());
        $this->assertSame('Faturar um atendimento', $this->json($r)['data']['titulo']);
    }

    public function testPaginaInexistente404(): void
    {
        $r = $this->controller(Documentacao::class)->execute('pagina', 'nao', 'existe');

        $this->assertSame(404, $r->response()->getStatusCode());
        $this->assertSame('error', $this->json($r)['status']);
    }

    public function testTela(): void
    {
        $this->assertSame('primeiros-passos/painel', $this->json($this->controller(Documentacao::class)->execute('tela', 'dashboard'))['data']['slug']);
        $this->assertSame(404, $this->controller(Documentacao::class)->execute('tela', 'tela-sem-ajuda')->response()->getStatusCode());
    }

    public function testPaginaComPermissaoQueOUsuarioNaoTemEh404(): void
    {
        $this->assertSame(404, $this->controller(Documentacao::class)->execute('pagina', 'ajuda', 'editar')->response()->getStatusCode());
    }

    public function testBusca(): void
    {
        $request = Services::incomingrequest(null, false);
        $request->setGlobal('get', ['q' => 'calculadora']);
        $r = $this->withRequest($request)->controller(Documentacao::class)->execute('busca');

        $this->assertContains('prontuarios/evolucao', array_column($this->json($r)['data'], 'slug'));
    }

    public function testImagemRecusaCaminhoForaDaPasta(): void
    {
        $this->assertSame(404, $this->controller(Documentacao::class)->execute('imagem', '..', '..', 'Config', 'App.php')->response()->getStatusCode());
    }
}
