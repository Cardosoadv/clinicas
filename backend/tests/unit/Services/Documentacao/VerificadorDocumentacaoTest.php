<?php

namespace Tests\Unit\Services\Documentacao;

use App\Services\Documentacao\CatalogoDocumentacao;
use App\Services\Documentacao\VerificadorDocumentacao;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\StreamFilterTrait;

/**
 * Verificação das páginas da documentação (comando docs:verificar).
 *
 * @internal
 */
final class VerificadorDocumentacaoTest extends CIUnitTestCase
{
    use StreamFilterTrait;

    private string $raiz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->raiz = sys_get_temp_dir() . '/docs-verif-' . bin2hex(random_bytes(4));
        mkdir($this->raiz);
        CatalogoDocumentacao::limparCache();
    }

    protected function tearDown(): void
    {
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->raiz, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($this->raiz);
        parent::tearDown();
    }

    private function arquivo(string $caminho, string $conteudo): void
    {
        $completo = $this->raiz . '/' . $caminho;
        if (!is_dir(dirname($completo))) {
            mkdir(dirname($completo), 0777, true);
        }
        file_put_contents($completo, $conteudo);
    }

    private function pagina(string $caminho, string $cabecalho, string $corpo = "Texto.\n"): void
    {
        $this->arquivo($caminho, "---\n{$cabecalho}\n---\n{$corpo}");
    }

    private function verificar(): array
    {
        return (new VerificadorDocumentacao($this->raiz))->verificar();
    }

    public function testTudoCertoNaoTemProblemas(): void
    {
        $this->pagina('a/index.md', "titulo: A\ntelas: [dashboard]", "## Seção\n\n[b](b.md#detalhe) ![tela](img/t.webp)\n");
        $this->pagina('a/b.md', "titulo: B\ntelas: [list]", "## Detalhe\n\n[volta](index.md) [tela](/agenda) [ext](https://x.test)\n");
        $this->arquivo('a/img/t.webp', 'x');

        $this->assertSame([], $this->verificar());
    }

    public function testCadaTipoDeErro(): void
    {
        $this->pagina('a/index.md', "titulo: A\ntelas: [dashboard]", implode("\n\n", [
            '## Topo',
            '[some](nao-existe.md)',
            '[âncora](b.md#nao-tem)',
            '[ok](b.md#existe)',
            '[planilha](dados.xlsx)',
            '![sem arquivo](img/nao-existe.webp)',
            '![](img/sem-alt.webp)',
            '![fora](../../x.png)',
        ]) . "\n");
        $this->pagina('a/b.md', "titulo: B\ntelas: [dashboard]", "## Existe\n");
        $this->pagina('a/sem-titulo.md', 'resumo: x');
        $this->pagina('a/modulo.md', "titulo: M\nmodulo: agenda");
        $this->arquivo('a/img/sem-alt.webp', 'x');
        $this->arquivo('solto.md', "---\ntitulo: X\n---\n");
        $this->arquivo('a/sub/fundo.md', "---\ntitulo: X\n---\n");

        $p = $this->verificar();

        $this->assertSame(['a/b.md', 'a/index.md', 'a/modulo.md', 'a/sem-titulo.md', 'a/sub/fundo.md', 'solto.md'], array_keys($p));
        $this->assertSame([
            'Link recusado: dados.xlsx',
            'Imagem sem texto alternativo: img/sem-alt.webp',
            'Imagem recusada (use <secao>/img/arquivo.webp): ../../x.png',
            'Link para página inexistente: a/nao-existe',
            'Link para âncora inexistente: a/b#nao-tem',
            'Imagem inexistente: a/img/nao-existe.webp',
            "Tela 'dashboard' declarada em mais de uma página: a/b.md, a/index.md",
        ], $p['a/index.md']);
        $this->assertSame(["Tela 'dashboard' declarada em mais de uma página: a/b.md, a/index.md"], $p['a/b.md']);
        $this->assertStringStartsWith('titulo:', $p['a/sem-titulo.md'][0]);
        $this->assertSame(['modulo: Campo desconhecido.'], $p['a/modulo.md']);
        $this->assertStringContainsString('Fora da estrutura', $p['solto.md'][0]);
        $this->assertStringContainsString('Fora da estrutura', $p['a/sub/fundo.md'][0]);
    }

    public function testImagemLargaDemais(): void
    {
        $this->pagina('a/index.md', 'titulo: A', "![Larga](img/larga.png)\n");
        mkdir($this->raiz . '/a/img');
        imagepng(imagecreatetruecolor(2000, 50), $this->raiz . '/a/img/larga.png');

        $this->assertSame(['a/index.md' => ['Imagem com 2000 px de largura (máximo 1600): img/larga.png']], $this->verificar());
    }

    public function testLinkParaCapaComAncora(): void
    {
        $this->pagina('a/index.md', 'titulo: A', "## Início rápido\n");
        $this->pagina('b/x.md', 'titulo: X', "[capa](../a/index.md#inicio-rapido) [capa errada](../a/index.md#outra)\n");

        $this->assertSame(['b/x.md' => ['Link para âncora inexistente: a#outra']], $this->verificar());
    }

    public function testDocumentacaoDoRepositorioPassa(): void
    {
        $this->assertSame([], (new VerificadorDocumentacao())->verificar());
    }

    public function testComando(): void
    {
        $this->pagina('a/index.md', 'titulo: A', "[x](nao.md)\n");

        command("docs:verificar --raiz {$this->raiz}");
        $this->assertStringContainsString('Link para página inexistente: a/nao', $this->getStreamFilterBuffer());
        $this->assertStringContainsString('1 problema(s) em 1 arquivo(s).', $this->getStreamFilterBuffer());

        $this->resetStreamFilterBuffer();
        command('docs:verificar');
        $this->assertStringContainsString('Documentação sem problemas.', $this->getStreamFilterBuffer());
    }
}
