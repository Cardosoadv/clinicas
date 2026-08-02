<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ImagensModel;

class ImagensRepository extends BaseRepository
{
    /**
     * @param ImagensModel|null $model
     */
    public function __construct(?ImagensModel $model = null)
    {
        $this->model = $model ?? new ImagensModel();
    }

    /**
     * Busca todas as imagens registradas de um pet específico.
     * 
     * @param int $petId
     * @return array
     */
    public function findByPet(int $petId): array
    {
        return $this->model
            ->where('paciente_id', $petId)
            ->orderBy('data_exame', 'DESC')
            ->findAll();
    }
}
