<?php

namespace Tests\Unit\Services\Documentacao;

use App\Database\Migrations\CreateDocumentacaoPaginas;
use App\Libraries\Documentacao\RenderizadorDocumentacao;
use App\Models\DocumentacaoPaginaModel;
use App\Repositories\DocumentacaoPaginaRepository;
use App\Services\Documentacao\CatalogoDocumentacao;
use App\Services\Documentacao\DocumentacaoService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * Páginas da documentação no banco: precedência sobre os arquivos e rascunhos.
 *
 * @internal
 */
final class DocumentacaoPaginasBancoTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;
    protected $refresh = false;

    private string $raiz;

    /** @var list<string> */
    private array $permissoes = [];

    protected function setUp(): void
    {
        parent::setUp();
        service('cache')->clean();

        self::criarTabela($this->db);

        $this->raiz = sys_get_temp_dir() . '/docs-banco-' . bin2hex(random_bytes(4));
        mkdir($this->raiz . '/processos', 0777, true);
        file_put_contents($this->raiz . '/processos/index.md', "---\ntitulo: Processos (arquivo)\npermissao: documentacao.ler\n---\nTexto do arquivo.\n");
        file_put_contents($this->raiz . '/processos/cadastro.md', "---\ntitulo: Cadastro (arquivo)\n---\n## Do arquivo\n");
        CatalogoDocumentacao::limparCache();
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->raiz . '/processos/*') ?: []);
        rmdir($this->raiz . '/processos');
        rmdir($this->raiz);
        parent::tearDown();
    }

    /** Recria a tabela documentacao_paginas pela migration. */
    public static function criarTabela($db): void
    {
        require_once APPPATH . 'Database/Migrations/2026-10-14-120000_CreateDocumentacaoPaginas.php';
        $forge = Database::forge($db);
        (new CreateDocumentacaoPaginas($forge))->down();
        (new CreateDocumentacaoPaginas($forge))->up();
    }

    private function gravar(array $dados): int
    {
        $model = new DocumentacaoPaginaModel();
        $id = $model->insert($dados + ['status' => 'rascunho', 'ordem' => 100], true);
        $this->assertNotFalse($id, implode(' ', $model->errors()));
        CatalogoDocumentacao::limparCache();

        return (int) $id;
    }

    private function catalogo(): CatalogoDocumentacao
    {
        return new CatalogoDocumentacao($this->raiz, new RenderizadorDocumentacao('https://x.test/img/'), new DocumentacaoPaginaRepository());
    }

    private function service(): DocumentacaoService
    {
        return new DocumentacaoService($this->catalogo(), fn (string $p): bool => in_array($p, $this->permissoes, true));
    }

    public function testRascunhoNaoSubstituiOArquivoParaQuemLe(): void
    {
        $this->permissoes = ['documentacao.ler'];
        $this->gravar(['slug' => 'processos/cadastro', 'titulo' => 'Cadastro (banco)', 'markdown' => "## Do banco\n"]);

        $leitor = $this->catalogo()->pagina('processos/cadastro');
        $this->assertSame('Cadastro (arquivo)', $leitor['titulo']);
        $this->assertSame('arquivo', $leitor['origem']);

        $editor = $this->catalogo()->comRascunhos()->pagina('processos/cadastro');
        $this->assertSame('Cadastro (banco)', $editor['titulo']);
        $this->assertSame(['banco', 'rascunho', true], [$editor['origem'], $editor['status'], $editor['tem_arquivo']]);
        $this->assertStringContainsString('<h2 id="do-banco">', $this->catalogo()->comRascunhos()->renderizada('processos/cadastro')['html']);
    }

    public function testPublicadoSubstituiOArquivoParaTodos(): void
    {
        $this->permissoes = ['documentacao.ler'];
        $id = $this->gravar(['slug' => 'processos/cadastro', 'titulo' => 'Cadastro (banco)', 'markdown' => "## Do banco\n"]);
        (new DocumentacaoPaginaModel())->update($id, ['status' => 'publicado']);
        CatalogoDocumentacao::limparCache();

        $p = $this->service()->pagina('processos/cadastro');
        $this->assertSame('Cadastro (banco)', $p['titulo']);
        $this->assertSame(['banco', 'publicado', $id], [$p['origem'], $p['status'], $p['id']]);
        $this->assertStringContainsString('<h2 id="do-banco">', $p['html']);
        $this->assertSame('documentacao.ler', $this->catalogo()->pagina('processos/cadastro')['permissao'], 'herda a permissão da capa do arquivo');
    }

    public function testRascunhoNovoSoParaQuemEdita(): void
    {
        $this->permissoes = ['documentacao.ler'];
        $this->gravar(['slug' => 'processos/novidade', 'titulo' => 'Novidade', 'resumo' => 'Ainda em escrita', 'markdown' => 'Texto novo.']);

        $this->assertNull($this->service()->pagina('processos/novidade'));
        $this->assertNotContains('processos/novidade', array_column($this->service()->arvore()[0]['paginas'], 'slug'));
        $this->assertSame([], $this->service()->buscar('novidade'));

        $this->permissoes[] = 'documentacao.editar';
        $this->assertSame('rascunho', $this->service()->pagina('processos/novidade')['status']);
        $itens = array_column($this->service()->arvore()[0]['paginas'], 'status', 'slug');
        $this->assertSame('rascunho', $itens['processos/novidade']);
        $this->assertSame('publicado', $itens['processos/cadastro']);
        $this->assertSame(['processos/novidade'], array_column($this->service()->buscar('novidade'), 'slug'));
    }

    public function testCapaDoBancoPassaSuaPermissaoAsPaginas(): void
    {
        $id = $this->gravar(['slug' => 'processos', 'titulo' => 'Processos (banco)', 'permissao' => 'processos.access', 'markdown' => 'Capa.']);
        (new DocumentacaoPaginaModel())->update($id, ['status' => 'publicado']);
        CatalogoDocumentacao::limparCache();

        $this->assertSame('processos.access', $this->catalogo()->pagina('processos/cadastro')['permissao']);
        $this->assertNull($this->service()->pagina('processos/cadastro'), 'sem processos.access');
    }

    public function testExcluirVoltaAValerOArquivo(): void
    {
        $id = $this->gravar(['slug' => 'processos/cadastro', 'titulo' => 'Cadastro (banco)', 'status' => 'publicado', 'markdown' => 'x']);
        $this->assertSame('banco', $this->catalogo()->pagina('processos/cadastro')['origem']);

        (new DocumentacaoPaginaModel())->delete($id);
        CatalogoDocumentacao::limparCache();

        $this->assertSame('arquivo', $this->catalogo()->pagina('processos/cadastro')['origem']);
    }

    public function testListasEmJson(): void
    {
        $id = $this->gravar(['slug' => 'processos/telas', 'titulo' => 'T', 'telas' => json_encode(['list']), 'palavras_chave' => json_encode(['cnj', 'número']), 'markdown' => '']);

        $linha = (new DocumentacaoPaginaRepository())->porId($id);
        $this->assertSame(['list'], $linha['telas']);
        $this->assertSame(['cnj', 'número'], $linha['palavras_chave']);
    }

    public function testSlugUnico(): void
    {
        $this->gravar(['slug' => 'processos/x', 'titulo' => 'X', 'markdown' => '']);
        $model = new DocumentacaoPaginaModel();

        $this->assertFalse($model->insert(['slug' => 'processos/x', 'titulo' => 'Y', 'markdown' => '']));
        $this->assertSame('Já existe uma página com este endereço.', $model->errors()['slug']);
    }

    public function testSemATabelaSegueComOsArquivos(): void
    {
        Database::forge($this->db)->dropTable('documentacao_paginas', true);
        CatalogoDocumentacao::limparCache();

        $this->assertSame(['processos', 'processos/cadastro'], array_keys($this->catalogo()->paginas()));
    }
}
