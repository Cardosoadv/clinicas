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
        $prescricao['veterinario_crmv'] = $vet['equ_crmv'] ?? null;

        $pet = $db->table('pacientes')->where('paciente_id', $prescricao['paciente_id'])->get()->getRowArray();
        $prescricao['pet_id'] = $prescricao['paciente_id'];
        $prescricao['pet_nome'] = $pet['paciente_nome'] ?? 'Desconhecido';
        $prescricao['pet_especie'] = $pet['paciente_especie'] ?? null;
        $prescricao['pet_raca'] = $pet['paciente_raca'] ?? null;
        $prescricao['pet_sexo'] = $pet['paciente_sexo'] ?? null;
        $prescricao['pet_nascimento'] = $pet['paciente_nascimento'] ?? null;

        $prescricao['tutor_nome'] = 'Desconhecido';
        $prescricao['tutor_endereco'] = null;

        if ($pet && !empty($pet['cliente_id'])) {
            $tutor = $db->table('clientes')->where('id', $pet['cliente_id'])->get()->getRowArray();
            $prescricao['tutor_nome'] = $tutor['nome'] ?? 'Desconhecido';

            if ($tutor) {
                $rua = trim(implode(', ', array_filter([$tutor['rua'] ?? null, $tutor['numero'] ?? null, $tutor['complemento'] ?? null])));
                $cidade = trim(implode('/', array_filter([$tutor['cidade'] ?? null, $tutor['estado'] ?? null])));
                $endereco = implode(' - ', array_filter([$rua, $tutor['bairro'] ?? null, $cidade]));
                $prescricao['tutor_endereco'] = $endereco !== '' ? $endereco : null;
            }
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
