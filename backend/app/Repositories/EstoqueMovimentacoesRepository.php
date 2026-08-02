<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EstoqueMovimentacoesModel;

class EstoqueMovimentacoesRepository extends BaseRepository
{
    /**
     * @param EstoqueMovimentacoesModel|null $model
     */
    public function __construct(?EstoqueMovimentacoesModel $model = null)
    {
        $this->model = $model ?? new EstoqueMovimentacoesModel();
    }

    /**
     * Busca todas as movimentações de um produto
     *
     * @param int|string $produto_id
     * @return array
     */
    public function getMovimentacoesByProduto($produto_id): array
    {
        return $this->model->where('produto_id', $produto_id)->orderBy('created_at', 'DESC')->findAll();
    }
}
