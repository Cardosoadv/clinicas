<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\FatCobrancaModel;

class FatCobrancaRepository extends BaseRepository
{
    /**
     * @param FatCobrancaModel|null $model
     */
    public function __construct(?FatCobrancaModel $model = null)
    {
        $this->model = $model ?? new FatCobrancaModel();
    }

    /**
     * Busca cobranças recentes
     *
     * @param int $limit
     * @return array
     */
    public function getRecent(int $limit = 5): array
    {
        return $this->model
            ->select('fat_cobrancas.*, pacientes.paciente_nome')
            ->join('pacientes', 'pacientes.paciente_id = fat_cobrancas.paciente_id')
            ->orderBy('fat_cobrancas.created_at', 'DESC')
            ->findAll($limit);
    }

    /**
     * Calcula o total de cobranças pagas no mês
     *
     * @param string $month
     * @param string $year
     * @return float
     */
    public function getTotalMonth(int|string $month, int|string $year): float
    {
        // Ensure month is 2 digits for consistent date strings
        $month = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
        
        // Using date range for SARGability to leverage idx_fat_cobrancas_data
        $start = "{$year}-{$month}-01";
        $end = date('Y-m-t', strtotime($start));

        $result = $this->model
            ->selectSum('valor')
            ->where('data_servico >=', $start)
            ->where('data_servico <=', $end)
            ->where('status', 'Pago')
            ->first();

        return (float) ($result['valor'] ?? 0.0);
    }

    /**
     * Retorna os totais de cobranças pendentes e atrasadas em uma única query.
     */
    public function getSummaryStats(): array
    {
        $result = $this->model
            ->select("SUM(CASE WHEN status = 'Pendente' THEN valor ELSE 0 END) as a_receber", false)
            ->select("SUM(CASE WHEN status = 'Atrasado' THEN valor ELSE 0 END) as vencidos", false)
            ->first();

        return [
            'a_receber' => (float) ($result['a_receber'] ?? 0.0),
            'vencidos'  => (float) ($result['vencidos']  ?? 0.0),
        ];
    }

    /**
     * Busca detalhes de uma cobrança com dados do pet e tutor.
     *
     * @param int $id
     * @return array|null
     */
    public function findWithTutor(int $id): ?array
    {
        return $this->model
            ->select('fat_cobrancas.*, pacientes.paciente_nome, clientes.id as cliente_id, clientes.nome as tutor_nome, clientes.telefones')
            ->join('pacientes', 'pacientes.paciente_id = fat_cobrancas.paciente_id')
            ->join('clientes', 'clientes.id = pacientes.cliente_id')
            ->where('fat_cobrancas.id', $id)
            ->first();
    }
}
