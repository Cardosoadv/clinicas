<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\LojasModel;

class LojasRepository extends BaseRepository
{
    /**
     * @param LojasModel|null $model
     */
    public function __construct(?LojasModel $model = null)
    {
        $this->model = $model ?? new LojasModel();
    }

    /**
     * Retorna a loja usada como identidade visual do sistema (nome, endereço,
     * logo exibidos no topo/relatórios): a loja ativa mais antiga cadastrada.
     */
    public function findPrincipal(): ?array
    {
        return $this->model->where('status', 'Ativo')->orderBy('id', 'asc')->first();
    }
}
