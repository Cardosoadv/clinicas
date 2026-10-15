<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\FatDespesaModel;

class FatDespesaRepository extends BaseRepository
{
    /**
     * @param FatDespesaModel|null $model
     */
    public function __construct(?FatDespesaModel $model = null)
    {
        $this->model = $model ?? new FatDespesaModel();
    }

    /**
     * Busca despesas recentes
     *
     * @param int $limit
     * @return array
     */
    public function getRecent(int $limit = 5): array
    {
        return $this->model
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }

    /**
     * Calcula o total de despesas pagas no mês
     *
     * @param string $month
     * @param string $year
     * @return float
     */
    public function getTotalMonth(int|string $month, int|string $year): float
    {
        $month = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
        // Using date range for SARGability to leverage idx_fat_despesas_data
        $start = "{$year}-{$month}-01";
        $end = date('Y-m-t', strtotime($start));

        $result = $this->model
            ->selectSum('valor')
            ->where('data_vencimento >=', $start)
            ->where('data_vencimento <=', $end)
            ->where('status', 'Pago')
            ->first();

        return (float) ($result['valor'] ?? 0.0);
    }

    /**
     * Busca todas as despesas de um grupo (parcelas ou recorrência), em ordem de parcela e vencimento.
     *
     * @param string $grupoId
     * @return array
     */
    public function getByGrupo(string $grupoId): array
    {
        return $this->model
            ->where('des_grupo_id', $grupoId)
            ->orderBy('des_parcela_num', 'ASC')
            ->orderBy('data_vencimento', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Lista despesas por vencimento, com filtros opcionais e paginação.
     *
     * @param string|null $mes Mês de vencimento (AAAA-MM)
     * @param string|null $status
     * @param int $page Página (a partir de 1)
     * @param int $perPage
     * @return array{items: array, total: int}
     */
    public function listar(?string $mes, ?string $status, int $page, int $perPage): array
    {
        if ($mes !== null) {
            $inicio = "{$mes}-01";
            $this->model
                ->where('data_vencimento >=', $inicio)
                ->where('data_vencimento <=', date('Y-m-t', strtotime($inicio)));
        }
        if ($status !== null) {
            $this->model->where('status', $status);
        }

        $total = $this->model->countAllResults(false);
        $items = $this->model
            ->orderBy('data_vencimento', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll($perPage, ($page - 1) * $perPage);

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Totais a pagar: despesas pendentes (todas) e, delas, as vencidas antes de $hoje.
     *
     * @param string $hoje Data AAAA-MM-DD
     * @return array{a_pagar: float, vencidas: float}
     */
    public function getResumoAPagar(string $hoje): array
    {
        // normaliza a data antes de interpolar no SQL
        $hoje = date('Y-m-d', strtotime($hoje));

        $result = $this->model
            ->select('SUM(valor) as a_pagar', false)
            ->select("SUM(CASE WHEN data_vencimento < '{$hoje}' THEN valor ELSE 0 END) as vencidas", false)
            ->where('status', 'Pendente')
            ->first();

        return [
            'a_pagar'  => (float) ($result['a_pagar'] ?? 0.0),
            'vencidas' => (float) ($result['vencidas'] ?? 0.0),
        ];
    }

    /**
     * Despesas pendentes por mês de vencimento, a partir de $aPartirDe.
     *
     * @param string $aPartirDe Data AAAA-MM-DD
     * @return array<int, array{mes: string, total: float}>
     */
    public function getPendentesPorMes(string $aPartirDe): array
    {
        $rows = $this->model
            ->select('SUBSTR(data_vencimento, 1, 7) as mes, SUM(valor) as total', false)
            ->where('status', 'Pendente')
            ->where('data_vencimento >=', $aPartirDe)
            ->groupBy('mes')
            ->orderBy('mes', 'ASC')
            ->findAll();

        return array_map(
            static fn(array $r): array => ['mes' => (string) $r['mes'], 'total' => (float) $r['total']],
            $rows
        );
    }
}
