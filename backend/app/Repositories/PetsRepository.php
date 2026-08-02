<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PetsModel;

class PetsRepository extends BaseRepository
{
    /**
     * @param PetsModel|null $model
     */
    public function __construct(?PetsModel $model = null)
    {
        $this->model = $model ?? new PetsModel();
    }

    /**
     * Busca todos os pets com as informações do cliente e último agendamento.
     *
     * @return array
     */
    public function getAllWithLastAppointment(): array
    {
        return $this->model
            ->select('pacientes.paciente_id, pacientes.paciente_nome, pacientes.paciente_nascimento, pacientes.paciente_sexo, pacientes.paciente_status, pacientes.paciente_avatar')
            ->select('clientes.nome as pet_resp_nome, clientes.telefones as pet_resp_tel')
            ->select('last_age.age_servico, last_age.age_data, last_age.age_hora')
            ->select('COALESCE(counts.total_consultas, 0) as total_consultas')
            ->join('clientes', 'clientes.id = pacientes.cliente_id', 'left')
            ->join('(SELECT a.paciente_id, GROUP_CONCAT(CONCAT(IFNULL(s.ser_icone, \'🐾\'), \' \', s.ser_nome) SEPARATOR \', \') as age_servico, a.age_data, a.age_hora FROM agendamentos a
                    LEFT JOIN agendamento_servicos as ASV ON ASV.age_id = a.age_id
                    LEFT JOIN servicos s ON s.ser_id = ASV.ser_id
                    WHERE a.age_id IN (SELECT MAX(age_id) FROM agendamentos GROUP BY paciente_id)
                    GROUP BY a.age_id) as last_age', 'last_age.paciente_id = pacientes.paciente_id', 'left')
            ->join('(SELECT paciente_id, COUNT(age_id) as total_consultas FROM agendamentos GROUP BY paciente_id) as counts', 'counts.paciente_id = pacientes.paciente_id', 'left')
            ->orderBy('pacientes.paciente_id', 'DESC')
            ->get()->getResultArray();
    }
    /**
     * Busca todos os registros do Pets com os dados do cliente (tutor) associados.
     */
    public function findAllWithClient(int $limit = 0): array
    {
        return $this->model
            ->select('pacientes.*, clientes.nome as pet_resp_nome, clientes.telefones as pet_resp_tel, clientes.cpf as pet_resp_cpf')
            ->join('clientes', 'clientes.id = pacientes.cliente_id', 'left')
            ->orderBy('pacientes.paciente_id', 'DESC')
            ->findAll($limit);
    }

    /**
     * Busca pets por um termo (nome, CPF dono, etc).
     *
     * @param string $term
     * @return array
     */
    public function search(string $term): array
    {
        return $this->model
            ->select('pacientes.*, clientes.nome as pet_resp_nome, clientes.telefones as pet_resp_tel, clientes.cpf as pet_resp_cpf')
            ->join('clientes', 'clientes.id = pacientes.cliente_id', 'left')
            ->groupStart()
                ->like('paciente_nome', $term)
                ->orLike('clientes.nome', $term)
                ->orLike('clientes.cpf', $term)
            ->groupEnd()
            ->findAll(20);
    }

    /**
     * Optimized search for lookups/autocomplete
     *
     * @param string|null $term
     * @return array
     */
    public function findForLookup(?string $term = null): array
    {
        $query = $this->model->select('pacientes.paciente_id, pacientes.paciente_nome, pacientes.paciente_avatar, clientes.nome as pet_resp_nome, clientes.telefones as pet_resp_tel')
            ->join('clientes', 'clientes.id = pacientes.cliente_id', 'left');

        if ($term) {
            $query->groupStart()
                ->like('paciente_nome', $term)
                ->orLike('clientes.nome', $term)
                ->orLike('clientes.cpf', $term)
            ->groupEnd();
        }

        return $query->findAll(100);
    }

    /**
     * Get recently registered pets
     *
     * @param int $limit
     * @return array
     */
    public function getRecent(int $limit = 3): array
    {
        return $this->model
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }

    /**
     * Busca um pet por ID, incluindo os dados do tutor (cliente).
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        return $this->model
            ->select('pacientes.*, clientes.nome as pet_resp_nome, clientes.telefones as pet_resp_tel, clientes.cpf as pet_resp_cpf')
            ->join('clientes', 'clientes.id = pacientes.cliente_id', 'left')
            ->where('pacientes.paciente_id', $id)
            ->first();
    }

    /**
     * Lista todos os pacientes vinculados a um cliente (tutor).
     *
     * @param int $clienteId
     * @return array
     */
    public function findByCliente(int $clienteId): array
    {
        return $this->model
            ->where('cliente_id', $clienteId)
            ->orderBy('paciente_nome', 'ASC')
            ->findAll();
    }

    /**
     * Find a pet by name and owner ID to check for duplicates.
     *
     * @param string $nome
     * @param int $clienteId
     * @return array|null
     */
    public function findByNomeAndCliente(string $nome, int $clienteId): ?array
    {
        return $this->model->where('paciente_nome', $nome)
                           ->where('cliente_id', $clienteId)
                           ->first();
    }

    /**
     * Busca os aniversariantes do mês.
     */
    public function getBirthdaysByMonth(int $month): array
    {
        return $this->model
            ->select('pacientes.paciente_id, pacientes.paciente_nome, pacientes.paciente_nascimento, pacientes.paciente_avatar')
            ->select('clientes.nome as pet_resp_nome, clientes.telefones as pet_resp_tel')
            ->join('clientes', 'clientes.id = pacientes.cliente_id', 'left')
            ->where('MONTH(paciente_nascimento)', $month)
            ->orderBy('DAY(paciente_nascimento)', 'ASC')
            ->findAll();
    }

    /**
     * Lista pacientes com dados do tutor, filtrando por status, espécie e um termo de busca livre.
     *
     * @param string $status 'Ativo', 'Inativo', 'Suspenso' ou '' (todos)
     * @param string|null $especie
     * @param string|null $term
     * @return array
     */
    public function getFiltered(string $status = '', ?string $especie = null, ?string $term = null): array
    {
        $builder = $this->model
            ->select('pacientes.*, clientes.nome as pet_resp_nome, clientes.telefones as pet_resp_tel, clientes.cpf as pet_resp_cpf')
            ->join('clientes', 'clientes.id = pacientes.cliente_id', 'left');

        if ($status !== '') {
            $builder = $builder->where('pacientes.paciente_status', $status);
        }

        if (!empty($especie)) {
            $builder = $builder->where('pacientes.paciente_especie', $especie);
        }

        if (!empty($term)) {
            $builder = $builder->groupStart()
                ->like('pacientes.paciente_nome', $term)
                ->orLike('clientes.nome', $term)
                ->orLike('clientes.cpf', $term)
            ->groupEnd();
        }

        return $builder->orderBy('pacientes.paciente_nome', 'ASC')->findAll();
    }

    /**
     * Totalizadores usados nos cartões de estatística da tela de pacientes.
     *
     * @return array{total: int, ativos: int, inativos: int, suspensos: int}
     */
    public function getStats(): array
    {
        return [
            'total'     => $this->model->countAllResults(),
            'ativos'    => $this->model->where('paciente_status', 'Ativo')->countAllResults(),
            'inativos'  => $this->model->where('paciente_status', 'Inativo')->countAllResults(),
            'suspensos' => $this->model->where('paciente_status', 'Suspenso')->countAllResults(),
        ];
    }

    /**
     * Espécies distintas já cadastradas, para alimentar o filtro da tela.
     *
     * @return list<string>
     */
    public function getEspecies(): array
    {
        $rows = $this->model
            ->select('paciente_especie')
            ->where('paciente_especie IS NOT NULL', null, false)
            ->where('paciente_especie !=', '')
            ->distinct()
            ->orderBy('paciente_especie', 'ASC')
            ->findAll();

        return array_column($rows, 'paciente_especie');
    }
}
