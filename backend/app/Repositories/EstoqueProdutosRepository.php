<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EstoqueProdutosModel;

class EstoqueProdutosRepository extends BaseRepository
{
    /**
     * @param EstoqueProdutosModel|null $model
     */
    public function __construct(?EstoqueProdutosModel $model = null)
    {
        $this->model = $model ?? new EstoqueProdutosModel();
    }

    /**
     * Busca alertas de estoque baixo
     *
     * @return array
     */
    public function getLowStockAlerts(): array
    {
        return $this->model->where('quantidade_atual <= estoque_minimo')->findAll();
    }
}
