<?php

declare(strict_types=1);

namespace App\Services\Documentacao;

use App\Libraries\Documentacao\ErroDeValidacao;
use App\Libraries\Documentacao\Frontmatter;
use App\Libraries\Documentacao\NaoEncontrado;
use App\Libraries\Documentacao\RenderizadorDocumentacao;
use App\Models\DocumentacaoPaginaModel;
use App\Repositories\DocumentacaoPaginaRepository;

/**
 * Edição das páginas da documentação pela interface (permissão `documentacao.editar`).
 *
 * As páginas criadas aqui ficam no banco e seguem o mesmo formato e as mesmas
 * regras de validação das páginas em arquivo (Frontmatter::validar). Toda
 * gravação limpa o cache do catálogo e registra quem criou/alterou.
 */
class EdicaoDocumentacaoService
{
    public const MAX_MARKDOWN = 200_000;
    public const MAX_IMAGEM   = 5 * 1024 * 1024;

    private const RE_SLUG = '#^[a-z0-9]+(?:-[a-z0-9]+)*(?:/[a-z0-9]+(?:-[a-z0-9]+)*)?$#';
    private const TIPOS_IMAGEM = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];

    private string $pastaImagens;

    public function __construct(
        private ?DocumentacaoPaginaRepository $repositorio = null,
        private ?CatalogoDocumentacao $catalogo = null,
        ?string $pastaImagens = null,
    ) {
        $this->repositorio ??= new DocumentacaoPaginaRepository();
        $this->catalogo    ??= new CatalogoDocumentacao(null, null, $this->repositorio);
        $this->pastaImagens = rtrim($pastaImagens ?? CatalogoDocumentacao::pastaImagensEnviadas(), '/');
    }

    /**
     * Todas as páginas, das duas fontes: as do banco (com status) e as de arquivo
     * que ainda não têm cópia no banco.
     *
     * @return list<array<string, mixed>>
     */
    public function listar(): array
    {
        ['arquivos' => $arquivos, 'banco' => $banco] = $this->catalogo->comRascunhos()->fontes();

        $lista = [];
        foreach ($banco as $slug => $p) {
            $lista[$slug] = [
                'id'            => $p['id'],
                'slug'          => $slug,
                'titulo'        => $p['titulo'],
                'secao'         => $p['secao'],
                'origem'        => 'banco',
                'status'        => $p['status'],
                'tem_arquivo'   => isset($arquivos[$slug]),
                'atualizado_em' => $p['atualizado_em'] ? date('c', $p['atualizado_em']) : null,
            ];
        }
        foreach ($arquivos as $slug => $p) {
            $lista[$slug] ??= [
                'id'            => null,
                'slug'          => $slug,
                'titulo'        => $p['titulo'],
                'secao'         => $p['secao'],
                'origem'        => 'arquivo',
                'status'        => 'publicado',
                'tem_arquivo'   => true,
                'atualizado_em' => date('c', $p['atualizado_em']),
            ];
        }
        ksort($lista);

        return array_values($lista);
    }

    public function obter(int $id): array
    {
        return $this->apresentar($this->linha($id));
    }

    /**
     * @param array<string, mixed> $entrada
     *
     * @return array{data: array<string, mixed>, avisos: list<string>}
     */
    public function criar(array $entrada): array
    {
        $dados = $this->validar($entrada);
        $dados += ['status' => 'rascunho', 'criado_por' => self::usuario(), 'atualizado_por' => self::usuario()];

        $id = $this->model()->insert($dados, true);
        if ($id === false) {
            throw new ErroDeValidacao($this->model()->errors());
        }
        CatalogoDocumentacao::limparCache();

        return $this->resultado((int) $id);
    }

    /**
     * Substitui os dados da página (o status não muda aqui: use publicar/despublicar).
     *
     * @param array<string, mixed> $entrada
     *
     * @return array{data: array<string, mixed>, avisos: list<string>}
     */
    public function atualizar(int $id, array $entrada): array
    {
        $this->linha($id);
        // `id` nos dados: a regra is_unique do model ignora o próprio registro.
        $dados = $this->validar($entrada, $id) + ['id' => $id, 'atualizado_por' => self::usuario()];

        if (!$this->model()->update($id, $dados)) {
            throw new ErroDeValidacao($this->model()->errors());
        }
        CatalogoDocumentacao::limparCache();

        return $this->resultado($id);
    }

    public function publicar(int $id): array
    {
        return $this->mudarStatus($id, 'publicado');
    }

    public function despublicar(int $id): array
    {
        return $this->mudarStatus($id, 'rascunho');
    }

    public function excluir(int $id): void
    {
        $this->linha($id);
        $this->model()->delete($id);
        CatalogoDocumentacao::limparCache();
    }

    /**
     * Copia uma página de arquivo para o banco, como rascunho, para editar.
     *
     * @return array{data: array<string, mixed>, avisos: list<string>}
     */
    public function copiarDoArquivo(string $slug): array
    {
        ['arquivos' => $arquivos, 'banco' => $banco] = $this->catalogo->fontes();
        $arquivo = $arquivos[$slug] ?? null;
        if ($arquivo === null) {
            throw new NaoEncontrado('Não há página de arquivo com este endereço.');
        }
        if (isset($banco[$slug])) {
            throw ErroDeValidacao::campo('slug', 'Esta página já tem uma cópia editável.');
        }

        return $this->criar([
            'slug'           => $slug,
            'titulo'         => $arquivo['titulo'],
            'resumo'         => $arquivo['resumo'],
            'ordem'          => $arquivo['ordem'],
            // permissao do próprio arquivo (sem a herança da capa)
            'permissao'      => $this->cabecalhoDoArquivo($arquivo)['permissao'] ?? null,
            'telas'          => $arquivo['telas'],
            'palavras_chave' => $arquivo['palavras_chave'],
            'markdown'       => (string) $this->catalogo->markdown($slug),
        ]);
    }

    /**
     * HTML de uma página ainda não salva, com os avisos do motor.
     *
     * @return array{html: string, sumario: list<array<string, mixed>>, avisos: list<string>}
     */
    public function preview(string $markdown, string $slug): array
    {
        if (mb_strlen($markdown) > self::MAX_MARKDOWN) {
            throw ErroDeValidacao::campo('markdown', 'O texto passa do tamanho máximo.');
        }
        $slug = preg_match(self::RE_SLUG, $slug) ? $slug : 'rascunho';

        return $this->renderizador()->renderizar($markdown, $slug);
    }

    /**
     * Guarda uma imagem para a seção e devolve o caminho a usar no Markdown.
     * Imagem mais larga que 1600 px é reduzida.
     *
     * @return array{caminho: string, markdown: string, largura: int, altura: int}
     */
    public function enviarImagem(string $caminhoTemporario, string $nomeOriginal, string $secao): array
    {
        if (!preg_match('#^[a-z0-9]+(?:-[a-z0-9]+)*$#', $secao)) {
            throw ErroDeValidacao::campo('secao', 'Informe a seção da página (minúsculas, números e hífen).');
        }
        $tamanho = (int) @filesize($caminhoTemporario);
        if ($tamanho <= 0 || $tamanho > self::MAX_IMAGEM) {
            throw ErroDeValidacao::campo('arquivo', 'Envie uma imagem de até 5 MB.');
        }
        $info = @getimagesize($caminhoTemporario);
        $extensao = $info !== false ? (self::TIPOS_IMAGEM[$info[2]] ?? null) : null;
        if ($extensao === null) {
            throw ErroDeValidacao::campo('arquivo', 'Formato não aceito. Use PNG, JPG, GIF ou WebP.');
        }

        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(pathinfo(
            \Transliterator::create('Any-Latin; Latin-ASCII')?->transliterate($nomeOriginal) ?? $nomeOriginal,
            PATHINFO_FILENAME,
        ))), '-') ?: 'imagem';
        $nome = substr($base, 0, 60) . '-' . bin2hex(random_bytes(3)) . '.' . $extensao;
        $relativo = "{$secao}/img/{$nome}";
        $destino = $this->pastaImagens . '/' . $relativo;
        if (!is_dir(dirname($destino)) && !@mkdir(dirname($destino), 0755, true) && !is_dir(dirname($destino))) {
            throw new \RuntimeException('Não foi possível criar a pasta das imagens.');
        }

        [$largura, $altura] = [(int) $info[0], (int) $info[1]];
        if ($largura > RenderizadorDocumentacao::LARGURA_MAXIMA_IMAGEM) {
            $altura = (int) round($altura * RenderizadorDocumentacao::LARGURA_MAXIMA_IMAGEM / $largura);
            $largura = RenderizadorDocumentacao::LARGURA_MAXIMA_IMAGEM;
            \Config\Services::image('gd', null, false)->withFile($caminhoTemporario)->resize($largura, $altura, false)->save($destino);
        } elseif (!@copy($caminhoTemporario, $destino)) {
            throw new \RuntimeException('Não foi possível gravar a imagem.');
        }
        @chmod($destino, 0644);

        return ['caminho' => $relativo, 'markdown' => "![Descreva a imagem](img/{$nome})", 'largura' => $largura, 'altura' => $altura];
    }

    /**
     * @param array<string, mixed> $entrada
     *
     * @return array<string, mixed> Colunas a gravar.
     */
    private function validar(array $entrada, ?int $id = null): array
    {
        $erros = [];

        $slug = strtolower(trim((string) ($entrada['slug'] ?? '')));
        if (!preg_match(self::RE_SLUG, $slug) || mb_strlen($slug) > 160) {
            $erros['slug'] = 'Endereço no formato "secao" ou "secao/pagina" (minúsculas, números e hífen).';
        } elseif (str_ends_with($slug, '/index')) {
            $erros['slug'] = 'Para a capa da seção, use só o nome da seção.';
        }

        $cabecalho = ['titulo' => trim((string) ($entrada['titulo'] ?? ''))];
        foreach (['resumo', 'permissao'] as $campo) {
            $valor = trim((string) ($entrada[$campo] ?? ''));
            if ($valor !== '') {
                $cabecalho[$campo] = $valor;
            }
        }
        if (isset($entrada['ordem']) && $entrada['ordem'] !== '' && $entrada['ordem'] !== null) {
            $cabecalho['ordem'] = filter_var($entrada['ordem'], FILTER_VALIDATE_INT) === false ? $entrada['ordem'] : (int) $entrada['ordem'];
        }
        foreach (['telas', 'palavras_chave'] as $lista) {
            $valor = $entrada[$lista] ?? [];
            $cabecalho[$lista] = is_array($valor)
                ? array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $valor), static fn ($v) => $v !== ''))
                : $valor;
        }
        $erros += Frontmatter::validar($cabecalho);

        $markdown = $entrada['markdown'] ?? '';
        if (!is_string($markdown)) {
            $erros['markdown'] = 'Texto inválido.';
        } elseif (mb_strlen($markdown) > self::MAX_MARKDOWN) {
            $erros['markdown'] = 'O texto passa do tamanho máximo.';
        }

        if (!isset($erros['slug']) && ($existente = $this->repositorio->porSlug($slug)) && $existente['id'] !== $id) {
            $erros['slug'] = 'Já existe uma página com este endereço.';
        }

        if ($erros !== []) {
            throw new ErroDeValidacao($erros);
        }

        return [
            'slug'           => $slug,
            'titulo'         => $cabecalho['titulo'],
            'resumo'         => $cabecalho['resumo'] ?? null,
            'ordem'          => $cabecalho['ordem'] ?? Frontmatter::ORDEM_PADRAO,
            'permissao'      => $cabecalho['permissao'] ?? null,
            'telas'          => json_encode($cabecalho['telas'], JSON_UNESCAPED_UNICODE),
            'palavras_chave' => json_encode($cabecalho['palavras_chave'], JSON_UNESCAPED_UNICODE),
            'markdown'       => $markdown,
        ];
    }

    /** Avisos que não impedem salvar: o que o motor descartou e telas já usadas por outra página. */
    private function avisos(array $linha): array
    {
        $avisos = $this->renderizador()->renderizar($linha['markdown'], $linha['slug'])['avisos'];

        foreach ($this->catalogo->comRascunhos()->paginas() as $pagina) {
            if ($pagina['slug'] === $linha['slug']) {
                continue;
            }
            foreach (array_intersect($linha['telas'], $pagina['telas']) as $tela) {
                $avisos[] = "A tela '{$tela}' também abre a página '{$pagina['slug']}' na ajuda contextual.";
            }
        }

        return $avisos;
    }

    /** @return array{data: array<string, mixed>, avisos: list<string>} */
    private function resultado(int $id): array
    {
        $linha = $this->linha($id);

        return ['data' => $this->apresentar($linha), 'avisos' => $this->avisos($linha)];
    }

    private function mudarStatus(int $id, string $status): array
    {
        $this->linha($id);
        $this->model()->update($id, [
            'id'             => $id,
            'status'         => $status,
            'publicado_em'   => $status === 'publicado' ? date('Y-m-d H:i:s') : null,
            'atualizado_por' => self::usuario(),
        ]);
        CatalogoDocumentacao::limparCache();

        return $this->apresentar($this->linha($id));
    }

    private function linha(int $id): array
    {
        return $this->repositorio->porId($id) ?? throw new NaoEncontrado('Página não encontrada.');
    }

    /** @return array<string, mixed> */
    private function apresentar(array $linha): array
    {
        $arquivos = $this->catalogo->fontes()['arquivos'];

        return [
            'id'             => $linha['id'],
            'slug'           => $linha['slug'],
            'titulo'         => $linha['titulo'],
            'resumo'         => $linha['resumo'],
            'ordem'          => $linha['ordem'],
            'permissao'      => $linha['permissao'],
            'telas'          => $linha['telas'],
            'palavras_chave' => $linha['palavras_chave'],
            'markdown'       => $linha['markdown'],
            'status'         => $linha['status'],
            'tem_arquivo'    => isset($arquivos[$linha['slug']]),
            'publicado_em'   => $linha['publicado_em'] ?? null,
            'criado_em'      => $linha['created_at'] ?? null,
            'atualizado_em'  => $linha['updated_at'] ?? null,
            'criado_por'     => isset($linha['criado_por']) ? (int) $linha['criado_por'] : null,
            'atualizado_por' => isset($linha['atualizado_por']) ? (int) $linha['atualizado_por'] : null,
        ];
    }

    /** Cabeçalho do arquivo, sem a herança da capa aplicada pelo catálogo. */
    private function cabecalhoDoArquivo(array $arquivo): array
    {
        return Frontmatter::separar((string) file_get_contents($this->catalogo->raiz() . '/' . $arquivo['arquivo']))[0];
    }

    private function renderizador(): RenderizadorDocumentacao
    {
        return new RenderizadorDocumentacao(
            RenderizadorDocumentacao::URL_IMAGENS,
            [$this->catalogo->raiz(), $this->pastaImagens],
        );
    }

    private function model(): DocumentacaoPaginaModel
    {
        return $this->repositorio->model();
    }

    private static function usuario(): ?int
    {
        try {
            $id = function_exists('user_id') ? user_id() : null;
        } catch (\Throwable) {
            $id = null;
        }

        return $id !== null ? (int) $id : null;
    }
}
