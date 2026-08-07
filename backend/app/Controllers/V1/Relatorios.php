<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Services\RelatoriosService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller do Módulo de Relatórios Financeiros (Extrato, DRE e Livro Caixa).
 * Todos os filtros são recebidos via GET para suportar bookmarking e
 * compartilhamento de URLs pelo frontend.
 */
class Relatorios extends BaseController
{
    protected RelatoriosService $service;

    public function __construct(?RelatoriosService $service = null)
    {
        $this->service = $service ?? new RelatoriosService();
    }

    /**
     * API: Retorna o Extrato de lançamentos do período.
     */
    public function extrato(): ResponseInterface
    {
        $filters = $this->request->getGet() ?? [];
        $data = $this->service->getExtratoData($filters);
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Retorna a DRE — Demonstração do Resultado do Exercício.
     */
    public function dre(): ResponseInterface
    {
        $filters = $this->request->getGet() ?? [];
        $data = $this->service->getDREData($filters);
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Retorna o Livro Caixa com saldo acumulado.
     */
    public function livroCaixa(): ResponseInterface
    {
        $filters = $this->request->getGet() ?? [];
        $data = $this->service->getLivroCaixaData($filters);
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }
}
