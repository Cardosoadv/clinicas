<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Services\ServicosService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller responsável pela gestão dos serviços/procedimentos oferecidos pela clínica.
 */
class Servicos extends BaseController
{
    protected ServicosService $service;

    public function __construct(?ServicosService $service = null)
    {
        $this->service = $service ?? new ServicosService();
    }

    /**
     * API: Retorna a lista completa de serviços ou filtrada por termo/status.
     */
    public function getAll(): ResponseInterface
    {
        $search = $this->request->getGet('search');
        $status = $this->request->getGet('status');

        $searchTerm = is_string($search) && trim($search) !== '' ? trim($search) : null;
        $statusFilter = is_string($status) && trim($status) !== '' ? trim($status) : null;

        $data = $this->service->getAllFiltered($searchTerm, $statusFilter);
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Busca os detalhes de um serviço específico, incluindo os produtos vinculados.
     */
    public function getById(int $id): ResponseInterface
    {
        $servico = $this->service->getServiceWithProducts($id);
        if (!$servico) {
            return $this->apiNotFound('Serviço não encontrado.');
        }
        return $this->apiResponse(['status' => 'success', 'data' => $servico]);
    }

    /**
     * API: Cadastra um novo serviço/procedimento.
     */
    public function create(): ResponseInterface
    {
        $data = $this->getRequestData();

        if (!$this->validateData($data, $this->getValidationRules())) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->create($data);
        return $this->apiResponse($result, 201);
    }

    /**
     * API: Atualiza os dados de um serviço existente.
     */
    public function update(int $id): ResponseInterface
    {
        $data = $this->getRequestData();

        if (!$this->validateData($data, $this->getValidationRules())) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->update($id, $data);
        return $this->apiResponse($result);
    }

    private function getValidationRules(): array
    {
        return [
            'ser_nome'  => 'required|min_length[2]|max_length[255]',
            'ser_valor' => 'permit_empty|numeric',
            'ser_status' => 'permit_empty|in_list[Ativo,Inativo]',
        ];
    }

    /**
     * API: Remove um serviço do sistema.
     */
    public function delete(int $id): ResponseInterface
    {
        $result = $this->service->delete($id);
        return $this->apiResponse($result);
    }

    /**
     * API: Sincroniza os produtos vinculados a um serviço (BOM).
     */
    public function syncProducts(int $id): ResponseInterface
    {
        $data = $this->getRequestData();
        $produtos = $data['produtos'] ?? [];

        $result = $this->service->syncProducts($id, (array) $produtos);
        return $this->apiResponse($result);
    }
}
