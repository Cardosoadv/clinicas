<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EstoqueProdutosRepository;
use App\Repositories\EstoqueMovimentacoesRepository;
use Exception;

class EstoqueService extends BaseService
{
    protected EstoqueProdutosRepository $produtosRepo;
    protected EstoqueMovimentacoesRepository $movimentacoesRepo;
    protected \App\Repositories\ServicoProdutosRepository $servicoProdutosRepo;
    protected FatService $fatService;

    /**
     * @param EstoqueProdutosRepository|null $produtosRepo
     * @param EstoqueMovimentacoesRepository|null $movimentacoesRepo
     * @param \App\Repositories\ServicoProdutosRepository|null $servicoProdutosRepo
     * @param FatService|null $fatService
     */
    public function __construct(
        ?EstoqueProdutosRepository $produtosRepo = null,
        ?EstoqueMovimentacoesRepository $movimentacoesRepo = null,
        ?\App\Repositories\ServicoProdutosRepository $servicoProdutosRepo = null,
        ?FatService $fatService = null
    ) {
        $this->produtosRepo = $produtosRepo ?? new EstoqueProdutosRepository();
        $this->movimentacoesRepo = $movimentacoesRepo ?? new EstoqueMovimentacoesRepository();
        $this->servicoProdutosRepo = $servicoProdutosRepo ?? new \App\Repositories\ServicoProdutosRepository();
        $this->fatService = $fatService ?? new FatService();
        
        $this->repository = $this->produtosRepo; // Default for BaseService
    }

    /**
     * Registra uma entrada de estoque
     *
     * @param array $data
     * @return array
     */
    public function registrarEntrada(array $data): array
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $produto_id = $data['produto_id'];
            $quantidade = $data['quantidade'];
            
            $movimentacaoData = [
                'produto_id' => $produto_id,
                'tipo'       => 'Entrada',
                'quantidade' => $quantidade,
                'observacao' => $data['observacao'] ?? null,
                'valor_unitario' => $data['valor_unitario'] ?? null,
                'valor_total'    => $data['valor_total'] ?? null,
            ];

            // Integrar com financeiro se solicitado
            if (!empty($data['lancar_financeiro']) && !empty($data['valor_total'])) {
                $despesaData = [
                    'descricao' => 'Compra de Estoque: ' . ($data['produto_nome'] ?? 'Produto ID ' . $produto_id),
                    'categoria' => 'Estoque',
                    'fornecedor' => $data['fornecedor'] ?? null,
                    'valor' => $data['valor_total'],
                    'data_vencimento' => $data['data_vencimento'] ?? date('Y-m-d'),
                    'forma_pagamento' => $data['forma_pagamento'] ?? null,
                    'status' => $data['status_pagamento'] ?? 'Pago'
                ];
                
                $resultFinanceiro = $this->fatService->createDespesa($despesaData);
                if ($resultFinanceiro['status'] === 'success') {
                    $movimentacaoData['despesa_id'] = $resultFinanceiro['id'];
                } else {
                    throw new Exception("Erro ao lançar no financeiro.");
                }
            }

            // 1. Criar Movimentação
            $this->movimentacoesRepo->create($movimentacaoData);

            // 2. Atualizar Produto
            $produto = $this->produtosRepo->findById((int)$produto_id);
            if (!$produto) {
                throw new Exception("Produto não encontrado.");
            }
            
            $novaQuantidade = $produto['quantidade_atual'] + $quantidade;
            $this->produtosRepo->update((int)$produto_id, ['quantidade_atual' => $novaQuantidade]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->error('Erro ao registrar entrada de estoque.');
            }

            return $this->success('Entrada de estoque registrada com sucesso.');

        } catch (Exception $e) {
            $db->transRollback();
            return $this->error($e->getMessage());
        }
    }

    /**
     * Registra uma saída de estoque
     *
     * @param array $data
     * @return array
     */
    public function registrarSaida(array $data): array
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $produto_id = $data['produto_id'];
            $quantidade = $data['quantidade'];

            $produto = $this->produtosRepo->findById((int)$produto_id);
            if (!$produto) {
                throw new Exception("Produto não encontrado.");
            }

            if ($produto['quantidade_atual'] < $quantidade) {
                throw new Exception("Quantidade em estoque insuficiente.");
            }

            $movimentacaoData = [
                'produto_id' => $produto_id,
                'tipo'       => 'Saída',
                'quantidade' => $quantidade,
                'observacao' => $data['observacao'] ?? null,
            ];

            // 1. Criar Movimentação
            $this->movimentacoesRepo->create($movimentacaoData);

            // 2. Atualizar Produto
            $novaQuantidade = $produto['quantidade_atual'] - $quantidade;
            $this->produtosRepo->update((int)$produto_id, ['quantidade_atual' => $novaQuantidade]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->error('Erro ao registrar saída de estoque.');
            }

            return $this->success('Saída de estoque registrada com sucesso.');

        } catch (Exception $e) {
            $db->transRollback();
            return $this->error($e->getMessage());
        }
    }

    /**
     * Retorna alertas de estoque baixo
     *
     * @return array
     */
    public function getLowStockAlerts(): array
    {
        return $this->produtosRepo->getLowStockAlerts();
    }

    /**
     * Deduz o estoque automaticamente com base nos produtos vinculados a um serviço.
     * Utilizado na finalização de faturamento (BOM).
     *
     * @param int $servicoId
     * @param float $multiplicador Quantas vezes o serviço foi realizado (geralmente 1.0)
     * @return array
     */
    public function deduzirEstoquePorServico(int $servicoId, float $multiplicador = 1.0): array
    {
        $vinculos = $this->servicoProdutosRepo->findByServico($servicoId);

        if (empty($vinculos)) {
            return $this->success('Nenhum produto vinculado a este serviço.');
        }

        $errors = [];
        $sucessos = 0;

        foreach ($vinculos as $v) {
            $quantidadeTotal = (float)$v['quantidade'] * $multiplicador;

            $result = $this->registrarSaida([
                'produto_id' => $v['produto_id'],
                'quantidade' => $quantidadeTotal,
                'observacao' => "Saída automática via faturamento de serviço (BOM) - Serviço ID: $servicoId"
            ]);

            if ($result['status'] === 'success') {
                $sucessos++;
            } else {
                $errors[] = "Erro ao deduzir '{$v['produto_nome']}': " . $result['message'];
            }
        }

        if (!empty($errors)) {
            return $this->error("Dedução parcial: $sucessos sucessos, " . count($errors) . " falhas.", ['errors' => $errors]);
        }

        return $this->success("Estoque deduzido automaticamente para $sucessos produtos vinculados.");
    }
}
