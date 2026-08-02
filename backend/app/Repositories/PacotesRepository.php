<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PacoteModel;

class PacotesRepository extends BaseRepository
{
    /**
     * @param PacoteModel|null $model
     */
    public function __construct(?PacoteModel $model = null)
    {
        $this->model = $model ?? new PacoteModel();
    }

    /**
     * Busca pacotes ativos de um pet.
     *
     * @param int $petId
     * @return array
     */
    public function getAtivosPorPet(int $petId): array
    {
        return $this->model
            ->where('paciente_id', $petId)
            ->where('status', 'Ativo')
            ->findAll();
    }

    /**
     * Busca todos os pacotes com dados do pet.
     *
     * @param array $filters
     * @return array
     */
    public function getAllWithPet(array $filters = []): array
    {
        $builder = $this->model
            ->select('pacotes.*, pacientes.paciente_nome, pacientes.paciente_avatar')
            ->join('pacientes', 'pacientes.paciente_id = pacotes.paciente_id');

        if (!empty($filters['paciente_id'])) {
            $builder->where('pacotes.paciente_id', $filters['paciente_id']);
        }

        if (!empty($filters['status'])) {
            $builder->where('pacotes.status', $filters['status']);
        }

        return $builder->orderBy('pacotes.created_at', 'DESC')->findAll();
    }

    /**
     * Retorna detalhes completos de um pacote
     *
     * @param int $id
     * @return array|null
     */
    public function getWithDetails(int $id): ?array
    {
        $db = \Config\Database::connect();

        $pacote = $db->table('pacotes p')
            ->select('p.*, pacientes.paciente_nome, pacientes.paciente_id, pacientes.paciente_avatar,
                      fc.id AS cobranca_id, fc.valor, fc.desconto, fc.forma_pagamento, fc.vencimento, fc.updated_at AS data_pagamento')
            ->join('pacientes', 'pacientes.paciente_id = p.paciente_id')
            ->join('fat_cobrancas fc', 'fc.pacote_id = p.id', 'left')
            ->where('p.id', $id)
            ->get()->getRowArray();

        if (!$pacote) return null;

        $pacote['itens'] = $db->table('pacote_itens pi')
            ->select('pi.*, servicos.ser_nome AS servico_nome')
            ->join('servicos', 'servicos.ser_id = pi.servico_id', 'left')
            ->where('pi.pacote_id', $id)
            ->get()->getResultArray();

        $pacote['historico'] = $db->table('pacote_uso pu')
            ->select('pu.*, servicos.ser_nome AS servico_nome')
            ->join('servicos', 'servicos.ser_id = pu.servico_id', 'left')
            ->where('pu.pacote_id', $id)
            ->orderBy('pu.data_uso', 'DESC')
            ->get()->getResultArray();

        return $pacote;
    }
}
