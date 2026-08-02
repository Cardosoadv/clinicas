<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Services\ClientesService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller responsável pelo cadastro de clientes (tutores) — CRUD e painel da tela de Clientes & CRM.
 */
class Clientes extends BaseController
{
    protected ClientesService $service;

    public function __construct(?ClientesService $service = null)
    {
        $this->service = $service ?? new ClientesService();
    }

    /**
     * API: Lista clientes filtrados por status (ativos/inativos/todos), tipo de
     * pessoa (fisica/juridica) e um termo de busca livre — todos opcionais via querystring.
     */
    public function getAll(): ResponseInterface
    {
        $status = (string) ($this->request->getGet('status') ?? 'ativos');
        $tipo   = $this->request->getGet('tipo') ?: null;
        $term   = $this->request->getGet('search') ?: null;

        $data = $this->service->search($status, $tipo, $term);
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Retorna os dados do painel lateral (estatísticas, adições recentes
     * e aniversariantes do mês) exibidos na tela de Clientes & CRM.
     */
    public function getPainel(): ResponseInterface
    {
        $data = $this->service->getPainelData();
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Busca os detalhes de um cliente pelo ID.
     */
    public function getById(int $id): ResponseInterface
    {
        $cliente = $this->service->findById($id);
        if (!$cliente) {
            return $this->apiNotFound('Cliente não encontrado.');
        }
        return $this->apiResponse(['status' => 'success', 'data' => $cliente]);
    }

    /**
     * API: Cadastra um novo cliente.
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
     * API: Atualiza os dados de um cliente existente.
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

    /**
     * API: Remove (soft delete) um cliente.
     */
    public function delete(int $id): ResponseInterface
    {
        $result = $this->service->delete($id);
        return $this->apiResponse($result);
    }

    /**
     * API: Lista os pacientes vinculados a um cliente.
     */
    public function getPacientes(int $id): ResponseInterface
    {
        $data = $this->service->getPacientes($id);
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Lista as notas internas registradas sobre um cliente.
     */
    public function getNotas(int $id): ResponseInterface
    {
        $data = $this->service->getNotas($id);
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Registra uma nova nota sobre um cliente.
     */
    public function createNota(int $id): ResponseInterface
    {
        $data = $this->getRequestData();

        if (!$this->validateData($data, ['texto' => 'required|min_length[2]'])) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $autor  = auth()->user()->username ?? auth()->user()->email ?? null;
        $result = $this->service->addNota($id, (string) $data['texto'], $autor);

        return $this->apiResponse($result, 201);
    }

    /**
     * API: Remove uma nota de um cliente.
     */
    public function deleteNota(int $id, int $notaId): ResponseInterface
    {
        $result = $this->service->deleteNota($id, $notaId);
        return $this->apiResponse($result);
    }

    /**
     * API: Lista o histórico de comunicações enviadas a um cliente.
     */
    public function getComunicacoes(int $id): ResponseInterface
    {
        $data = $this->service->getComunicacoes($id);
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Registra e gera o link de uma comunicação manual (WhatsApp) para um cliente.
     */
    public function createComunicacao(int $id): ResponseInterface
    {
        $data = $this->getRequestData();

        if (!$this->validateData($data, ['mensagem' => 'required|min_length[2]'])) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->enviarComunicacao($id, (string) $data['mensagem']);
        return $this->apiResponse($result, 201);
    }

    private function getValidationRules(): array
    {
        return [
            'nome' => 'required|min_length[3]|max_length[255]',
            'cpf'  => 'permit_empty|max_length[14]|valid_cpf',
            'cnpj' => 'permit_empty|max_length[18]|valid_cnpj',
        ];
    }
}
