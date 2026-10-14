<?php

declare(strict_types=1);

namespace App\Libraries\Documentacao;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Converte o corpo Markdown de uma página da documentação em HTML pronto para
 * o frontend React. Formato em docs/documentacao-interna.md.
 *
 * 1. CommonMark + GFM, com HTML cru escapado e links inseguros descartados.
 * 2. Pós-processamento no DOM: âncoras e sumário dos títulos, links `.md`
 *    reescritos para `documentacao/{slug}`, links de tela (`/agenda`) para o
 *    caminho relativo (`agenda`), imagens para a rota da API, avisos
 *    `> [!NOTA]` em `<aside>`.
 * 3. Lista branca de tags e atributos sobre o resultado final: o que escapar
 *    das etapas anteriores não chega ao frontend (que usa `dangerouslySetInnerHTML`).
 *
 * Os links internos saem **relativos** (sem barra inicial): o `<base href>` do
 * index.html resolve o subcaminho de deploy (`/clinicas/`), e o frontend
 * intercepta o clique para navegar sem recarregar.
 */
final class RenderizadorDocumentacao
{
    /** Muda quando a saída muda, para invalidar o HTML em cache. */
    public const VERSAO = 1;

    /** Prefixo dos links para outras páginas da documentação (rota do frontend, relativa). */
    public const ROTA_SPA = 'documentacao/';

    /** Prefixo padrão das imagens: relativo à URL da API; o frontend completa com a base da API. */
    public const URL_IMAGENS = 'documentacao/imagens/';

    public const AVISOS = ['NOTA' => 'Nota', 'DICA' => 'Dica', 'ATENCAO' => 'Atenção'];

    /** Link para uma tela do sistema: caminho do React Router com barra inicial, sem `//` nem `:`. */
    private const RE_TELA = '#^/(?:[a-z0-9_-]+(?:/[a-z0-9_-]+)*)?/?(?:\?[a-z0-9_=&%.-]*)?(?:\#[a-z0-9_-]*)?$#i';

    /** href relativo gerado por este motor (tela ou página da documentação). */
    private const RE_RELATIVO = '#^(?:\./|[a-z0-9_-]+(?:/[a-z0-9_-]+)*/?(?:\?[a-z0-9_=&%.-]*)?(?:\#[^\s"<>]*)?)$#i';

    /** Caminho aceito para imagem, relativo a app/Docs. */
    public const RE_IMAGEM = '#^[a-z0-9-]+/img/[a-z0-9._-]+\.(?:png|jpe?g|gif|webp|avif)$#i';

    /** tag => atributos permitidos */
    private const PERMITIDOS = [
        'p' => ['class'], 'br' => [], 'hr' => [],
        'h1' => ['id'], 'h2' => ['id'], 'h3' => ['id'], 'h4' => ['id'], 'h5' => ['id'], 'h6' => ['id'],
        'strong' => [], 'em' => [], 'del' => [], 'code' => ['class'], 'pre' => [],
        'blockquote' => [], 'aside' => ['class', 'role'],
        'ul' => ['class'], 'ol' => ['start'], 'li' => ['class'],
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'loading', 'width', 'height'],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['align'], 'td' => ['align'],
        'input' => ['type', 'checked', 'disabled'],
    ];

    /** Tags removidas com o conteúdo; as demais fora da lista perdem só a tag. */
    private const DESCARTA = ['script', 'style', 'iframe', 'object', 'embed', 'template', 'svg', 'math', 'form', 'textarea', 'select', 'button'];

    private MarkdownConverter $conversor;

    /** Largura máxima das imagens da documentação (docs/documentacao-formato.md). */
    public const LARGURA_MAXIMA_IMAGEM = 1600;

    /**
     * @param string      $urlImagens  Prefixo das imagens (rota da API), com barra final.
     *                                  Padrão: URL_IMAGENS, relativo à base da API.
     * @param string|list<string>|null $raizArquivos Pastas onde procurar as imagens (app/Docs e as
     *                                  enviadas pela interface), para ler largura e altura
     *                                  (`width`/`height` evitam salto de layout ao carregar).
     */
    public function __construct(private string $urlImagens = self::URL_IMAGENS, private string|array|null $raizArquivos = null)
    {
        $ambiente = new Environment([
            'html_input'         => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level'  => 50,
        ]);
        $ambiente->addExtension(new CommonMarkCoreExtension());
        $ambiente->addExtension(new GithubFlavoredMarkdownExtension());

        $this->conversor = new MarkdownConverter($ambiente);
    }

    /**
     * @param string $slug Slug da página (`agenda/agendamento`), base dos links relativos.
     *
     * `avisos` lista o que foi descartado (link ou imagem fora do formato, imagem
     * sem texto alternativo); a página continua válida, e `docs:verificar` acusa.
     *
     * @return array{html: string, sumario: list<array{nivel: int, texto: string, ancora: string}>, avisos: list<string>}
     */
    public function renderizar(string $markdown, string $slug): array
    {
        $html = trim((string) $this->conversor->convert($markdown));
        if ($html === '') {
            return ['html' => '', 'sumario' => [], 'avisos' => []];
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $anterior = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><body><div id="raiz">' . $html . '</div></body>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        $raiz = $doc->getElementById('raiz');
        $raiz->removeAttribute('id');
        $xpath = new DOMXPath($doc);

        $this->avisos($doc, $xpath, $raiz);
        $sumario = $this->ancoras($xpath, $raiz);
        $avisos = [];
        $this->links($xpath, $raiz, $slug, $avisos);
        $this->imagens($xpath, $raiz, $slug, $avisos);
        $this->limpar($raiz);

        $saida = '';
        foreach ($raiz->childNodes as $filho) {
            $saida .= $doc->saveHTML($filho);
        }

        return ['html' => trim($saida), 'sumario' => $sumario, 'avisos' => $avisos];
    }

    /** `> [!NOTA]` vira `<aside class="doc-aviso doc-aviso--nota" role="note">`. */
    private function avisos(DOMDocument $doc, DOMXPath $xpath, DOMElement $raiz): void
    {
        foreach (iterator_to_array($xpath->query('.//blockquote', $raiz)) as $citacao) {
            $primeiro = $xpath->query('./p[1]', $citacao)->item(0);
            if (!$primeiro instanceof DOMElement || !$primeiro->firstChild instanceof \DOMText) {
                continue;
            }
            if (!preg_match('/^\[!(NOTA|DICA|ATENCAO)\][ \t]*\n?/u', $primeiro->firstChild->data, $m)) {
                continue;
            }

            $tipo = $m[1];
            $primeiro->firstChild->data = substr($primeiro->firstChild->data, strlen($m[0]));
            if ($primeiro->firstChild->data === '' && $primeiro->firstChild->nextSibling instanceof DOMElement
                && $primeiro->firstChild->nextSibling->tagName === 'br') {
                $primeiro->removeChild($primeiro->firstChild->nextSibling);
            }
            if (trim($primeiro->textContent) === '' && $primeiro->childNodes->length <= 1) {
                $citacao->removeChild($primeiro);
            }

            $aside = $doc->createElement('aside');
            $aside->setAttribute('class', 'doc-aviso doc-aviso--' . strtolower($tipo));
            $aside->setAttribute('role', 'note');
            $titulo = $doc->createElement('p');
            $titulo->setAttribute('class', 'doc-aviso-titulo');
            $titulo->appendChild($doc->createTextNode(self::AVISOS[$tipo]));
            $aside->appendChild($titulo);
            while ($citacao->firstChild) {
                $aside->appendChild($citacao->firstChild);
            }
            $citacao->parentNode->replaceChild($aside, $citacao);
        }
    }

    /**
     * Dá `id` estável a todos os títulos e devolve o sumário (h2 e h3).
     *
     * @return list<array{nivel: int, texto: string, ancora: string}>
     */
    private function ancoras(DOMXPath $xpath, DOMElement $raiz): array
    {
        $usadas = [];
        $sumario = [];

        foreach ($xpath->query('.//h1|.//h2|.//h3|.//h4|.//h5|.//h6', $raiz) as $titulo) {
            $texto = trim(preg_replace('/\s+/u', ' ', $titulo->textContent) ?? '');
            $base = self::slugificar($texto) ?: 'secao';
            $ancora = $base;
            for ($n = 2; isset($usadas[$ancora]); $n++) {
                $ancora = "{$base}-{$n}";
            }
            $usadas[$ancora] = true;
            $titulo->setAttribute('id', $ancora);

            $nivel = (int) substr($titulo->tagName, 1);
            if ($nivel === 2 || $nivel === 3) {
                $sumario[] = ['nivel' => $nivel, 'texto' => $texto, 'ancora' => $ancora];
            }
        }

        return $sumario;
    }

    /** @param list<string> $avisos */
    private function links(DOMXPath $xpath, DOMElement $raiz, string $slug, array &$avisos): void
    {
        foreach ($xpath->query('.//a[@href]', $raiz) as $a) {
            $href = trim($a->getAttribute('href'));
            $destino = self::destinoDoLink($href, $slug);

            if ($destino === null) {
                $avisos[] = "Link recusado: {$href}";
                $a->removeAttribute('href');
                continue;
            }

            $a->setAttribute('href', $destino);
            if (str_starts_with($destino, 'http')) {
                $a->setAttribute('target', '_blank');
                $a->setAttribute('rel', 'noopener noreferrer');
            }
        }
    }

    /**
     * Destino final de um link do corpo, ou null quando não é aceito.
     *
     * - `outra.md`, `../secao/pagina.md#ancora` → `documentacao/secao/pagina#ancora`
     * - `#ancora` → `documentacao/{slug atual}#ancora`
     * - `/agenda`, `/clientes?status=ativo` (tela do sistema) → `agenda`, `clientes?status=ativo`; `/` → `./`
     * - `https://`, `http://`, `mailto:` → sem mudança
     */
    public static function destinoDoLink(string $href, string $slug): ?string
    {
        if (preg_match(self::RE_TELA, $href)) {
            return substr($href, 1) ?: './';
        }
        if (str_starts_with($href, '#')) {
            return self::ROTA_SPA . $slug . $href;
        }
        if (preg_match('#^(?:https?:|mailto:)#i', $href)) {
            return $href;
        }
        if (preg_match('#^([^:?\#]+)\.md(\#[^\s]*)?$#', $href, $m)) {
            $alvo = self::resolver(self::pastaDoSlug($slug), $m[1]);
            if ($alvo === null) {
                return null;
            }
            $alvo = preg_replace('#/index$#', '', $alvo);

            return self::ROTA_SPA . $alvo . ($m[2] ?? '');
        }

        return null;
    }

    /** @param list<string> $avisos */
    private function imagens(DOMXPath $xpath, DOMElement $raiz, string $slug, array &$avisos): void
    {
        foreach (iterator_to_array($xpath->query('.//img', $raiz)) as $img) {
            $src = trim($img->getAttribute('src'));
            $caminho = preg_match('#^[a-z0-9./_-]+$#i', $src) ? self::resolver(self::pastaDoSlug($slug), $src) : null;

            if ($caminho === null || !preg_match(self::RE_IMAGEM, $caminho)) {
                // Imagem fora de `<secao>/img/` não é servida: fica só o texto alternativo.
                $avisos[] = "Imagem recusada (use <secao>/img/arquivo.webp): {$src}";
                $img->parentNode->replaceChild($img->ownerDocument->createTextNode($img->getAttribute('alt')), $img);
                continue;
            }
            if (trim($img->getAttribute('alt')) === '') {
                $avisos[] = "Imagem sem texto alternativo: {$src}";
            }

            $img->setAttribute('src', $this->urlImagens . $caminho);
            $img->setAttribute('loading', 'lazy');

            $dimensoes = $this->dimensoes($caminho);
            if ($dimensoes !== null) {
                [$largura, $altura] = $dimensoes;
                $img->setAttribute('width', (string) $largura);
                $img->setAttribute('height', (string) $altura);
                if ($largura > self::LARGURA_MAXIMA_IMAGEM) {
                    $avisos[] = "Imagem com {$largura} px de largura (máximo " . self::LARGURA_MAXIMA_IMAGEM . "): {$src}";
                }
            }
        }
    }

    /** @return array{0: int, 1: int}|null Largura e altura do arquivo, quando ele existe e é uma imagem. */
    private function dimensoes(string $caminho): ?array
    {
        foreach ((array) $this->raizArquivos as $raiz) {
            $arquivo = rtrim($raiz, '/') . '/' . $caminho;
            $info = is_file($arquivo) ? @getimagesize($arquivo) : false;
            if ($info !== false && $info[0] > 0 && $info[1] > 0) {
                return [(int) $info[0], (int) $info[1]];
            }
        }

        return null;
    }

    /** Pasta (seção) de uma página: `processos/cadastro` e `processos` (capa) → `processos`. */
    private static function pastaDoSlug(string $slug): string
    {
        return explode('/', $slug, 2)[0];
    }

    /** Junta um caminho relativo à pasta, resolvendo `.` e `..`; null se sair de app/Docs. */
    private static function resolver(string $pasta, string $relativo): ?string
    {
        $partes = $pasta === '' ? [] : [$pasta];
        foreach (explode('/', $relativo) as $parte) {
            if ($parte === '' || $parte === '.') {
                continue;
            }
            if ($parte === '..') {
                if ($partes === []) {
                    return null;
                }
                array_pop($partes);
                continue;
            }
            $partes[] = $parte;
        }

        return $partes === [] ? null : implode('/', $partes);
    }

    private function limpar(DOMNode $no): void
    {
        foreach (iterator_to_array($no->childNodes) as $filho) {
            if ($filho instanceof \DOMText) {
                continue;
            }
            if (!$filho instanceof DOMElement) {
                $no->removeChild($filho); // comentários, instruções de processamento
                continue;
            }

            $tag = strtolower($filho->tagName);
            if (in_array($tag, self::DESCARTA, true)) {
                $no->removeChild($filho);
                continue;
            }

            $this->limpar($filho);

            if (!array_key_exists($tag, self::PERMITIDOS)) {
                while ($filho->firstChild) {
                    $no->insertBefore($filho->firstChild, $filho);
                }
                $no->removeChild($filho);
                continue;
            }

            foreach (iterator_to_array($filho->attributes) as $atributo) {
                if (!in_array($atributo->name, self::PERMITIDOS[$tag], true) || !self::valorSeguro($tag, $atributo->name, $atributo->value)) {
                    $filho->removeAttribute($atributo->name);
                }
            }
            if ($tag === 'input' && $filho->getAttribute('type') !== 'checkbox') {
                $no->removeChild($filho);
            }
        }
    }

    private static function valorSeguro(string $tag, string $atributo, string $valor): bool
    {
        return match ($atributo) {
            'href'   => (bool) preg_match('#^(?:https?://|mailto:)#i', $valor) || (bool) preg_match(self::RE_RELATIVO, $valor),
            'src'    => (bool) preg_match('#^(?:https?://|/)#i', $valor) || str_starts_with($valor, self::URL_IMAGENS),
            'class'  => (bool) preg_match('/^[a-z0-9 _-]+$/i', $valor),
            'target' => $valor === '_blank',
            'align'  => in_array($valor, ['left', 'center', 'right'], true),
            'type'   => $valor === 'checkbox',
            'start', 'width', 'height' => ctype_digit($valor),
            default  => true,
        };
    }

    /** `Cadastro de Pacientes (Pets)` → `cadastro-de-pacientes-pets` */
    public static function slugificar(string $texto): string
    {
        $ascii = \Transliterator::create('Any-Latin; Latin-ASCII; Lower()')?->transliterate($texto) ?? strtolower($texto);

        return trim(preg_replace('/[^a-z0-9]+/', '-', $ascii) ?? '', '-');
    }
}
