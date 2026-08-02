<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PacoteUsoModel;

class PacoteUsoRepository extends BaseRepository
{
    /**
     * @param PacoteUsoModel|null $model
     */
    public function __construct(?PacoteUsoModel $model = null)
    {
        $this->model = $model ?? new PacoteUsoModel();
    }

    /**
     * Busca o histórico de uso de um pacote
     *
     * @param int $pacoteId
     * @return array
     */
    public function getPorPacote(int $pacoteId): array
    {
        return $this->model
            ->where('pacote_id', $pacoteId)
            ->orderBy('data_uso', 'DESC')
            ->findAll();
    }
}
