<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PacoteItemModel;

class PacoteItensRepository extends BaseRepository
{
    /**
     * @param PacoteItemModel|null $model
     */
    public function __construct(?PacoteItemModel $model = null)
    {
        $this->model = $model ?? new PacoteItemModel();
    }

    /**
     * Busca itens de um pacote
     *
     * @param int $pacoteId
     * @return array
     */
    public function getPorPacote(int $pacoteId): array
    {
        return $this->model
            ->select('pacote_itens.*, servicos.ser_nome')
            ->join('servicos', 'servicos.ser_id = pacote_itens.servico_id', 'left')
            ->where('pacote_id', $pacoteId)
            ->findAll();
    }

    /**
     * Busca itens de múltiplos pacotes em uma única query (Otimização N+1)
     *
     * @param array $pacoteIds
     * @return array
     */
    public function getPorPacotes(array $pacoteIds): array
    {
        if (empty($pacoteIds)) {
            return [];
        }

        return $this->model
            ->select('pacote_itens.*, servicos.ser_nome')
            ->join('servicos', 'servicos.ser_id = pacote_itens.servico_id', 'left')
            ->whereIn('pacote_id', $pacoteIds)
            ->findAll();
    }

    /**
     * Remove todos os itens de um pacote.
     *
     * @param int $pacoteId
     * @return void
     */
    public function deleteByPacote(int $pacoteId): void
    {
        $this->model->where('pacote_id', $pacoteId)->delete();
    }

    /**
     * Substitui os itens de um pacote. Usado apenas enquanto o pacote ainda
     * não possui uso registrado (ex.: status "Pendente"), já que recria os
     * registros e zera a quantidade usada.
     *
     * @param int $pacoteId
     * @param array $itens Array de ['servico_id', 'item_nome', 'quantidade', 'valor_unitario']
     * @return void
     */
    public function sync(int $pacoteId, array $itens): void
    {
        $this->deleteByPacote($pacoteId);

        $data = [];
        foreach ($itens as $item) {
            if (empty($item['servico_id']) && empty($item['item_nome'])) {
                continue;
            }

            $data[] = [
                'pacote_id'        => $pacoteId,
                'servico_id'       => $item['servico_id'] ?? null,
                'item_nome'        => $item['item_nome'] ?? null,
                'quantidade_total' => $item['quantidade'] ?? 1,
                'quantidade_usada' => 0,
                'valor_unitario'   => $item['valor_unitario'] ?? 0,
            ];
        }

        if (!empty($data)) {
            $this->model->insertBatch($data);
        }
    }
}
