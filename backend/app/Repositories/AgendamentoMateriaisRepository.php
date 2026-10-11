<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AgendamentoMaterialModel;

class AgendamentoMateriaisRepository extends BaseRepository
{
    /**
     * @param AgendamentoMaterialModel|null $model
     */
    public function __construct(?AgendamentoMaterialModel $model = null)
    {
        $this->model = $model ?? new AgendamentoMaterialModel();
    }

    /**
     * Busca os materiais lançados em um agendamento, com nome e unidade do produto.
     *
     * @param int $agendamentoId
     * @return array
     */
    public function findByAgendamento(int $agendamentoId): array
    {
        return $this->model
            ->select('agendamento_materiais.*, estoque_produtos.nome as produto_nome, estoque_produtos.unidade_medida')
            ->join('estoque_produtos', 'estoque_produtos.id = agendamento_materiais.produto_id')
            ->where('agendamento_id', $agendamentoId)
            ->orderBy('agendamento_materiais.id', 'ASC')
            ->findAll();
    }

    /**
     * Substitui os materiais de um agendamento.
     *
     * @param int $agendamentoId
     * @param array $materiais Array de ['produto_id' => X, 'quantidade' => Y, 'valor_unitario' => Z]
     * @return void
     */
    public function sync(int $agendamentoId, array $materiais): void
    {
        $this->model->where('agendamento_id', $agendamentoId)->delete();

        $data = [];
        foreach ($materiais as $m) {
            $data[] = [
                'agendamento_id' => $agendamentoId,
                'produto_id'     => (int) $m['produto_id'],
                'quantidade'     => (float) $m['quantidade'],
                'valor_unitario' => (float) $m['valor_unitario'],
            ];
        }
        if (!empty($data)) {
            $this->model->insertBatch($data);
        }
    }
}
