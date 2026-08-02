<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ClienteNotaModel;

/**
 * Repositório para as notas internas registradas sobre um cliente.
 */
class ClienteNotaRepository extends BaseRepository
{
    /**
     * @param ClienteNotaModel|null $model
     */
    public function __construct(?ClienteNotaModel $model = null)
    {
        $this->model = $model ?? new ClienteNotaModel();
    }

    /**
     * Lista as notas de um cliente, mais recentes primeiro.
     *
     * @param int $clienteId
     * @return array
     */
    public function findByCliente(int $clienteId): array
    {
        return $this->model
            ->where('cliente_id', $clienteId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
