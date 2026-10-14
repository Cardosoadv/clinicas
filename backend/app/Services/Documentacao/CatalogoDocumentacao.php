<?php

declare(strict_types=1);

namespace App\Services\Documentacao;

use App\Libraries\Documentacao\Frontmatter;
use App\Libraries\Documentacao\FrontmatterInvalido;
use App\Libraries\Documentacao\RenderizadorDocumentacao;
use App\Repositories\DocumentacaoPaginaRepository;

/**
 * Catálogo das páginas da documentação interna: as versionadas em app/Docs
 * (formato em docs/documentacao-interna.md) e as criadas pela interface,
 * guardadas no banco (tabela documentacao_paginas).
 *
 * Página do banco com o mesmo slug de um arquivo o **substitui depois de
 * publicada**. Rascunho do banco só existe na visão de quem edita
 * (`comRascunhos()`): para os demais vale a versão do arquivo, se houver.
 * Cada página traz `origem` (arquivo|banco) e `status` (publicado|rascunho).
 *
 * Varre `<secao>/<pagina>.md`, lê os cabeçalhos, aplica a herança da capa
 * (`permissao` do `index.md` da seção) e monta a árvore ordenada e
 * o texto usado pela busca. Página com cabeçalho inválido fica fora do
 * catálogo e aparece em `erros()`, sem derrubar o restante do manual.
 *
 * Cache: o catálogo e o HTML de cada página ficam no cache do CodeIgniter sob
 * uma assinatura (arquivos + datas de modificação + versão do motor). Editar
 * um arquivo muda a assinatura; gravar no banco e `docs:cache:clear` trocam a
 * versão (`limparCache()`) e descartam tudo. Sem a tabela (migration ainda não
 * rodou) ou com o banco fora do ar, o catálogo segue só com os arquivos.
 */
class CatalogoDocumentacao
{
    private const RE_NOME      = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
    private const CHAVE_VERSAO = 'documentacao_versao';
    private const TTL          = 86400;

    private string $raiz;

    /** @var array{arquivos: array<string, array<string, mixed>>, banco: array<string, array<string, mixed>>, erros: array<string, array<string, string>>}|null */
    private ?array $dados = null;

    /** @var array<string, array<string, mixed>>|null Páginas efetivas da visão atual. */
    private ?array $efetivas = null;

    private ?string $assinatura = null;

    private bool $comRascunhos = false;

    /** Repositório das páginas do banco; `false` desliga a fonte do banco (ex.: docs:verificar). */
    private DocumentacaoPaginaRepository|false $banco;

    public function __construct(
        ?string $raiz = null,
        private ?RenderizadorDocumentacao $renderizador = null,
        DocumentacaoPaginaRepository|false|null $banco = null,
    ) {
        $this->raiz = rtrim($raiz ?? self::raizPadrao(), '/');
        $this->banco = $banco ?? new DocumentacaoPaginaRepository();
    }

    /** Pasta das páginas em arquivo deste catálogo. */
    public function raiz(): string
    {
        return $this->raiz;
    }

    /** Pasta padrão das páginas em arquivo. */
    public static function raizPadrao(): string
    {
        return rtrim(APPPATH . 'Docs', '/');
    }

    /** Imagens enviadas pela interface: `<secao>/img/<arquivo>`, em writable (fora do deploy). */
    public static function pastaImagensEnviadas(): string
    {
        return WRITEPATH . 'uploads/documentacao';
    }

    /** Mesmo catálogo, na visão de quem edita: rascunhos do banco no lugar dos arquivos. */
    public function comRascunhos(): static
    {
        $copia = clone $this;
        $copia->comRascunhos = true;
        $copia->efetivas = null;

        return $copia;
    }

    /** Descarta o catálogo e as páginas em cache (comando `docs:cache:clear`). */
    public static function limparCache(): void
    {
        service('cache')->save(self::CHAVE_VERSAO, uniqid('', true), 0);
    }

    /**
     * Todas as páginas válidas da visão atual, por slug, já com a herança da capa.
     *
     * @return array<string, array{slug: string, secao: string, capa: bool, titulo: string, resumo: string, ordem: int,
     *     permissao: ?string, telas: list<string>, palavras_chave: list<string>, texto: string,
     *     arquivo: ?string, atualizado_em: int, origem: string, status: string, id: ?int, tem_arquivo: bool}>
     */
    public function paginas(): array
    {
        if ($this->efetivas !== null) {
            return $this->efetivas;
        }

        $dados = $this->dados();
        $efetivas = $dados['arquivos'];
        foreach ($dados['banco'] as $slug => $pagina) {
            if ($pagina['status'] === 'publicado' || $this->comRascunhos) {
                $efetivas[$slug] = $pagina;
            }
        }
        foreach ($efetivas as $slug => $pagina) {
            $efetivas[$slug]['tem_arquivo'] = isset($dados['arquivos'][$slug]);
        }

        // Herança da capa: página sem permissão usa a da capa da sua seção.
        foreach ($efetivas as $slug => $pagina) {
            $capa = $efetivas[$pagina['secao']] ?? null;
            if ($pagina['capa'] || $capa === null) {
                continue;
            }
            $efetivas[$slug]['permissao'] ??= $capa['permissao'];
        }
        ksort($efetivas);

        return $this->efetivas = $efetivas;
    }

    /**
     * As duas fontes, sem mesclar: para a lista de edição.
     *
     * @return array{arquivos: array<string, array<string, mixed>>, banco: array<string, array<string, mixed>>}
     */
    public function fontes(): array
    {
        $dados = $this->dados();

        return ['arquivos' => $dados['arquivos'], 'banco' => $dados['banco']];
    }

    public function pagina(string $slug): ?array
    {
        return $this->paginas()[$slug] ?? null;
    }

    /**
     * Arquivos recusados, com os erros de cada um (caminho relativo a app/Docs).
     *
     * @return array<string, array<string, string>>
     */
    public function erros(): array
    {
        return $this->dados()['erros'];
    }

    /**
     * Árvore ordenada: seções (por `ordem` da capa, depois título) e as páginas
     * de cada uma, sem a capa. `$visivel` filtra páginas (permissão);
     * a seção some quando não sobra nada visível nela.
     *
     * @param (callable(array): bool)|null $visivel
     *
     * @return list<array{slug: string, titulo: string, resumo: string, capa: bool, paginas: list<array{slug: string, titulo: string, resumo: string}>}>
     */
    public function arvore(?callable $visivel = null): array
    {
        $porSecao = [];
        foreach ($this->paginas() as $pagina) {
            if ($visivel !== null && !$visivel($pagina)) {
                continue;
            }
            $porSecao[$pagina['secao']][] = $pagina;
        }

        $secoes = [];
        foreach ($porSecao as $secao => $paginas) {
            $capa = $this->pagina($secao);
            $capaVisivel = $capa !== null && in_array($capa, $paginas, true);
            $filhas = array_values(array_filter($paginas, static fn (array $p): bool => !$p['capa']));
            usort($filhas, self::comparar(...));

            $secoes[] = [
                'slug'    => $secao,
                'titulo'  => $capa['titulo'] ?? self::tituloDaPasta($secao),
                'resumo'  => $capaVisivel ? $capa['resumo'] : '',
                'ordem'   => $capa['ordem'] ?? Frontmatter::ORDEM_PADRAO,
                'capa'    => $capaVisivel,
                'paginas' => array_map(static fn (array $p): array => [
                    'slug'   => $p['slug'],
                    'titulo' => $p['titulo'],
                    'resumo' => $p['resumo'],
                    'status' => $p['status'],
                ], $filhas),
            ];
        }
        usort($secoes, self::comparar(...));

        return array_map(static function (array $s): array {
            unset($s['ordem']);

            return $s;
        }, $secoes);
    }

    /**
     * Corpo Markdown da página (sem o cabeçalho).
     */
    public function markdown(string $slug): ?string
    {
        $pagina = $this->pagina($slug);
        if ($pagina === null) {
            return null;
        }
        if ($pagina['origem'] === 'banco') {
            return $this->dados()['banco'][$slug]['markdown'];
        }

        return Frontmatter::separar((string) file_get_contents($this->raiz . '/' . $pagina['arquivo']))[1];
    }

    /**
     * HTML e sumário da página, com cache.
     *
     * @return array{html: string, sumario: list<array{nivel: int, texto: string, ancora: string}>}|null
     */
    public function renderizada(string $slug): ?array
    {
        $pagina = $this->pagina($slug);
        if ($pagina === null) {
            return null;
        }

        $chave = 'documentacao_html_' . md5(implode('|', [$slug, $pagina['origem'], $pagina['status'], $pagina['atualizado_em'], $this->assinatura()]));
        $cache = service('cache');
        $guardada = $cache->get($chave);
        if (is_array($guardada) && isset($guardada['html'], $guardada['sumario'])) {
            return $guardada;
        }

        $this->renderizador ??= new RenderizadorDocumentacao(RenderizadorDocumentacao::URL_IMAGENS, [$this->raiz, self::pastaImagensEnviadas()]);
        $renderizada = $this->renderizador->renderizar((string) $this->markdown($slug), $slug);
        $cache->save($chave, $renderizada, self::TTL);

        return $renderizada;
    }

    /**
     * Muda quando qualquer arquivo é criado, removido ou alterado, quando o motor
     * muda de versão ou quando o cache é limpo.
     */
    public function assinatura(): string
    {
        if ($this->assinatura === null) {
            $partes = [RenderizadorDocumentacao::VERSAO, (string) service('cache')->get(self::CHAVE_VERSAO), $this->raiz];
            foreach ($this->arquivos() as $relativo) {
                $partes[] = $relativo . '@' . (int) @filemtime($this->raiz . '/' . $relativo);
            }
            $this->assinatura = md5(implode('|', $partes));
        }

        return $this->assinatura;
    }

    /** @return array{arquivos: array<string, array<string, mixed>>, banco: array<string, array<string, mixed>>, erros: array<string, array<string, string>>} */
    private function dados(): array
    {
        if ($this->dados !== null) {
            return $this->dados;
        }

        $chave = 'documentacao_catalogo_' . ($this->banco === false ? 'arq_' : '') . $this->assinatura();
        $guardado = service('cache')->get($chave);
        if (is_array($guardado) && isset($guardado['arquivos'], $guardado['banco'], $guardado['erros'])) {
            return $this->dados = $guardado;
        }

        [$banco, $bancoOk] = $this->lerBanco();
        $this->dados = $this->montar() + ['banco' => $banco];
        if ($bancoOk) {
            // Falha no banco não fica em cache: na próxima requisição tenta de novo.
            service('cache')->save($chave, $this->dados, self::TTL);
        }

        return $this->dados;
    }

    /** @return array{0: array<string, array<string, mixed>>, 1: bool} Páginas do banco por slug, e se a leitura deu certo. */
    private function lerBanco(): array
    {
        if ($this->banco === false) {
            return [[], true];
        }

        try {
            if (!$this->banco->disponivel()) {
                return [[], true]; // migration ainda não rodou: só arquivos, e isso pode ir para o cache
            }
            $linhas = $this->banco->todas();
        } catch (\Throwable $e) {
            log_message('error', 'Documentação: páginas do banco indisponíveis; seguindo só com os arquivos. ' . $e->getMessage());

            return [[], false];
        }

        $paginas = [];
        foreach ($linhas as $linha) {
            $slug = (string) $linha['slug'];
            $secao = explode('/', $slug, 2)[0];
            $paginas[$slug] = [
                'slug'           => $slug,
                'secao'          => $secao,
                'capa'           => !str_contains($slug, '/'),
                'titulo'         => trim((string) $linha['titulo']),
                'resumo'         => trim($linha['resumo']),
                'ordem'          => $linha['ordem'],
                'permissao'      => $linha['permissao'],
                'telas'          => $linha['telas'],
                'palavras_chave' => $linha['palavras_chave'],
                'texto'          => self::textoPuro($linha['markdown']),
                'arquivo'        => null,
                'atualizado_em'  => (int) strtotime((string) ($linha['updated_at'] ?? $linha['created_at'] ?? '')),
                'origem'         => 'banco',
                'status'         => $linha['status'] === 'publicado' ? 'publicado' : 'rascunho',
                'id'             => $linha['id'],
                'markdown'       => $linha['markdown'],
            ];
        }

        return [$paginas, true];
    }

    /** @return array{arquivos: array<string, array<string, mixed>>, erros: array<string, array<string, string>>} */
    private function montar(): array
    {
        $lidas = [];
        $erros = [];

        foreach ($this->arquivos() as $relativo) {
            [$secao, $arquivo] = explode('/', $relativo, 2);
            $nome = substr($arquivo, 0, -3);

            if (!preg_match(self::RE_NOME, $secao) || !preg_match(self::RE_NOME, $nome)) {
                $erros[$relativo] = ['arquivo' => 'Pasta e arquivo devem usar só minúsculas, números e hífen.'];
                continue;
            }

            try {
                [$cabecalho, $corpo] = Frontmatter::separar((string) file_get_contents($this->raiz . '/' . $relativo));
            } catch (FrontmatterInvalido $e) {
                $erros[$relativo] = $e->erros();
                continue;
            }
            $invalidos = Frontmatter::validar($cabecalho);
            if ($invalidos !== []) {
                $erros[$relativo] = $invalidos;
                continue;
            }

            $capa = $nome === 'index';
            $slug = $capa ? $secao : "{$secao}/{$nome}";
            $lidas[$slug] = [
                'slug'           => $slug,
                'secao'          => $secao,
                'capa'           => $capa,
                'titulo'         => trim($cabecalho['titulo']),
                'resumo'         => trim((string) ($cabecalho['resumo'] ?? '')),
                'ordem'          => $cabecalho['ordem'] ?? Frontmatter::ORDEM_PADRAO,
                'permissao'      => $cabecalho['permissao'] ?? null,
                'telas'          => $cabecalho['telas'] ?? [],
                'palavras_chave' => $cabecalho['palavras_chave'] ?? [],
                'texto'          => self::textoPuro($corpo),
                'arquivo'        => $relativo,
                'atualizado_em'  => (int) filemtime($this->raiz . '/' . $relativo),
                'origem'         => 'arquivo',
                'status'         => 'publicado',
                'id'             => null,
            ];
        }

        ksort($lidas);

        return ['arquivos' => $lidas, 'erros' => $erros];
    }

    /** @return list<string> Caminhos `secao/pagina.md`, relativos à raiz. */
    private function arquivos(): array
    {
        $arquivos = array_map(
            fn (string $caminho): string => substr($caminho, strlen($this->raiz) + 1),
            glob($this->raiz . '/*/*.md') ?: [],
        );
        sort($arquivos);

        return $arquivos;
    }

    /** Texto corrido do Markdown, para a busca. */
    public static function textoPuro(string $markdown): string
    {
        $texto = preg_replace([
            '/!\[([^\]]*)\]\([^)]*\)/u',   // imagens: fica o alt
            '/\[([^\]]*)\]\([^)]*\)/u',    // links: fica o texto
            '/^\s{0,3}>\s?\[![A-Z]+\]/mu', // marcadores de aviso
            '/^\s{0,3}(?:#{1,6}|>|[-+*]|\d+[.)])\s+/mu', // marcadores de título, citação e lista
            '/[`*_~|]+/u',
            '/\s+/u',
        ], ['$1', '$1', '', ' ', '', ' '], $markdown);

        return trim((string) $texto);
    }

    private static function tituloDaPasta(string $pasta): string
    {
        return mb_convert_case(str_replace('-', ' ', $pasta), MB_CASE_TITLE, 'UTF-8');
    }

    private static function comparar(array $a, array $b): int
    {
        static $collator = null;
        $collator ??= new \Collator('pt_BR');

        return $a['ordem'] <=> $b['ordem'] ?: (int) $collator->compare($a['titulo'], $b['titulo']);
    }
}
