<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Libraries\Documentacao\ErroDeValidacao;
use App\Libraries\Documentacao\NaoEncontrado;
use App\Services\Documentacao\EdicaoDocumentacaoService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Edição das páginas da documentação. Contrato em docs/api-documentacao.md.
 *
 * Rotas atrás de `apipermission:documentacao.editar`.
 * Erros: 422 com `errors` por campo, 404 para página inexistente.
 */
class DocumentacaoAdmin extends BaseController
{
    public function __construct(private ?EdicaoDocumentacaoService $service = null)
    {
    }

    private function service(): EdicaoDocumentacaoService
    {
        return $this->service ??= new EdicaoDocumentacaoService();
    }

    /** GET documentacao/admin/paginas: páginas do banco e de arquivo. */
    public function index(): ResponseInterface
    {
        return $this->executar(fn () => $this->sucesso(['data' => $this->service()->listar()]));
    }

    public function show(int $id): ResponseInterface
    {
        return $this->executar(fn () => $this->sucesso(['data' => $this->service()->obter($id)]));
    }

    public function create(): ResponseInterface
    {
        return $this->executar(fn () => $this->sucesso($this->service()->criar($this->getRequestData()), 201));
    }

    public function update(int $id): ResponseInterface
    {
        return $this->executar(fn () => $this->sucesso($this->service()->atualizar($id, $this->getRequestData())));
    }

    public function publicar(int $id): ResponseInterface
    {
        return $this->executar(fn () => $this->sucesso(['data' => $this->service()->publicar($id)]));
    }

    public function despublicar(int $id): ResponseInterface
    {
        return $this->executar(fn () => $this->sucesso(['data' => $this->service()->despublicar($id)]));
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->executar(function () use ($id) {
            $this->service()->excluir($id);

            return $this->sucesso(['message' => 'Página excluída.', 'data' => ['id' => $id]]);
        });
    }

    /** POST documentacao/admin/paginas/de-arquivo/{slug...}: cópia editável de uma página de arquivo. */
    public function deArquivo(string ...$segmentos): ResponseInterface
    {
        return $this->executar(fn () => $this->sucesso($this->service()->copiarDoArquivo(implode('/', $segmentos)), 201));
    }

    /** POST documentacao/admin/preview: `{markdown, slug}` → `{html, sumario, avisos}`, sem gravar. */
    public function preview(): ResponseInterface
    {
        return $this->executar(function () {
            $entrada = $this->getRequestData();

            return $this->sucesso(['data' => $this->service()->preview((string) ($entrada['markdown'] ?? ''), (string) ($entrada['slug'] ?? ''))]);
        });
    }

    /** POST documentacao/admin/imagens (multipart): `arquivo` e `secao`. */
    public function imagem(): ResponseInterface
    {
        return $this->executar(function () {
            $arquivo = $this->request->getFile('arquivo');
            if ($arquivo === null) {
                throw ErroDeValidacao::campo('arquivo', 'Envie a imagem no campo "arquivo" (multipart).');
            }
            if (!$arquivo->isValid()) {
                throw ErroDeValidacao::campo('arquivo', 'Falha no envio: ' . $arquivo->getErrorString() . '.');
            }

            return $this->sucesso(['data' => $this->service()->enviarImagem(
                $arquivo->getTempName(),
                $arquivo->getClientName(),
                (string) $this->request->getPost('secao'),
            )], 201);
        });
    }

    /** @param array<string, mixed> $corpo */
    private function sucesso(array $corpo, int $codigo = 200): ResponseInterface
    {
        return $this->apiResponse(['status' => 'success'] + $corpo, $codigo);
    }

    /** Converte as exceções do serviço nas respostas de erro da API. */
    private function executar(callable $acao): ResponseInterface
    {
        try {
            return $acao();
        } catch (ErroDeValidacao $e) {
            return $this->apiValidationError($e->erros());
        } catch (NaoEncontrado $e) {
            return $this->apiNotFound($e->getMessage());
        }
    }
}
