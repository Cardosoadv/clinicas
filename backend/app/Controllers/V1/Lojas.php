<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Services\LojasService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller responsável pela gestão das lojas (unidades) da clínica.
 */
class Lojas extends BaseController
{
    protected LojasService $service;

    public function __construct(?LojasService $service = null)
    {
        $this->service = $service ?? new LojasService();
    }

    /**
     * API: Retorna a lista completa de lojas.
     */
    public function getAll(): ResponseInterface
    {
        $data = $this->service->getAll();
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Retorna a loja usada como identidade visual do sistema (nome,
     * endereço e logo exibidos na barra lateral e nos relatórios impressos).
     */
    public function getPrincipal(): ResponseInterface
    {
        $loja = $this->service->getPrincipal();
        return $this->apiResponse(['status' => 'success', 'data' => $loja]);
    }

    /**
     * API: Busca os detalhes de uma loja específica pelo ID.
     */
    public function getById(int $id): ResponseInterface
    {
        $loja = $this->service->findById($id);
        if (!$loja) {
            return $this->apiNotFound('Loja não encontrada.');
        }
        return $this->apiResponse(['status' => 'success', 'data' => $loja]);
    }

    /**
     * API: Cadastra uma nova loja. Aceita multipart/form-data (logo_file opcional).
     */
    public function create(): ResponseInterface
    {
        // multipart/form-data: os campos vêm por $_POST junto com o arquivo, não em JSON.
        $data = $this->request->getPost();

        if (!$this->validateData($data, $this->getValidationRules())) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $file = $this->request->getFile('logo_file');
        $result = $this->service->createWithLogo($data, $file);
        return $this->apiResponse($result, 201);
    }

    /**
     * API: Atualiza os dados de uma loja existente. Aceita multipart/form-data.
     */
    public function update(int $id): ResponseInterface
    {
        $data = $this->request->getPost();

        if (!$this->validateData($data, $this->getValidationRules())) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $file = $this->request->getFile('logo_file');
        $result = $this->service->updateWithLogo($id, $data, $file);
        return $this->apiResponse($result);
    }

    /**
     * Retorna as regras de validação para criação e atualização.
     */
    private function getValidationRules(): array
    {
        return [
            'nome'      => 'required|min_length[3]|max_length[255]',
            'email'     => 'permit_empty|valid_email',
            'logo_file' => [
                'label' => 'Logo da Loja',
                'rules' => [
                    'permit_empty',
                    'is_image[logo_file]',
                    'mime_in[logo_file,image/png,image/jpg,image/jpeg,image/webp]',
                    'ext_in[logo_file,png,jpg,jpeg,webp]',
                    'max_size[logo_file,2048]',
                ],
            ],
        ];
    }

    /**
     * API: Remove uma loja do sistema.
     */
    public function delete(int $id): ResponseInterface
    {
        $result = $this->service->delete($id);
        return $this->apiResponse($result);
    }

    /**
     * Serve a logo da loja armazenada de forma segura.
     */
    public function viewLogo(int $id, string $filename): ResponseInterface
    {
        $filename = basename($filename);
        $path = WRITEPATH . 'imagens/loja/' . $id . '/' . $filename;

        if (!file_exists($path)) {
            return $this->apiNotFound('Logo não encontrada.');
        }

        $file = new \CodeIgniter\Files\File($path);

        return $this->response
            ->setHeader('Content-Type', $file->getMimeType())
            ->setBody(file_get_contents($path));
    }
}
