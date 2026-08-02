<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EvolucoesModel;

class EvolucoesRepository extends BaseRepository
{
    /**
     * @param EvolucoesModel|null $model
     */
    public function __construct(?EvolucoesModel $model = null)
    {
        $this->model = $model ?? new EvolucoesModel();
    }

    /**
     * Busca as evoluções de um pet
     *
     * @param int $petId
     * @return array
     */
    public function findByPet(int $petId): array
    {
        return $this->model
            ->select('evolucoes.*, equipe.equ_nome as veterinario_nome')
            ->join('equipe', 'equipe.equ_id = evolucoes.veterinario_id', 'left')
            ->where('paciente_id', $petId)
            ->orderBy('data_registro', 'DESC')
            ->findAll();
    }
}
