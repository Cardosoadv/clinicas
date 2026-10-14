<?php

declare(strict_types=1);

namespace App\Services\Documentacao;

use App\Libraries\Documentacao\RenderizadorDocumentacao;

/**
 * Confere as páginas da documentação em app/Docs (comando
 * `php spark docs:verificar`, que roda no CI).
 *
 * Erros acusados, por arquivo:
 * - cabeçalho inválido, nome de pasta/arquivo fora do padrão (vêm da validação
 *   do cabeçalho, via CatalogoDocumentacao::erros());
 * - `.md` fora de `<secao>/<pagina>.md` (seria ignorado sem aviso);
 * - link interno para página inexistente ou âncora inexistente;
 * - link ou imagem recusados pelo motor, imagem inexistente, sem texto alternativo
 *   ou com mais de 1600 px de largura;
 * - a mesma tela (`telas`) declarada em mais de uma página.
 *
 * Os slugs vêm do caminho do arquivo, então não há slug repetido entre arquivos.
 */
class VerificadorDocumentacao
{
    /** Prefixo fictício das imagens: só serve para reconhecer o caminho no HTML gerado. */
    private const PREFIXO_IMAGEM = 'https://docs.invalid/img/';

    private string $raiz;

    private CatalogoDocumentacao $catalogo;

    private RenderizadorDocumentacao $renderizador;

    public function __construct(?string $raiz = null)
    {
        $this->raiz = rtrim($raiz ?? APPPATH . 'Docs', '/');
        $this->renderizador = new RenderizadorDocumentacao(self::PREFIXO_IMAGEM, $this->raiz);
        // Só os arquivos: as páginas do banco são validadas ao salvar.
        $this->catalogo = new CatalogoDocumentacao($this->raiz, $this->renderizador, false);
    }

    /**
     * @return array<string, list<string>> Problemas por arquivo (relativo a app/Docs); vazio quando está tudo certo.
     */
    public function verificar(): array
    {
        $problemas = [];

        foreach ($this->catalogo->erros() as $arquivo => $erros) {
            foreach ($erros as $campo => $mensagem) {
                $problemas[$arquivo][] = "{$campo}: {$mensagem}";
            }
        }

        foreach ($this->foraDaEstrutura() as $arquivo) {
            $problemas[$arquivo][] = 'Fora da estrutura <secao>/<pagina>.md: o arquivo é ignorado.';
        }

        $paginas = $this->catalogo->paginas();
        $renderizadas = [];
        foreach ($paginas as $slug => $pagina) {
            $renderizadas[$slug] = $this->renderizador->renderizar((string) $this->catalogo->markdown($slug), $slug);
        }
        $ancoras = array_map(static fn (array $r): array => self::ancorasDoHtml($r['html']), $renderizadas);

        foreach ($renderizadas as $slug => $r) {
            $arquivo = $paginas[$slug]['arquivo'];

            foreach ($r['avisos'] as $aviso) {
                $problemas[$arquivo][] = $aviso;
            }

            foreach (self::linksInternos($r['html']) as [$destino, $ancora]) {
                if (!isset($paginas[$destino])) {
                    $problemas[$arquivo][] = "Link para página inexistente: {$destino}";
                } elseif ($ancora !== null && !in_array($ancora, $ancoras[$destino], true)) {
                    $problemas[$arquivo][] = "Link para âncora inexistente: {$destino}#{$ancora}";
                }
            }

            foreach (self::imagens($r['html']) as $caminho) {
                if (!is_file($this->raiz . '/' . $caminho)) {
                    $problemas[$arquivo][] = "Imagem inexistente: {$caminho}";
                }
            }
        }

        $donos = [];
        foreach ($paginas as $pagina) {
            foreach ($pagina['telas'] as $tela) {
                $donos[$tela][] = $pagina['arquivo'];
            }
        }
        foreach ($donos as $tela => $arquivos) {
            if (count($arquivos) > 1) {
                sort($arquivos);
                foreach ($arquivos as $arquivo) {
                    $problemas[$arquivo][] = "Tela '{$tela}' declarada em mais de uma página: " . implode(', ', $arquivos);
                }
            }
        }

        ksort($problemas);

        return array_map(static fn (array $l): array => array_values(array_unique($l)), $problemas);
    }

    /** @return list<string> `.md` que o catálogo não lê (na raiz ou em subpastas). */
    private function foraDaEstrutura(): array
    {
        if (!is_dir($this->raiz)) {
            return [];
        }

        $fora = [];
        $iterador = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->raiz, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterador as $arquivo) {
            if (strtolower($arquivo->getExtension()) !== 'md') {
                continue;
            }
            $relativo = substr($arquivo->getPathname(), strlen($this->raiz) + 1);
            if (substr_count($relativo, '/') !== 1) {
                $fora[] = $relativo;
            }
        }
        sort($fora);

        return $fora;
    }

    /** @return list<string> */
    private static function ancorasDoHtml(string $html): array
    {
        preg_match_all('/<h[1-6] id="([^"]+)"/', $html, $m);

        return $m[1];
    }

    /** @return list<array{0: string, 1: ?string}> [slug, âncora] de cada link `documentacao/...`. */
    private static function linksInternos(string $html): array
    {
        preg_match_all('#href="' . preg_quote(RenderizadorDocumentacao::ROTA_SPA, '#') . '([^"\#]*)(?:\#([^"]*))?"#', $html, $m, PREG_SET_ORDER);

        return array_map(static fn (array $l): array => [html_entity_decode($l[1]), isset($l[2]) && $l[2] !== '' ? html_entity_decode($l[2]) : null], $m);
    }

    /** @return list<string> Caminhos (relativos a app/Docs) das imagens aceitas pelo motor. */
    private static function imagens(string $html): array
    {
        preg_match_all('#src="' . preg_quote(self::PREFIXO_IMAGEM, '#') . '([^"]+)"#', $html, $m);

        return $m[1];
    }
}
