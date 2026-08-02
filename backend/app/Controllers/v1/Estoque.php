<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Services\EstoqueService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller responsável pelo módulo de estoque (produtos e movimentações).
 */
class Estoque extends BaseController
{
    protected EstoqueService $service;

    public function __construct(?EstoqueService $service = null)
    {
        $this->service = $service ?? new EstoqueService();
    }

    /**
     * API: Retorna a lista completa de produtos em estoque.
     */
    public function getAll(): ResponseInterface
    {
        $data = $this->service->getAll();
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Retorna os alertas de estoque baixo.
     */
    public function getAlertas(): ResponseInterface
    {
        $data = $this->service->getLowStockAlerts();
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Retorna os detalhes de um produto pelo ID.
     */
    public function getById(int $id): ResponseInterface
    {
        $produto = $this->service->findById($id);
        if (!$produto) {
            return $this->apiNotFound('Produto não encontrado.');
        }
        return $this->apiResponse(['status' => 'success', 'data' => $produto]);
    }

    /**
     * API: Cadastra um novo produto.
     */
    public function create(): ResponseInterface
    {
        $data = $this->getRequestData();

        if (!$this->validateData($data, [
            'nome'             => 'required|min_length[2]|max_length[255]',
            'quantidade_atual' => 'permit_empty|numeric',
            'estoque_minimo'   => 'permit_empty|numeric',
            'valor_venda'      => 'permit_empty|numeric',
        ])) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->create($data);
        return $this->apiResponse($result, 201);
    }

    /**
     * API: Atualiza um produto existente.
     */
    public function update(int $id): ResponseInterface
    {
        $data = $this->getRequestData();

        if (!$this->validateData($data, [
            'nome'             => 'required|min_length[2]|max_length[255]',
            'quantidade_atual' => 'permit_empty|numeric',
            'estoque_minimo'   => 'permit_empty|numeric',
            'valor_venda'      => 'permit_empty|numeric',
        ])) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->update($id, $data);
        return $this->apiResponse($result);
    }

    /**
     * API: Remove um produto do estoque.
     */
    public function delete(int $id): ResponseInterface
    {
        $result = $this->service->delete($id);
        return $this->apiResponse($result);
    }

    /**
     * API: Registra uma entrada de estoque para um produto.
     */
    public function registrarEntrada(): ResponseInterface
    {
        $data = $this->getRequestData();

        if (!$this->validateData($data, [
            'produto_id' => 'required|is_natural_no_zero',
            'quantidade' => 'required|numeric',
        ])) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->registrarEntrada($data);
        return $this->apiResponse($result, 201);
    }

    /**
     * API: Registra uma saída de estoque para um produto.
     */
    public function registrarSaida(): ResponseInterface
    {
        $data = $this->getRequestData();

        if (!$this->validateData($data, [
            'produto_id' => 'required|is_natural_no_zero',
            'quantidade' => 'required|numeric',
        ])) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->registrarSaida($data);
        return $this->apiResponse($result, 201);
    }
}
