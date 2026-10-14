<?php

namespace Tests\Unit\Services\Documentacao;

use App\Libraries\Documentacao\RenderizadorDocumentacao;
use App\Services\Documentacao\CatalogoDocumentacao;
use App\Services\Documentacao\DocumentacaoService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Leitura da documentação com permissões.
 *
 * @internal
 */
final class DocumentacaoServiceTest extends CIUnitTestCase
{
    private string $raiz;

    /** @var list<string> */
    private array $permissoes = ['prontuarios.ver'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->raiz = sys_get_temp_dir() . '/docs-' . bin2hex(random_bytes(4));
        mkdir($this->raiz);
        CatalogoDocumentacao::limparCache();

        $this->pagina('primeiros-passos/index.md', "titulo: Primeiros passos\nordem: 0\ntelas: [dashboard]", "Bem-vindo ao sistema.\n");
        $this->pagina('primeiros-passos/navegacao.md', "titulo: Navegação\nresumo: Menu lateral e barra superior.", "## Menu\n\nO menu lateral agrupa as telas.\n");
        $this->pagina('prontuarios/index.md', "titulo: Prontuários\nordem: 10\npermissao: prontuarios.ver");
        $this->pagina('prontuarios/vacinas.md', "titulo: Vacinas do paciente\ntelas: [prontuarios, prontuario-vacinas]\npalavras_chave: [V10, reforço anual]", "Informe o lote da vacina V10.\n");
        $this->pagina('financeiro/index.md', "titulo: Financeiro\nordem: 20\npermissao: financeiro.access", "Receitas e despesas.\n");
    }

    protected function tearDown(): void
    {
        foreach (glob($this->raiz . '/*/img/*') ?: [] as $f) {
            unlink($f);
        }
        foreach (glob($this->raiz . '/*/img') ?: [] as $d) {
            rmdir($d);
        }
        foreach (glob($this->raiz . '/*/*') ?: [] as $f) {
            unlink($f);
        }
        foreach (glob($this->raiz . '/*') ?: [] as $d) {
            rmdir($d);
        }
        rmdir($this->raiz);
        parent::tearDown();
    }

    private function pagina(string $caminho, string $cabecalho, string $corpo = "Texto.\n"): void
    {
        @mkdir(dirname($this->raiz . '/' . $caminho));
        file_put_contents($this->raiz . '/' . $caminho, "---\n{$cabecalho}\n---\n{$corpo}");
    }

    private function service(): DocumentacaoService
    {
        return new DocumentacaoService(
            new CatalogoDocumentacao($this->raiz, new RenderizadorDocumentacao('https://x.test/img/'), false),
            fn (string $p): bool => in_array($p, $this->permissoes, true),
        );
    }

    public function testArvoreRespeitaPermissao(): void
    {
        // financeiro: sem permissão
        $this->assertSame(['primeiros-passos', 'prontuarios'], array_column($this->service()->arvore(), 'slug'));

        $this->permissoes[] = 'financeiro.access';
        $this->assertSame(['primeiros-passos', 'prontuarios', 'financeiro'], array_column($this->service()->arvore(), 'slug'));
    }

    public function testPaginaInvisivelEhInexistente(): void
    {
        $service = $this->service();

        $this->assertNull($service->pagina('financeiro'), 'sem permissão');
        $this->assertNull($service->pagina('nao/existe'));
    }

    public function testHerancaDaCapaEsconde(): void
    {
        $this->permissoes = [];
        $this->assertNull($this->service()->pagina('prontuarios/vacinas'), 'herda prontuarios.ver da capa');
    }

    public function testPaginaCompletaComVizinhas(): void
    {
        $p = $this->service()->pagina('primeiros-passos/navegacao');

        $this->assertSame('Navegação', $p['titulo']);
        $this->assertSame('primeiros-passos', $p['secao']);
        $this->assertFalse($p['capa']);
        $this->assertSame('Menu lateral e barra superior.', $p['resumo']);
        $this->assertStringContainsString('<h2 id="menu">Menu</h2>', $p['html']);
        $this->assertSame([['nivel' => 2, 'texto' => 'Menu', 'ancora' => 'menu']], $p['sumario']);
        $this->assertSame(['slug' => 'primeiros-passos', 'titulo' => 'Primeiros passos'], $p['anterior']);
        $this->assertSame(['slug' => 'prontuarios', 'titulo' => 'Prontuários'], $p['proxima']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $p['atualizado_em']);

        $ultima = $this->service()->pagina('prontuarios/vacinas');
        $this->assertNull($ultima['proxima'], 'financeiro está invisível');
        $this->assertNull($this->service()->pagina('primeiros-passos')['anterior']);
    }

    public function testPorTela(): void
    {
        $service = $this->service();

        $this->assertSame('prontuarios/vacinas', $service->porTela('prontuario-vacinas')['slug']);
        $this->assertSame('primeiros-passos', $service->porTela('dashboard')['slug']);
        $this->assertNull($service->porTela('relatorio-extrato'));

        $this->permissoes = [];
        $this->assertNull($this->service()->porTela('prontuarios'), 'página sem permissão não serve de ajuda');
    }

    public function testBusca(): void
    {
        $service = $this->service();

        $this->assertSame(['prontuarios/vacinas'], array_column($service->buscar('v10'), 'slug'));
        $this->assertSame(['prontuarios/vacinas'], array_column($service->buscar('REFORCO anual'), 'slug'), 'sem acento e sem caixa');
        $this->assertSame([], $service->buscar('v10 inexistente'), 'todos os termos precisam aparecer');
        $this->assertSame([], $service->buscar('receitas'), 'página sem permissão não aparece');
        $this->assertSame([], $service->buscar('a'), 'termo curto é ignorado');

        $r = $service->buscar('menu');
        $this->assertSame('primeiros-passos/navegacao', $r[0]['slug']);
        $this->assertSame('Primeiros passos', $r[0]['secao']);
        $this->assertStringContainsString('menu lateral', $r[0]['trecho']);
    }

    public function testBuscaOrdenaPorRelevancia(): void
    {
        $this->pagina('primeiros-passos/atalhos.md', 'titulo: Atalhos', "Use o menu, o menu e o menu.\n");

        $this->assertSame('primeiros-passos/navegacao', $this->service()->buscar('lateral')[0]['slug']);
        $this->assertSame(['primeiros-passos/navegacao', 'primeiros-passos/atalhos'], array_column($this->service()->buscar('menu'), 'slug'), 'resumo vale mais que o corpo');
    }

    public function testTrechoLongoEAcentuado(): void
    {
        $this->pagina('primeiros-passos/longa.md', 'titulo: Longa', str_repeat('Texto de preenchimento. ', 20) . "Vacinação antirrábica aqui.\n" . str_repeat('Mais texto. ', 20));

        $trecho = $this->service()->buscar('antirrabica')[0]['trecho'];

        $this->assertStringStartsWith('…', $trecho);
        $this->assertStringEndsWith('…', $trecho);
        $this->assertStringContainsString('Vacinação antirrábica', $trecho);
    }

    public function testCaminhoDaImagem(): void
    {
        mkdir($this->raiz . '/prontuarios/img');
        file_put_contents($this->raiz . '/prontuarios/img/tela.webp', 'x');
        $service = $this->service();

        $this->assertSame(realpath($this->raiz . '/prontuarios/img/tela.webp'), $service->caminhoDaImagem('prontuarios/img/tela.webp', $this->raiz));
        $this->assertNull($service->caminhoDaImagem('prontuarios/img/nao-existe.webp', $this->raiz));
        $this->assertNull($service->caminhoDaImagem('prontuarios/vacinas.md', $this->raiz));
        $this->assertNull($service->caminhoDaImagem('../prontuarios/img/tela.webp', $this->raiz));
        $this->assertNull($service->caminhoDaImagem('prontuarios/img/../../x.webp', $this->raiz));
        $this->assertNull($service->caminhoDaImagem('prontuarios/img/tela.svg', $this->raiz));
    }
}
