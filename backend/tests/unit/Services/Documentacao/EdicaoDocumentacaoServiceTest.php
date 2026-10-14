<?php

namespace Tests\Unit\Services\Documentacao;

use App\Controllers\V1\DocumentacaoAdmin;
use App\Libraries\Documentacao\ErroDeValidacao;
use App\Libraries\Documentacao\NaoEncontrado;
use App\Repositories\DocumentacaoPaginaRepository;
use App\Services\Documentacao\CatalogoDocumentacao;
use App\Services\Documentacao\EdicaoDocumentacaoService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Edição das páginas da documentação pela interface.
 *
 * @internal
 */
final class EdicaoDocumentacaoServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use ControllerTestTrait;

    protected $migrate = false;
    protected $refresh = false;

    private string $raiz;
    private string $imagens;

    protected function setUp(): void
    {
        parent::setUp();
        service('cache')->clean();
        DocumentacaoPaginasBancoTest::criarTabela($this->db);

        $base = sys_get_temp_dir() . '/docs-edicao-' . bin2hex(random_bytes(4));
        $this->raiz = $base . '/docs';
        $this->imagens = $base . '/imagens';
        mkdir($this->raiz . '/processos', 0777, true);
        file_put_contents($this->raiz . '/processos/index.md', "---\ntitulo: Processos\npermissao: processos.ver\ntelas: [list]\n---\nCapa.\n");
        file_put_contents($this->raiz . '/processos/cadastro.md', "---\ntitulo: Cadastro\nordem: 10\ntelas: [process-form]\npalavras_chave: [CNJ]\n---\n## Partes\n");
        CatalogoDocumentacao::limparCache();
    }

    protected function tearDown(): void
    {
        $base = dirname($this->raiz);
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($base);
        parent::tearDown();
    }

    private function service(): EdicaoDocumentacaoService
    {
        $repo = new DocumentacaoPaginaRepository();

        return new EdicaoDocumentacaoService($repo, new CatalogoDocumentacao($this->raiz, null, $repo), $this->imagens);
    }

    private function erros(callable $acao): array
    {
        try {
            $acao();
            $this->fail('Deveria recusar.');
        } catch (ErroDeValidacao $e) {
            return $e->erros();
        }
    }

    public function testCriarComoRascunhoEEditar(): void
    {
        $r = $this->service()->criar([
            'slug' => 'processos/prazos', 'titulo' => 'Prazos', 'resumo' => 'Como contar', 'ordem' => '20',
            'telas' => ['tarefas', ' '], 'palavras_chave' => ['prazo'], 'markdown' => "## Contagem\n",
        ]);

        $p = $r['data'];
        $this->assertSame(['processos/prazos', 'rascunho', 20, ['tarefas'], false], [$p['slug'], $p['status'], $p['ordem'], $p['telas'], $p['tem_arquivo']]);
        $this->assertSame([], $r['avisos']);

        $r = $this->service()->atualizar($p['id'], ['slug' => 'processos/prazos', 'titulo' => 'Prazos processuais', 'markdown' => 'Novo texto [x](nao-existe.xlsx)']);
        $this->assertSame('Prazos processuais', $r['data']['titulo']);
        $this->assertSame('rascunho', $r['data']['status'], 'editar não publica');
        $this->assertSame(['Link recusado: nao-existe.xlsx'], $r['avisos']);
    }

    public function testValidacao(): void
    {
        $erros = $this->erros(fn () => $this->service()->criar([
            'slug' => 'processos/novo item', 'titulo' => '', 'permissao' => 'semescopo', 'ordem' => 'dez', 'telas' => ['Tela Ruim'],
        ]));
        $this->assertSame(['slug', 'titulo', 'ordem', 'permissao', 'telas.0'], array_keys($erros));

        $this->assertArrayHasKey('slug', $this->erros(fn () => $this->service()->criar(['slug' => 'processos/index', 'titulo' => 'X'])));
        $this->assertArrayHasKey('slug', $this->erros(fn () => $this->service()->criar(['slug' => 'a/b/c', 'titulo' => 'X'])));

        $this->service()->criar(['slug' => 'processos/x', 'titulo' => 'X']);
        $this->assertSame('Já existe uma página com este endereço.', $this->erros(fn () => $this->service()->criar(['slug' => 'processos/x', 'titulo' => 'Y']))['slug']);
    }

    public function testAvisoDeTelaJaUsada(): void
    {
        $r = $this->service()->criar(['slug' => 'processos/outra', 'titulo' => 'Outra', 'telas' => ['list']]);

        $this->assertSame(["A tela 'list' também abre a página 'processos' na ajuda contextual."], $r['avisos']);
    }

    public function testPublicarDespublicarEListar(): void
    {
        $id = $this->service()->criar(['slug' => 'processos/cadastro', 'titulo' => 'Cadastro (editado)', 'markdown' => 'x'])['data']['id'];

        $lista = array_column($this->service()->listar(), null, 'slug');
        $this->assertSame(['banco', 'rascunho', true], [$lista['processos/cadastro']['origem'], $lista['processos/cadastro']['status'], $lista['processos/cadastro']['tem_arquivo']]);
        $this->assertSame(['arquivo', null], [$lista['processos']['origem'], $lista['processos']['id']]);

        $this->assertSame('publicado', $this->service()->publicar($id)['status']);
        $this->assertSame('Cadastro (editado)', (new CatalogoDocumentacao($this->raiz))->pagina('processos/cadastro')['titulo'], 'publicada substitui o arquivo para todos');

        $this->assertSame('rascunho', $this->service()->despublicar($id)['status']);
        $this->assertSame('Cadastro', (new CatalogoDocumentacao($this->raiz))->pagina('processos/cadastro')['titulo']);

        $this->service()->excluir($id);
        $this->assertSame('arquivo', array_column($this->service()->listar(), null, 'slug')['processos/cadastro']['origem']);
        $this->expectException(NaoEncontrado::class);
        $this->service()->obter($id);
    }

    public function testCopiarDoArquivo(): void
    {
        $p = $this->service()->copiarDoArquivo('processos/cadastro')['data'];

        $this->assertSame(['Cadastro', 10, ['process-form'], ['CNJ'], "## Partes\n", 'rascunho'], [$p['titulo'], $p['ordem'], $p['telas'], $p['palavras_chave'], $p['markdown'], $p['status']]);
        $this->assertNull($p['permissao'], 'não copia a permissão herdada da capa');

        $this->assertArrayHasKey('slug', $this->erros(fn () => $this->service()->copiarDoArquivo('processos/cadastro')));
        $this->expectException(NaoEncontrado::class);
        $this->service()->copiarDoArquivo('processos/nao-existe');
    }

    public function testPreview(): void
    {
        $r = $this->service()->preview("## Título\n\n> [!DICA]\n> Use.\n\n[x](../outra/pagina.md)", 'processos/novo');

        $this->assertStringContainsString('<h2 id="titulo">', $r['html']);
        $this->assertStringContainsString('doc-aviso--dica', $r['html']);
        $this->assertStringContainsString('href="documentacao/outra/pagina"', $r['html']);
        $this->assertSame([['nivel' => 2, 'texto' => 'Título', 'ancora' => 'titulo']], $r['sumario']);
    }

    public function testEnviarImagem(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'img');
        imagepng(imagecreatetruecolor(2000, 1000), $tmp);

        $r = $this->service()->enviarImagem($tmp, 'Tela de Processos.PNG', 'processos');
        unlink($tmp);

        $this->assertMatchesRegularExpression('#^processos/img/tela-de-processos-[0-9a-f]{6}\.png$#', $r['caminho']);
        $this->assertSame([1600, 800], [$r['largura'], $r['altura']], 'reduzida para 1600 px');
        $this->assertSame([1600, 800], array_slice(getimagesize($this->imagens . '/' . $r['caminho']), 0, 2));
        $this->assertStringStartsWith('![Descreva a imagem](img/tela-de-processos-', $r['markdown']);

        $html = $this->service()->preview($r['markdown'], 'processos/x')['html'];
        $this->assertStringContainsString('width="1600" height="800"', $html, 'o motor acha a imagem enviada');

        $texto = tempnam(sys_get_temp_dir(), 'txt');
        file_put_contents($texto, 'não é imagem');
        $this->assertArrayHasKey('arquivo', $this->erros(fn () => $this->service()->enviarImagem($texto, 'a.png', 'processos')));
        $this->assertArrayHasKey('secao', $this->erros(fn () => $this->service()->enviarImagem($texto, 'a.png', '../x')));
        unlink($texto);
    }

    public function testControllerResponde422E404(): void
    {
        $this->request->setHeader('Content-Type', 'application/json');
        $r = $this->withBody(json_encode(['slug' => 'x y', 'titulo' => '']))
            ->controller(DocumentacaoAdmin::class)->execute('create');
        $this->assertSame(422, $r->response()->getStatusCode());
        $corpo = json_decode($r->response()->getBody(), true);
        $this->assertSame('error', $corpo['status']);
        $this->assertArrayHasKey('slug', $corpo['errors']);
        $this->assertArrayHasKey('titulo', $corpo['errors']);

        $this->resetServices();
        $this->assertSame(404, $this->controller(DocumentacaoAdmin::class)->execute('show', 9999)->response()->getStatusCode());
    }
}
