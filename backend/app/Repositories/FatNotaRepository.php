<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\FatNotaModel;

class FatNotaRepository extends BaseRepository
{
    /**
     * @param FatNotaModel|null $model
     */
    public function __construct(?FatNotaModel $model = null)
    {
        $this->model = $model ?? new FatNotaModel();
    }

    /**
     * Busca notas recentes
     *
     * @param int $limit
     * @return array
     */
    public function getRecent(int $limit = 5): array
    {
        return $this->model
            ->select('fat_notas.*, pacientes.paciente_nome')
            ->join('pacientes', 'pacientes.paciente_id = fat_notas.paciente_id')
            ->orderBy('fat_notas.created_at', 'DESC')
            ->findAll($limit);
    }
}
