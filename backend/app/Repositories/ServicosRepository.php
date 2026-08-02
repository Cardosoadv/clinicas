<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ServicosModel;

class ServicosRepository extends BaseRepository
{
    /**
     * @param ServicosModel|null $model
     */
    public function __construct(?ServicosModel $model = null)
    {
        $this->model = $model ?? new ServicosModel();
    }

    /**
     * Busca todos os serviços ativos
     *
     * @return array
     */
    public function findAllActive(): array
    {
        return $this->model->where('ser_status', 'Ativo')->findAll();
    }
}
