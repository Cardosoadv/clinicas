<?php

declare(strict_types=1);

namespace App\Services\Documentacao;

/**
 * Leitura da documentação interna pela API (contrato em docs/api-documentacao.md).
 *
 * Toda consulta passa por `visivel()`: o usuário precisa ter a permissão que a
 * página declara (quando declara uma). Página invisível é tratada como
 * inexistente, para não revelar que existe.
 *
 * Quem tem `documentacao.editar` lê na visão com rascunhos: o rascunho de uma
 * página do banco aparece no lugar da versão publicada.
 */
class DocumentacaoService
{
    public const BUSCA_MINIMO = 2;
    public const BUSCA_LIMITE = 20;

    private const RE_IMAGEM = '#^[a-z0-9]+(?:-[a-z0-9]+)*/img/[a-z0-9]+(?:[._-][a-z0-9]+)*\.(?:png|jpe?g|gif|webp|avif)$#';

    /** @var callable(string): bool */
    private $pode;

    /**
     * @param (callable(string): bool)|null $pode Permissão do usuário atual (padrão: auth()->user()->can()).
     */
    public function __construct(
        private ?CatalogoDocumentacao $catalogo = null,
        ?callable $pode = null,
    ) {
        $this->catalogo ??= new CatalogoDocumentacao();
        $this->pode = $pode ?? static fn (string $permissao): bool => (bool) auth()->user()?->can($permissao);
    }

    private bool $visaoDefinida = false;

    /** Catálogo da visão do usuário: com rascunhos para quem edita. */
    private function catalogo(): CatalogoDocumentacao
    {
        if (!$this->visaoDefinida) {
            $this->visaoDefinida = true;
            if ($this->podeEditar()) {
                $this->catalogo = $this->catalogo->comRascunhos();
            }
        }

        return $this->catalogo;
    }

    public function podeEditar(): bool
    {
        return ($this->pode)('documentacao.editar');
    }

    public function visivel(array $pagina): bool
    {
        return $pagina['permissao'] === null || ($this->pode)($pagina['permissao']);
    }

    public function arvore(): array
    {
        return $this->catalogo()->arvore($this->visivel(...));
    }

    /**
     * Página completa (HTML e sumário) e as vizinhas na ordem do índice, ou null.
     */
    public function pagina(string $slug): ?array
    {
        $pagina = $this->catalogo()->pagina($slug);
        if ($pagina === null || !$this->visivel($pagina)) {
            return null;
        }

        $renderizada = $this->catalogo()->renderizada($slug);
        [$anterior, $proxima] = $this->vizinhas($slug);

        return [
            'slug'          => $pagina['slug'],
            'secao'         => $pagina['secao'],
            'capa'          => $pagina['capa'],
            'origem'        => $pagina['origem'],
            'status'        => $pagina['status'],
            'id'            => $pagina['id'],
            'titulo'        => $pagina['titulo'],
            'resumo'        => $pagina['resumo'],
            'html'          => $renderizada['html'],
            'sumario'       => $renderizada['sumario'],
            'anterior'      => $anterior,
            'proxima'       => $proxima,
            'atualizado_em' => date('c', $pagina['atualizado_em']),
        ];
    }

    /**
     * Primeira página visível (na ordem do índice) que declara a tela em `telas`.
     */
    public function porTela(string $tela): ?array
    {
        foreach ($this->sequencia() as $item) {
            $pagina = $this->catalogo()->pagina($item['slug']);
            if (in_array($tela, $pagina['telas'], true)) {
                return $this->pagina($item['slug']);
            }
        }

        return null;
    }

    /**
     * Busca por todos os termos (sem diferenciar acentos e maiúsculas). Relevância:
     * título > palavras-chave > resumo > corpo.
     *
     * @return list<array{slug: string, titulo: string, secao: string, trecho: string}>
     */
    public function buscar(string $consulta): array
    {
        $termos = array_values(array_unique(array_filter(
            preg_split('/\s+/u', self::normalizar($consulta)) ?: [],
            static fn (string $t): bool => mb_strlen($t) >= self::BUSCA_MINIMO,
        )));
        if ($termos === []) {
            return [];
        }

        $titulosDasSecoes = array_column($this->arvore(), 'titulo', 'slug');
        $resultados = [];

        foreach ($this->catalogo()->paginas() as $pagina) {
            if (!$this->visivel($pagina)) {
                continue;
            }

            $campos = [
                10 => self::normalizar($pagina['titulo']),
                5  => self::normalizar(implode(' ', $pagina['palavras_chave'])),
                3  => self::normalizar($pagina['resumo']),
                1  => self::normalizar($pagina['texto']),
            ];

            $pontos = 0;
            foreach ($termos as $termo) {
                $doTermo = 0;
                foreach ($campos as $peso => $texto) {
                    $doTermo += $peso * substr_count($texto, $termo);
                }
                if ($doTermo === 0) {
                    continue 2; // todos os termos precisam aparecer
                }
                $pontos += $doTermo;
            }

            $resultados[] = [
                'pontos' => $pontos,
                'slug'   => $pagina['slug'],
                'titulo' => $pagina['titulo'],
                'secao'  => $titulosDasSecoes[$pagina['secao']] ?? $pagina['secao'],
                'trecho' => self::trecho($pagina['resumo'] !== '' && !self::contem($pagina['texto'], $termos) ? $pagina['resumo'] : $pagina['texto'], $termos),
            ];
        }

        usort($resultados, static fn (array $a, array $b): int => $b['pontos'] <=> $a['pontos'] ?: strcmp($a['slug'], $b['slug']));

        return array_map(static function (array $r): array {
            unset($r['pontos']);

            return $r;
        }, array_slice($resultados, 0, self::BUSCA_LIMITE));
    }

    /**
     * Caminho absoluto de uma imagem da documentação (`<secao>/img/<arquivo>`), ou null.
     */
    public function caminhoDaImagem(string $relativo, string|array|null $raizes = null): ?string
    {
        if (!preg_match(self::RE_IMAGEM, $relativo)) {
            return null;
        }

        // app/Docs primeiro; depois as imagens enviadas pela interface.
        foreach ((array) ($raizes ?? [CatalogoDocumentacao::raizPadrao(), CatalogoDocumentacao::pastaImagensEnviadas()]) as $raiz) {
            $raiz = realpath($raiz);
            $caminho = $raiz === false ? false : realpath($raiz . '/' . $relativo);
            if ($caminho !== false && str_starts_with($caminho, $raiz . DIRECTORY_SEPARATOR) && is_file($caminho)) {
                return $caminho;
            }
        }

        return null;
    }

    /**
     * Páginas visíveis na ordem de leitura: capa de cada seção e depois as páginas dela.
     *
     * @return list<array{slug: string, titulo: string}>
     */
    private function sequencia(): array
    {
        $ordem = [];
        foreach ($this->arvore() as $secao) {
            if ($secao['capa']) {
                $ordem[] = ['slug' => $secao['slug'], 'titulo' => $secao['titulo']];
            }
            foreach ($secao['paginas'] as $pagina) {
                $ordem[] = ['slug' => $pagina['slug'], 'titulo' => $pagina['titulo']];
            }
        }

        return $ordem;
    }

    /** @return array{0: ?array{slug: string, titulo: string}, 1: ?array{slug: string, titulo: string}} */
    private function vizinhas(string $slug): array
    {
        $ordem = $this->sequencia();
        $i = array_search($slug, array_column($ordem, 'slug'), true);
        if ($i === false) {
            return [null, null];
        }

        return [$ordem[$i - 1] ?? null, $ordem[$i + 1] ?? null];
    }

    /** Minúsculas e sem acentos. */
    public static function normalizar(string $texto): string
    {
        $t = \Transliterator::create('Any-Latin; Latin-ASCII; Lower()')?->transliterate($texto) ?? mb_strtolower($texto);

        return trim(preg_replace('/\s+/u', ' ', $t) ?? '');
    }

    /** @param list<string> $termos */
    private static function contem(string $texto, array $termos): bool
    {
        $normalizado = self::normalizar($texto);
        foreach ($termos as $termo) {
            if (str_contains($normalizado, $termo)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Trecho de até ~160 caracteres em volta da primeira ocorrência de um termo.
     * A normalização preserva o comprimento só para ASCII, então a posição é
     * procurada no texto original, sem acentos, caractere a caractere.
     *
     * @param list<string> $termos
     */
    private static function trecho(string $texto, array $termos): string
    {
        $limite = 160;
        $caracteres = mb_str_split($texto);
        $normalizados = array_map(static fn (string $c): string => self::normalizar($c) ?: ' ', $caracteres);
        $plano = implode('', array_map(static fn (string $c): string => mb_substr($c, 0, 1), $normalizados));

        $posicao = null;
        foreach ($termos as $termo) {
            $p = mb_strpos($plano, $termo);
            if ($p !== false && ($posicao === null || $p < $posicao)) {
                $posicao = $p;
            }
        }

        $inicio = max(0, ($posicao ?? 0) - 40);
        $trecho = trim(mb_substr($texto, $inicio, $limite));

        return ($inicio > 0 ? '…' : '') . $trecho . ($inicio + $limite < count($caracteres) ? '…' : '');
    }
}
