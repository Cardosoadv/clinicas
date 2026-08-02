<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RelatoriosRepository;

/**
 * Serviço de Relatórios Financeiros
 *
 * Orquestra os dados do RelatoriosRepository e aplica
 * lógica de negócio: saldo acumulado, estrutura DRE, totalização.
 */
class RelatoriosService
{
    protected RelatoriosRepository $repo;

    /**
     * @param RelatoriosRepository|null $repo
     */
    public function __construct(?RelatoriosRepository $repo = null)
    {
        $this->repo = $repo ?? new RelatoriosRepository();
    }

    // ─────────────────────────────────────────────
    //  HELPERS DE PERÍODO
    // ─────────────────────────────────────────────

    /**
     * Resolve início e fim do período a partir de filtros GET.
     * Padrão: mês corrente.
     *
     * @param array $filters
     * @return array
     */
    public function resolvePeriodo(array $filters): array
    {
        $inicio = $filters['inicio'] ?? date('Y-m-01');
        $fim    = $filters['fim']    ?? date('Y-m-t');

        // Sanity: se início > fim, inverte
        if ($inicio > $fim) {
            [$inicio, $fim] = [$fim, $inicio];
        }

        return [
            'inicio'       => $inicio,
            'fim'          => $fim,
            'inicio_fmt'   => date('d/m/Y', strtotime($inicio)),
            'fim_fmt'      => date('d/m/Y', strtotime($fim)),
            'mes_referencia' => date('F/Y', strtotime($inicio)),
        ];
    }

    // ─────────────────────────────────────────────
    //  EXTRATO
    // ─────────────────────────────────────────────

    /**
     * Retorna todos os dados necessários para renderizar o Extrato.
     *
     * @param array $filters
     * @return array
     */
    public function getExtratoData(array $filters): array
    {
        $periodo = $this->resolvePeriodo($filters);

        $cobrancas = $this->repo->getCobrancasExtrato($periodo['inicio'], $periodo['fim']);
        $despesas  = $this->repo->getDespesasExtrato($periodo['inicio'], $periodo['fim']);

        // Normaliza para array unificado com campos comuns
        $lancamentos = [];

        foreach ($cobrancas as $c) {
            $lancamentos[] = [
                'id'          => $c['id'],
                'data'        => $c['data_servico'],
                'historico'   => ($c['paciente_nome'] ?? 'Pet') . ' — ' . ($c['servico'] ?? 'Serviço'),
                'tipo'        => 'cobranca',
                'natureza'    => 'Entrada',
                'valor'       => (float) $c['valor'],
                'status'      => $c['status'],
                'forma_pgto'  => $c['forma_pagamento'] ?? '',
                'categoria'   => $c['servico'] ?? '',
                'origem'      => 'cobranca',
            ];
        }

        foreach ($despesas as $d) {
            $lancamentos[] = [
                'id'          => $d['id'],
                'data'        => $d['data_vencimento'],
                'historico'   => ($d['fornecedor'] ?? 'Fornecedor') . ' — ' . ($d['descricao'] ?? 'Despesa'),
                'tipo'        => 'despesa',
                'natureza'    => 'Saída',
                'valor'       => (float) $d['valor'],
                'status'      => $d['status'],
                'forma_pgto'  => $d['forma_pagamento'] ?? '',
                'categoria'   => $d['categoria'] ?? '',
                'origem'      => 'despesa',
            ];
        }

        // Ordena cronologicamente
        usort($lancamentos, static fn($a, $b) => strcmp($a['data'], $b['data']));

        // Aplica filtro de tipo se informado
        $tipoFiltro = $filters['tipo'] ?? 'todos';
        if ($tipoFiltro === 'entradas') {
            $lancamentos = array_filter($lancamentos, static fn($l) => $l['natureza'] === 'Entrada');
        } elseif ($tipoFiltro === 'saidas') {
            $lancamentos = array_filter($lancamentos, static fn($l) => $l['natureza'] === 'Saída');
        }

        // Filtro de status
        $statusFiltro = $filters['status'] ?? 'todos';
        if ($statusFiltro !== 'todos') {
            $lancamentos = array_filter($lancamentos, static fn($l) => strtolower($l['status']) === strtolower($statusFiltro));
        }

        $lancamentos = array_values($lancamentos);

        // Totalização
        $totalEntradas = array_sum(array_map(
            static fn($l) => $l['natureza'] === 'Entrada' ? $l['valor'] : 0,
            $lancamentos
        ));
        $totalSaidas = array_sum(array_map(
            static fn($l) => $l['natureza'] === 'Saída' ? $l['valor'] : 0,
            $lancamentos
        ));

        return [
            'periodo'       => $periodo,
            'lancamentos'   => $lancamentos,
            'total_entradas' => $totalEntradas,
            'total_saidas'   => $totalSaidas,
            'saldo_periodo'  => $totalEntradas - $totalSaidas,
            'filtros'        => [
                'tipo'    => $tipoFiltro,
                'status'  => $statusFiltro,
                'inicio'  => $periodo['inicio'],
                'fim'     => $periodo['fim'],
            ],
        ];
    }

    // ─────────────────────────────────────────────
    //  DRE
    // ─────────────────────────────────────────────

    /**
     * Retorna a estrutura completa do DRE.
     *
     * @param array $filters
     * @return array
     */
    public function getDREData(array $filters): array
    {
        $periodo = $this->resolvePeriodo($filters);

        $totais      = $this->repo->getTotaisDRE($periodo['inicio'], $periodo['fim']);
        $porServico  = $this->repo->getReceitasBrutasDRE($periodo['inicio'], $periodo['fim']);
        $porCategoria = $this->repo->getDespesasPorCategoriaDRE($periodo['inicio'], $periodo['fim']);

        $receitaBruta  = (float) $totais['receita_bruta'];
        $descontos     = (float) $totais['descontos'];
        $receitaLiquida = $receitaBruta - $descontos;
        $totalDespesas = (float) $totais['despesas'];
        $resultadoLiquido = $receitaLiquida - $totalDespesas;

        // Margem de lucro
        $margem = ($receitaBruta > 0)
            ? round(($resultadoLiquido / $receitaBruta) * 100, 1)
            : 0;

        // Período anterior (mês -1) para variação
        $inicioAnt = date('Y-m-01', strtotime($periodo['inicio'] . ' -1 month'));
        $fimAnt    = date('Y-m-t',  strtotime($periodo['inicio'] . ' -1 month'));
        $totaisAnt = $this->repo->getTotaisDRE($inicioAnt, $fimAnt);

        $receitaAnt = (float) $totaisAnt['receita_bruta'];
        $varReceita = ($receitaAnt > 0)
            ? round((($receitaBruta - $receitaAnt) / $receitaAnt) * 100, 1)
            : 0;

        $despesaAnt = (float) $totaisAnt['despesas'];
        $varDespesa = ($despesaAnt > 0)
            ? round((($totalDespesas - $despesaAnt) / $despesaAnt) * 100, 1)
            : 0;

        return [
            'periodo'          => $periodo,
            'receita_bruta'    => $receitaBruta,
            'descontos'        => $descontos,
            'receita_liquida'  => $receitaLiquida,
            'total_despesas'   => $totalDespesas,
            'resultado_liquido' => $resultadoLiquido,
            'margem_liquida'   => $margem,
            'por_servico'      => $porServico,
            'por_categoria'    => $porCategoria,
            'variacao_receita' => $varReceita,
            'variacao_despesa' => $varDespesa,
            'filtros'          => [
                'inicio' => $periodo['inicio'],
                'fim'    => $periodo['fim'],
            ],
        ];
    }

    // ─────────────────────────────────────────────
    //  LIVRO CAIXA
    // ─────────────────────────────────────────────

    /**
     * Retorna os movimentos com saldo acumulado para o Livro Caixa.
     *
     * @param array $filters
     * @return array
     */
    public function getLivroCaixaData(array $filters): array
    {
        $periodo = $this->resolvePeriodo($filters);

        $saldoInicial = $this->repo->getSaldoAnterior($periodo['inicio']);
        $entradas     = $this->repo->getEntradasLivroCaixa($periodo['inicio'], $periodo['fim']);
        $saidas       = $this->repo->getSaidasLivroCaixa($periodo['inicio'], $periodo['fim']);

        // Constrói movimentos unificados
        $movimentos = [];

        foreach ($entradas as $e) {
            $movimentos[] = [
                'data'       => $e['data_servico'],
                'historico'  => ($e['paciente_nome'] ?? 'Pet') . ' — ' . ($e['servico'] ?? 'Serviço'),
                'entrada'    => (float) $e['valor'],
                'saida'      => 0.0,
                'forma_pgto' => $e['forma_pagamento'] ?? '',
                'tipo'       => 'entrada',
            ];
        }

        foreach ($saidas as $s) {
            $movimentos[] = [
                'data'       => $s['data_vencimento'],
                'historico'  => ($s['fornecedor'] ?? 'Despesa') . ' — ' . ($s['descricao'] ?? ''),
                'entrada'    => 0.0,
                'saida'      => (float) $s['valor'],
                'forma_pgto' => $s['forma_pagamento'] ?? '',
                'tipo'       => 'saida',
            ];
        }

        // Ordena por data
        usort($movimentos, static fn($a, $b) => strcmp($a['data'], $b['data']));

        // Calcula saldo acumulado
        $saldo = $saldoInicial;
        foreach ($movimentos as &$mov) {
            $saldo += $mov['entrada'] - $mov['saida'];
            $mov['saldo_acumulado'] = $saldo;
        }
        unset($mov);

        $totalEntradas = array_sum(array_column($movimentos, 'entrada'));
        $totalSaidas   = array_sum(array_column($movimentos, 'saida'));

        return [
            'periodo'        => $periodo,
            'saldo_inicial'  => $saldoInicial,
            'movimentos'     => $movimentos,
            'total_entradas' => $totalEntradas,
            'total_saidas'   => $totalSaidas,
            'saldo_final'    => $saldoInicial + $totalEntradas - $totalSaidas,
            'filtros'        => [
                'inicio' => $periodo['inicio'],
                'fim'    => $periodo['fim'],
            ],
        ];
    }
}
