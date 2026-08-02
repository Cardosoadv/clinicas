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
}
