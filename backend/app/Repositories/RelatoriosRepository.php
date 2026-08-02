<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\FatCobrancaModel;
use App\Models\FatDespesaModel;

/**
 * Repositório de Relatórios Financeiros
 *
 * Fornece queries por período para Extrato, DRE e Livro Caixa.
 * Trabalha em regime de caixa: apenas lançamentos com status 'Pago'
 * são considerados nos totalizadores do DRE e Livro Caixa.
 */
class RelatoriosRepository
{
    protected FatCobrancaModel $cobrancaModel;
    protected FatDespesaModel  $despesaModel;

    /**
     * @param FatCobrancaModel|null $cobrancaModel
     * @param FatDespesaModel|null $despesaModel
     */
    public function __construct(
        ?FatCobrancaModel $cobrancaModel = null,
        ?FatDespesaModel  $despesaModel  = null
    ) {
        $this->cobrancaModel = $cobrancaModel ?? new FatCobrancaModel();
        $this->despesaModel  = $despesaModel  ?? new FatDespesaModel();
    }

    // ─────────────────────────────────────────────
    //  EXTRATO
    // ─────────────────────────────────────────────

    /**
     * Retorna cobranças do período para o Extrato (todos os status).
     *
     * @param string $inicio
     * @param string $fim
     * @return array
     */
    public function getCobrancasExtrato(string $inicio, string $fim): array
    {
        return $this->cobrancaModel
            ->select('fat_cobrancas.*, pacientes.paciente_nome, "cobranca" as tipo_mov')
            ->join('pacientes', 'pacientes.paciente_id = fat_cobrancas.paciente_id', 'left')
            ->where('fat_cobrancas.data_servico >=', $inicio)
            ->where('fat_cobrancas.data_servico <=', $fim)
            ->orderBy('fat_cobrancas.data_servico', 'ASC')
            ->findAll();
    }

    /**
     * Retorna despesas do período para o Extrato (todos os status).
     *
     * @param string $inicio
     * @param string $fim
     * @return array
     */
    public function getDespesasExtrato(string $inicio, string $fim): array
    {
        return $this->despesaModel
            ->select('fat_despesas.*, "despesa" as tipo_mov')
            ->where('data_vencimento >=', $inicio)
            ->where('data_vencimento <=', $fim)
            ->orderBy('data_vencimento', 'ASC')
            ->findAll();
    }

    // ─────────────────────────────────────────────
    //  DRE
    // ─────────────────────────────────────────────

    /**
     * Retorna total de receitas pagas por grupo de serviço (DRE).
     *
     * @param string $inicio
     * @param string $fim
     * @return array
     */
    public function getReceitasBrutasDRE(string $inicio, string $fim): array
    {
        return $this->cobrancaModel
            ->select('servico, SUM(valor) as total, COUNT(id) as qtd')
            ->where('data_servico >=', $inicio)
            ->where('data_servico <=', $fim)
            ->where('status', 'Pago')
            ->groupBy('servico')
            ->orderBy('total', 'DESC')
            ->findAll();
    }

    /**
     * Retorna total de descontos concedidos no período (DRE).
     *
     * @param string $inicio
     * @param string $fim
     * @return float
     */
    public function getDescontosDRE(string $inicio, string $fim): float
    {
        $result = $this->cobrancaModel
            ->selectSum('desconto')
            ->where('data_servico >=', $inicio)
            ->where('data_servico <=', $fim)
            ->where('status', 'Pago')
            ->first();

        return (float) ($result['desconto'] ?? 0.0);
    }

    /**
     * Retorna despesas pagas por categoria (DRE).
     *
     * @param string $inicio
     * @param string $fim
     * @return array
     */
    public function getDespesasPorCategoriaDRE(string $inicio, string $fim): array
    {
        return $this->despesaModel
            ->select('categoria, SUM(valor) as total, COUNT(id) as qtd')
            ->where('data_vencimento >=', $inicio)
            ->where('data_vencimento <=', $fim)
            ->where('status', 'Pago')
            ->groupBy('categoria')
            ->orderBy('total', 'DESC')
            ->findAll();
    }

    /**
     * Retorna totais do período para o DRE.
     *
     * @param string $inicio
     * @param string $fim
     * @return array
     */
    public function getTotaisDRE(string $inicio, string $fim): array
    {
        $cobrancas = $this->cobrancaModel
            ->selectSum('valor', 'receita_bruta')
            ->selectSum('desconto', 'descontos')
            ->where('data_servico >=', $inicio)
            ->where('data_servico <=', $fim)
            ->where('status', 'Pago')
            ->first();

        $despesa = $this->despesaModel
            ->selectSum('valor')
            ->where('data_vencimento >=', $inicio)
            ->where('data_vencimento <=', $fim)
            ->where('status', 'Pago')
            ->first();

        return [
            'receita_bruta' => (float) ($cobrancas['receita_bruta'] ?? 0.0),
            'descontos'     => (float) ($cobrancas['descontos']     ?? 0.0),
            'despesas'      => (float) ($despesa['valor']           ?? 0.0),
        ];
    }

    // ─────────────────────────────────────────────
    //  LIVRO CAIXA
    // ─────────────────────────────────────────────

    /**
     * Retorna entradas (cobranças pagas) agrupadas por data para o Livro Caixa.
     *
     * @param string $inicio
     * @param string $fim
     * @return array
     */
    public function getEntradasLivroCaixa(string $inicio, string $fim): array
    {
        return $this->cobrancaModel
            ->select('fat_cobrancas.*, pacientes.paciente_nome, "Entrada" as natureza')
            ->join('pacientes', 'pacientes.paciente_id = fat_cobrancas.paciente_id', 'left')
            ->where('fat_cobrancas.data_servico >=', $inicio)
            ->where('fat_cobrancas.data_servico <=', $fim)
            ->where('fat_cobrancas.status', 'Pago')
            ->orderBy('fat_cobrancas.data_servico', 'ASC')
            ->findAll();
    }

    /**
     * Retorna saídas (despesas pagas) para o Livro Caixa.
     *
     * @param string $inicio
     * @param string $fim
     * @return array
     */
    public function getSaidasLivroCaixa(string $inicio, string $fim): array
    {
        return $this->despesaModel
            ->select('fat_despesas.*, "Saída" as natureza')
            ->where('data_vencimento >=', $inicio)
            ->where('data_vencimento <=', $fim)
            ->where('status', 'Pago')
            ->orderBy('data_vencimento', 'ASC')
            ->findAll();
    }

    /**
     * Saldo acumulado antes do período (para saldo inicial do Livro Caixa).
     *
     * @param string $inicio
     * @return float
     */
    public function getSaldoAnterior(string $inicio): float
    {
        $entradas = $this->cobrancaModel
            ->selectSum('valor')
            ->where('data_servico <', $inicio)
            ->where('status', 'Pago')
            ->first();

        $saidas = $this->despesaModel
            ->selectSum('valor')
            ->where('data_vencimento <', $inicio)
            ->where('status', 'Pago')
            ->first();

        return (float) ($entradas['valor'] ?? 0.0) - (float) ($saidas['valor'] ?? 0.0);
    }
}
