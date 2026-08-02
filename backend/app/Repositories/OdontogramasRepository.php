<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\OdontogramasModel;

class OdontogramasRepository extends BaseRepository
{
    /**
     * @param OdontogramasModel|null $model
     */
    public function __construct(?OdontogramasModel $model = null)
    {
        $this->model = $model ?? new OdontogramasModel();
    }

    /**
     * Busca o odontograma mais recente de um pet
     *
     * @param int $petId
     * @return array|null
     */
    public function findLatestByPet(int $petId): ?array
    {
        return $this->model
            ->where('paciente_id', $petId)
            ->orderBy('data_registro', 'DESC')
            ->first();
    }

    /**
     * Busca o histórico de odontogramas de um pet
     *
     * @param int $petId
     * @return array
     */
    public function findHistoryByPet(int $petId): array
    {
        return $this->model
            ->where('paciente_id', $petId)
            ->orderBy('data_registro', 'DESC')
            ->findAll();
    }
}
