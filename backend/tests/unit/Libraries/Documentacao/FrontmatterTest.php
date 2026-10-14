<?php

namespace Tests\Unit\Libraries\Documentacao;

use App\Libraries\Documentacao\Frontmatter;
use App\Libraries\Documentacao\FrontmatterInvalido;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Formato do cabeçalho das páginas da documentação interna.
 *
 * @internal
 */
final class FrontmatterTest extends CIUnitTestCase
{
    public function testSeparaCabecalhoECorpo(): void
    {
        $md = "---\ntitulo: Cadastro\nordem: 10\n---\n\n## Partes\n";

        [$dados, $corpo] = Frontmatter::separar($md);

        $this->assertSame(['titulo' => 'Cadastro', 'ordem' => 10], $dados);
        $this->assertSame("\n## Partes\n", $corpo);
    }

    public function testAceitaCrlfEBom(): void
    {
        [$dados, $corpo] = Frontmatter::separar("\xEF\xBB\xBF---\r\ntitulo: X\r\n---\r\ncorpo");

        $this->assertSame(['titulo' => 'X'], $dados);
        $this->assertSame('corpo', $corpo);
    }

    public function testLeTodosOsTiposDoSubconjunto(): void
    {
        $yaml = <<<'YAML'
            # comentário
            titulo: "Título: com dois-pontos"
            resumo: 'Aspas '' simples'
            ordem: 5
            telas: [agenda, agenda-todos]
            palavras_chave:
              - novo agendamento
              - "CNJ"
            YAML;

        $this->assertSame([
            'titulo'         => 'Título: com dois-pontos',
            'resumo'         => "Aspas ' simples",
            'ordem'          => 5,
            'telas'          => ['agenda', 'agenda-todos'],
            'palavras_chave' => ['novo agendamento', 'CNJ'],
        ], Frontmatter::ler($yaml));
    }

    public function testListasVazias(): void
    {
        $this->assertSame(['telas' => [], 'palavras_chave' => []], Frontmatter::ler("telas: []\npalavras_chave:"));
    }

    #[DataProvider('sintaxeInvalida')]
    public function testRecusaSintaxeInvalida(string $conteudo, string $campo): void
    {
        try {
            Frontmatter::separar($conteudo);
            $this->fail('Deveria recusar o cabeçalho.');
        } catch (FrontmatterInvalido $e) {
            $this->assertArrayHasKey($campo, $e->erros());
        }
    }

    public static function sintaxeInvalida(): array
    {
        return [
            'sem cabeçalho'         => ["## Só corpo\n", 'frontmatter'],
            'cabeçalho sem fim'     => ["---\ntitulo: X\n", 'frontmatter'],
            'linha fora do formato' => ["---\ntitulo: X\nsolto\n---\n", 'linha 2'],
            'campo repetido'        => ["---\ntitulo: X\ntitulo: Y\n---\n", 'titulo'],
            'lista sem fechar'      => ["---\ntitulo: X\ntelas: [list\n---\n", 'telas'],
            'chave com maiúscula'   => ["---\nTitulo: X\n---\n", 'linha 1'],
        ];
    }

    public function testCabecalhoMinimoEValido(): void
    {
        $this->assertSame([], Frontmatter::validar(['titulo' => 'Página']));
    }

    #[DataProvider('camposInvalidos')]
    public function testValidaCadaCampo(array $dados, string $campo): void
    {
        $this->assertArrayHasKey($campo, Frontmatter::validar($dados + ['titulo' => 'T']));
    }

    public static function camposInvalidos(): array
    {
        return [
            'campo desconhecido'      => [['autor' => 'x'], 'autor'],
            'título longo'            => [['titulo' => str_repeat('a', 121)], 'titulo'],
            'resumo longo'            => [['resumo' => str_repeat('a', 301)], 'resumo'],
            'ordem texto'             => [['ordem' => 'dez'], 'ordem'],
            'ordem negativa'          => [['ordem' => -1], 'ordem'],
            'módulo não existe aqui'  => [['modulo' => 'agenda'], 'modulo'],
            'permissão sem escopo'    => [['permissao' => 'documentacao'], 'permissao'],
            'telas não é lista'       => [['telas' => 'agenda'], 'telas'],
            'tela mal formada'        => [['telas' => ['Agenda Todos']], 'telas.0'],
            'telas repetidas'         => [['telas' => ['agenda', 'agenda']], 'telas'],
            'palavra vazia'           => [['palavras_chave' => ['']], 'palavras_chave.0'],
        ];
    }

    public function testTituloObrigatorio(): void
    {
        $this->assertArrayHasKey('titulo', Frontmatter::validar(['resumo' => 'x']));
        $this->assertArrayHasKey('titulo', Frontmatter::validar(['titulo' => '   ']));
    }

    /**
     * Toda página versionada em app/Docs segue o formato.
     */
    public function testPaginasDoRepositorioSaoValidas(): void
    {
        $arquivos = glob(APPPATH . 'Docs/*/*.md') ?: [];
        $this->assertNotEmpty($arquivos, 'app/Docs não tem páginas.');

        foreach ($arquivos as $arquivo) {
            [$dados] = Frontmatter::separar((string) file_get_contents($arquivo));
            $this->assertSame([], Frontmatter::validar($dados), $arquivo);
        }
    }
}
