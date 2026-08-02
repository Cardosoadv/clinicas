<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PesoModel;

class PesosRepository extends BaseRepository
{
    /**
     * @param PesoModel|null $model
     */
    public function __construct(?PesoModel $model = null)
    {
        $this->model = $model ?? new PesoModel();
    }

    /**
     * Busca o histórico de pesos de um pet ordenado pela data da pesagem.
     *
     * @param int $petId
     * @return array
     */
    public function findByPet(int $petId): array
    {
        return $this->model
            ->where('paciente_id', $petId)
            ->orderBy('data_pesagem', 'DESC')
            ->findAll();
    }

    /**
     * Busca os pesos ordenados de forma ascendente para geração de gráfico.
     *
     * @param int $petId
     * @return array
     */
    public function findByPetForChart(int $petId): array
    {
        return $this->model
            ->where('paciente_id', $petId)
            ->orderBy('data_pesagem', 'ASC')
            ->findAll();
    }
}
