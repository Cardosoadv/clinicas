<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PrescricaoModel;
use App\Models\PrescricaoItemModel;

class PrescricoesRepository extends BaseRepository
{
    protected PrescricaoItemModel $itemModel;

    public function __construct(?PrescricaoModel $model = null, ?PrescricaoItemModel $itemModel = null)
    {
        $this->model = $model ?? new PrescricaoModel();
        $this->itemModel = $itemModel ?? new PrescricaoItemModel();
    }

    /**
     * Retorna todas as prescrições de um pet com os dados do veterinário.
     *
     * @param int $petId
     * @return array
     */
    public function findByPet(int $petId): array
    {
        return $this->model
            ->select('prescricoes.*, equipe.equ_nome as veterinario_nome')
            ->join('equipe', 'equipe.equ_id = prescricoes.veterinario_id', 'left')
            ->where('paciente_id', $petId)
            ->orderBy('data_prescricao', 'DESC')
            ->findAll();
    }

    /**
     * Retorna uma prescrição com todos os seus itens.
     *
     * @param int $id
     * @return array|null
     */
    public function getWithItems(int $id): ?array
    {
        $prescricao = $this->findById($id);
        if (!$prescricao) {
            return null;
        }

        $prescricao['itens'] = $this->itemModel->where('prescricao_id', $id)->findAll();

        // Veterinario info
        $db = \Config\Database::connect();
        $vet = $db->table('equipe')->where('equ_id', $prescricao['veterinario_id'])->get()->getRowArray();
        $prescricao['veterinario_nome'] = $vet['equ_nome'] ?? 'Clínico';

        $pet = $db->table('pacientes')->where('paciente_id', $prescricao['paciente_id'])->get()->getRowArray();
        $prescricao['pet_id'] = $prescricao['paciente_id'];
        $prescricao['pet_nome'] = $pet['paciente_nome'] ?? 'Desconhecido';

        if ($pet && !empty($pet['cliente_id'])) {
            $tutor = $db->table('clientes')->where('id', $pet['cliente_id'])->get()->getRowArray();
            $prescricao['tutor_nome'] = $tutor['nome'] ?? 'Desconhecido';
        } else {
            $prescricao['tutor_nome'] = 'Desconhecido';
        }

        return $prescricao;
    }

    /**
     * Cria um item de prescrição.
     *
     * @param array $data
     * @return int
     */
    public function createItem(array $data): int
    {
        return (int) $this->itemModel->insert($data);
    }
}
