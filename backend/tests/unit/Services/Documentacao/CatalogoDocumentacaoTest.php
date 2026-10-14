<?php

namespace Tests\Unit\Services\Documentacao;

use App\Libraries\Documentacao\RenderizadorDocumentacao;
use App\Services\Documentacao\CatalogoDocumentacao;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Catálogo das páginas em arquivo da documentação interna.
 *
 * @internal
 */
final class CatalogoDocumentacaoTest extends CIUnitTestCase
{
    private string $raiz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->raiz = sys_get_temp_dir() . '/docs-' . bin2hex(random_bytes(4));
        mkdir($this->raiz);
        CatalogoDocumentacao::limparCache();
    }

    protected function tearDown(): void
    {
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

    private function catalogo(): CatalogoDocumentacao
    {
        return new CatalogoDocumentacao($this->raiz, new RenderizadorDocumentacao('https://x.test/img/'));
    }

    public function testSlugsEHerancaDaCapa(): void
    {
        $this->pagina('financeiro/index.md', "titulo: Financeiro\npermissao: financeiro.access");
        $this->pagina('financeiro/extrato.md', 'titulo: Extrato');
        $this->pagina('financeiro/dre.md', "titulo: DRE\npermissao: financeiro.edit");

        $paginas = $this->catalogo()->paginas();

        $this->assertSame(['financeiro', 'financeiro/dre', 'financeiro/extrato'], array_keys($paginas));
        $this->assertTrue($paginas['financeiro']['capa']);
        $this->assertSame('financeiro.access', $paginas['financeiro/extrato']['permissao']);
        $this->assertSame('financeiro.edit', $paginas['financeiro/dre']['permissao'], 'a página pode trocar a permissão da capa');
        $this->assertSame(100, $paginas['financeiro/extrato']['ordem']);
    }

    public function testArvoreOrdenadaEFiltrada(): void
    {
        $this->pagina('agenda/index.md', "titulo: Agenda\nordem: 20");
        $this->pagina('agenda/novo.md', "titulo: Novo agendamento\nordem: 10\nresumo: Como agendar");
        $this->pagina('agenda/faturar.md', "titulo: Faturar\nordem: 10");
        $this->pagina('agenda/encaixe.md', "titulo: Encaixe\nordem: 5\npermissao: agenda.encaixe");
        $this->pagina('primeiros-passos/index.md', "titulo: Primeiros passos\nordem: 0");
        $this->pagina('sem-capa/pagina.md', 'titulo: Avulsa');

        $arvore = $this->catalogo()->arvore();

        $this->assertSame(['primeiros-passos', 'agenda', 'sem-capa'], array_column($arvore, 'slug'));
        $this->assertSame('Sem Capa', $arvore[2]['titulo'], 'seção sem index.md usa o nome da pasta');
        $this->assertFalse($arvore[2]['capa']);
        $this->assertSame(['agenda/encaixe', 'agenda/faturar', 'agenda/novo'], array_column($arvore[1]['paginas'], 'slug'));
        $this->assertSame('Como agendar', $arvore[1]['paginas'][2]['resumo']);

        $semEncaixe = $this->catalogo()->arvore(static fn (array $p): bool => $p['permissao'] !== 'agenda.encaixe');
        $this->assertSame(['agenda/faturar', 'agenda/novo'], array_column($semEncaixe[1]['paginas'], 'slug'));

        $soPrimeiros = $this->catalogo()->arvore(static fn (array $p): bool => $p['secao'] === 'primeiros-passos');
        $this->assertSame(['primeiros-passos'], array_column($soPrimeiros, 'slug'), 'seção sem nada visível some');
    }

    public function testPaginasInvalidasFicamForaEApareceEmErros(): void
    {
        $this->pagina('ok/index.md', 'titulo: OK');
        $this->pagina('ok/sem-titulo.md', 'resumo: x');
        $this->pagina('ok/Maiuscula.md', 'titulo: X');
        file_put_contents($this->raiz . '/ok/sem-cabecalho.md', "# Sem cabeçalho\n");

        $catalogo = $this->catalogo();

        $this->assertSame(['ok'], array_keys($catalogo->paginas()));
        $this->assertArrayHasKey('titulo', $catalogo->erros()['ok/sem-titulo.md']);
        $this->assertArrayHasKey('arquivo', $catalogo->erros()['ok/Maiuscula.md']);
        $this->assertArrayHasKey('frontmatter', $catalogo->erros()['ok/sem-cabecalho.md']);
    }

    public function testRenderizadaETextoDeBusca(): void
    {
        $this->pagina('agenda/faturar.md', 'titulo: Faturar', "## Materiais\n\nVeja o [extrato](../relatorios/extrato.md) e ![tela](img/a.png).\n");

        $catalogo = $this->catalogo();
        $r = $catalogo->renderizada('agenda/faturar');

        $this->assertSame([['nivel' => 2, 'texto' => 'Materiais', 'ancora' => 'materiais']], $r['sumario']);
        $this->assertStringContainsString('href="documentacao/relatorios/extrato"', $r['html']);
        $this->assertSame('Materiais Veja o extrato e tela.', $catalogo->pagina('agenda/faturar')['texto']);
        $this->assertNull($catalogo->renderizada('nao/existe'));
    }

    public function testAssinaturaMudaQuandoArquivoMuda(): void
    {
        $this->pagina('a/index.md', 'titulo: A');
        $antes = $this->catalogo()->assinatura();

        $this->pagina('a/b.md', 'titulo: B');
        $this->assertNotSame($antes, $this->catalogo()->assinatura());

        $depois = $this->catalogo()->assinatura();
        CatalogoDocumentacao::limparCache();
        $this->assertNotSame($depois, $this->catalogo()->assinatura(), 'docs:cache:clear invalida tudo');
    }

    public function testCatalogoDoRepositorioNaoTemErros(): void
    {
        $catalogo = new CatalogoDocumentacao();

        $this->assertSame([], $catalogo->erros());
        $this->assertArrayHasKey('primeiros-passos', $catalogo->paginas());
    }
}
