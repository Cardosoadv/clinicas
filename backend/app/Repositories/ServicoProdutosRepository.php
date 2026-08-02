<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ServicoProdutoModel;

class ServicoProdutosRepository extends BaseRepository
{
    /**
     * @param ServicoProdutoModel|null $model
     */
    public function __construct(?ServicoProdutoModel $model = null)
    {
        $this->model = $model ?? new ServicoProdutoModel();
    }

    /**
     * Busca todos os produtos vinculados a um serviço.
     *
     * @param int $servicoId
     * @return array
     */
    public function findByServico(int $servicoId): array
    {
        return $this->model
            ->select('servico_produtos.*, estoque_produtos.nome as produto_nome, estoque_produtos.quantidade_atual')
            ->join('estoque_produtos', 'estoque_produtos.id = servico_produtos.produto_id')
            ->where('ser_id', $servicoId)
            ->findAll();
    }

    /**
     * Sincroniza os produtos de um serviço.
     *
     * @param int $servicoId
     * @param array $produtos Array de ['produto_id' => X, 'quantidade' => Y]
     * @return void
     */
    public function sync(int $servicoId, array $produtos): void
    {
        $this->model->where('ser_id', $servicoId)->delete();

        if (!empty($produtos)) {
            $data = [];
            foreach ($produtos as $p) {
                if (!empty($p['produto_id'])) {
                    $data[] = [
                        'ser_id'     => $servicoId,
                        'produto_id' => (int) $p['produto_id'],
                        'quantidade' => (float) ($p['quantidade'] ?? 1.0)
                    ];
                }
            }
            if (!empty($data)) {
                $this->model->insertBatch($data);
            }
        }
    }
}
