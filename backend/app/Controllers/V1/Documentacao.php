<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Services\Documentacao\DocumentacaoService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Leitura da documentação interna (manual do sistema). Contrato em docs/api-documentacao.md.
 *
 * Rotas atrás do `apiauth`: basta estar logado. Página que exige uma permissão
 * que o usuário não tem responde 404, como se não existisse.
 */
class Documentacao extends BaseController
{
    public function __construct(private ?DocumentacaoService $service = null)
    {
    }

    private function service(): DocumentacaoService
    {
        return $this->service ??= service('documentacao');
    }

    /** GET documentacao: índice (seções e páginas visíveis). */
    public function index(): ResponseInterface
    {
        return $this->apiResponse(['status' => 'success', 'data' => $this->service()->arvore()]);
    }

    /** GET documentacao/paginas/{slug...} */
    public function pagina(string ...$segmentos): ResponseInterface
    {
        $pagina = $this->service()->pagina(implode('/', $segmentos));

        return $pagina === null
            ? $this->apiNotFound('Página da documentação não encontrada.')
            : $this->apiResponse(['status' => 'success', 'data' => $pagina]);
    }

    /** GET documentacao/busca?q= */
    public function busca(): ResponseInterface
    {
        $q = trim((string) $this->request->getGet('q'));

        return $this->apiResponse(['status' => 'success', 'data' => $this->service()->buscar(mb_substr($q, 0, 100))]);
    }

    /** GET documentacao/tela/{tela}: página de ajuda contextual da tela. */
    public function tela(string $tela): ResponseInterface
    {
        $pagina = $this->service()->porTela($tela);

        return $pagina === null
            ? $this->apiNotFound('Esta tela não tem página de ajuda.')
            : $this->apiResponse(['status' => 'success', 'data' => $pagina]);
    }

    /** GET documentacao/imagens/{secao}/img/{arquivo} */
    public function imagem(string ...$segmentos): ResponseInterface
    {
        $caminho = $this->service()->caminhoDaImagem(implode('/', $segmentos));
        if ($caminho === null) {
            return $this->apiNotFound('Imagem não encontrada.');
        }

        $etag = '"' . md5($caminho . '@' . filemtime($caminho) . '@' . filesize($caminho)) . '"';
        $this->response
            ->setHeader('Cache-Control', 'private, max-age=86400')
            ->setHeader('ETag', $etag)
            ->setHeader('X-Content-Type-Options', 'nosniff');

        if ($this->request->getHeaderLine('If-None-Match') === $etag) {
            return $this->response->setStatusCode(304);
        }

        return $this->response
            ->setContentType((string) (mime_content_type($caminho) ?: 'application/octet-stream'))
            ->setBody((string) file_get_contents($caminho));
    }
}
