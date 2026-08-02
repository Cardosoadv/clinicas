<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ComunicacaoModel;

/**
 * Repositório para o histórico de comunicações (ex: mensagens de WhatsApp) enviadas a um cliente.
 */
class ComunicacaoRepository extends BaseRepository
{
    /**
     * @param ComunicacaoModel|null $model
     */
    public function __construct(?ComunicacaoModel $model = null)
    {
        $this->model = $model ?? new ComunicacaoModel();
    }

    /**
     * Lista as comunicações de um cliente, mais recentes primeiro.
     *
     * @param int $clienteId
     * @param int $limit
     * @return array
     */
    public function findByCliente(int $clienteId, int $limit = 20): array
    {
        return $this->model
            ->where('cliente_id', $clienteId)
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }
}
