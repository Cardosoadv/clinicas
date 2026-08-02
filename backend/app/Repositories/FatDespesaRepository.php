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
}
