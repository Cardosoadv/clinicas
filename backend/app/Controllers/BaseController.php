<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Inicializa o controlador, carrega helpers e executa ações comuns a todos os controladores.
     *
     * @param RequestInterface $request Instância da requisição HTTP
     * @param ResponseInterface $response Instância da resposta HTTP
     * @param LoggerInterface $logger Instância do logger
     *
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        $this->helpers = ['url'];

        parent::initController($request, $response, $logger);
    }

    /**
     * Lê o corpo da requisição independentemente do formato usado pelo cliente:
     * JSON (application/json, o formato padrão enviado pelo frontend React),
     * form urlencoded (PUT/PATCH) ou form POST tradicional.
     * Uploads multipart continuam sendo lidos via getPost()/getFile() diretamente,
     * pois arquivos não trafegam em um corpo JSON.
     */
    protected function getRequestData(): array
    {
        if (str_contains($this->request->getHeaderLine('Content-Type'), 'application/json')) {
            return (array) ($this->request->getJSON(true) ?? []);
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            return $this->request->getPost();
        }

        return $this->request->getRawInput();
    }

    /**
     * Envelope padrão de resposta da API: {status, message, ...}.
     * O status HTTP é derivado do campo 'status' retornado pelos Services
     * ('success' ou 'error'), garantindo que o frontend possa confiar tanto
     * no corpo JSON quanto no código HTTP.
     *
     * @param array $result Resultado no formato retornado por BaseService::success()/error()
     */
    protected function apiResponse(array $result, int $successCode = 200, int $errorCode = 400): ResponseInterface
    {
        $code = (($result['status'] ?? null) === 'success') ? $successCode : $errorCode;

        return $this->response->setStatusCode($code)->setJSON($result);
    }

    /**
     * Resposta de erro no mesmo envelope {status, message, ...}.
     */
    protected function apiError(string $message, int $code = 400, array $extra = []): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON(array_merge(['status' => 'error', 'message' => $message], $extra));
    }

    protected function apiNotFound(string $message = 'Registro não encontrado.'): ResponseInterface
    {
        return $this->apiError($message, 404);
    }

    /**
     * Resposta de falha de validação, incluindo os erros por campo retornados
     * pelo Validation Service ($this->validator->getErrors()).
     */
    protected function apiValidationError(array $errors): ResponseInterface
    {
        return $this->apiError('Dados inválidos.', 422, ['errors' => $errors]);
    }
}
