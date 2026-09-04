<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AgendamentosModel;

class AgendamentosRepository extends BaseRepository
{
    /**
     * Busca um agendamento por ID com os nomes dos serviços concatenados.
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        return $this->model
            ->select('agendamentos.*')
            ->select("GROUP_CONCAT(CONCAT(IFNULL(servicos.ser_icone, '🐾'), ' ', servicos.ser_nome) SEPARATOR ', ') as age_servico")
            ->join('agendamento_servicos', 'agendamento_servicos.age_id = agendamentos.age_id', 'left')
            ->join('servicos', 'servicos.ser_id = agendamento_servicos.ser_id', 'left')
            ->where('agendamentos.age_id', $id)
            ->groupBy('agendamentos.age_id')
            ->first();
    }

    /**
     * @param AgendamentosModel|null $model
     */
    public function __construct(?AgendamentosModel $model = null)
    {
        $this->model = $model ?? new AgendamentosModel();
    }

    /**
     * Helper para formatar o endereço completo a partir do paciente ou do tutor
     *
     * @param array $row
     * @return string|null
     */
    public function formatEndereco(array $row): ?string
    {
        if (!empty($row['paciente_endereco'])) {
            return trim((string) $row['paciente_endereco']);
        }

        $parts = [];
        $rua = $row['tutor_rua'] ?? $row['rua'] ?? null;
        if (!empty($rua)) {
            $numero = $row['tutor_numero'] ?? $row['numero'] ?? null;
            $complemento = $row['tutor_complemento'] ?? $row['complemento'] ?? null;
            $line = $rua;
            if (!empty($numero)) {
                $line .= ', ' . $numero;
            }
            if (!empty($complemento)) {
                $line .= ' - ' . $complemento;
            }
            $parts[] = $line;
        }

        $bairro = $row['tutor_bairro'] ?? $row['bairro'] ?? null;
        if (!empty($bairro)) {
            $parts[] = 'Bairro ' . $bairro;
        }

        $cidade = $row['tutor_cidade'] ?? $row['cidade'] ?? null;
        $estado = $row['tutor_estado'] ?? $row['estado'] ?? null;
        $cidadeEstado = array_filter([$cidade, $estado]);
        if (!empty($cidadeEstado)) {
            $parts[] = implode('/', $cidadeEstado);
        }

        $cep = $row['tutor_cep'] ?? $row['cep'] ?? null;
        if (!empty($cep)) {
            $parts[] = 'CEP: ' . $cep;
        }

        return !empty($parts) ? implode(' · ', $parts) : null;
    }

    /**
     * Search for appointments based on a term.
     *
     * @param string $term
     * @return array
     */
    public function search(string $term): array
    {
        $results = $this->model
            ->select('agendamentos.*, pacientes.paciente_nome, pacientes.paciente_avatar, pacientes.paciente_endereco, clientes.nome as tutor_nome, clientes.rua as tutor_rua, clientes.numero as tutor_numero, clientes.complemento as tutor_complemento, clientes.bairro as tutor_bairro, clientes.cidade as tutor_cidade, clientes.estado as tutor_estado, clientes.cep as tutor_cep')
            ->select("GROUP_CONCAT(CONCAT(IFNULL(servicos.ser_icone, '🐾'), ' ', servicos.ser_nome) SEPARATOR ', ') as age_servico")
            ->select("GROUP_CONCAT(servicos.ser_id) as age_servico_ids")
            ->join('pacientes', 'pacientes.paciente_id = agendamentos.paciente_id')
            ->join('clientes', 'clientes.id = pacientes.cliente_id', 'left')
            ->join('agendamento_servicos', 'agendamento_servicos.age_id = agendamentos.age_id', 'left')
            ->join('servicos', 'servicos.ser_id = agendamento_servicos.ser_id', 'left')
            ->groupStart()
                ->like('pacientes.paciente_nome', $term)
                ->orLike('servicos.ser_nome', $term)
            ->groupEnd()
            ->groupBy('agendamentos.age_id')
            ->findAll(20);

        foreach ($results as &$item) {
            $item['paciente_endereco'] = $this->formatEndereco($item);
        }

        return $results;
    }

    /**
     * Get appointments for a specific date
     *
     * @param string $date
     * @return array
     */
    public function getByDate(string $date): array
    {
        $results = $this->model
            ->select('agendamentos.*, pacientes.paciente_nome, pacientes.paciente_avatar, pacientes.paciente_sexo, pacientes.paciente_endereco, clientes.nome as tutor_nome, clientes.rua as tutor_rua, clientes.numero as tutor_numero, clientes.complemento as tutor_complemento, clientes.bairro as tutor_bairro, clientes.cidade as tutor_cidade, clientes.estado as tutor_estado, clientes.cep as tutor_cep')
            ->select("GROUP_CONCAT(CONCAT(IFNULL(servicos.ser_icone, '🐾'), ' ', servicos.ser_nome) SEPARATOR ', ') as age_servico")
            ->select("GROUP_CONCAT(servicos.ser_id) as age_servico_ids")
            ->join('pacientes', 'pacientes.paciente_id = agendamentos.paciente_id')
            ->join('clientes', 'clientes.id = pacientes.cliente_id', 'left')
            ->join('agendamento_servicos', 'agendamento_servicos.age_id = agendamentos.age_id', 'left')
            ->join('servicos', 'servicos.ser_id = agendamento_servicos.ser_id', 'left')
            ->where('age_data', $date)
            ->groupBy('agendamentos.age_id')
            ->orderBy('age_hora', 'ASC')
            ->findAll();

        foreach ($results as &$item) {
            $item['paciente_endereco'] = $this->formatEndereco($item);
        }

        return $results;
    }

    /**
     * Get upcoming appointments for the next X hours
     *
     * @param int $hours
     * @return array
     */
    public function getUpcoming(int $hours = 48): array
    {
        $now = date('Y-m-d');
        $end = date('Y-m-d', strtotime("+$hours hours"));

        $results = $this->model
            ->select('agendamentos.*, pacientes.paciente_nome, pacientes.paciente_avatar, pacientes.paciente_endereco, clientes.nome as tutor_nome, clientes.rua as tutor_rua, clientes.numero as tutor_numero, clientes.complemento as tutor_complemento, clientes.bairro as tutor_bairro, clientes.cidade as tutor_cidade, clientes.estado as tutor_estado, clientes.cep as tutor_cep')
            ->select("GROUP_CONCAT(CONCAT(IFNULL(servicos.ser_icone, '🐾'), ' ', servicos.ser_nome) SEPARATOR ', ') as age_servico")
            ->select("GROUP_CONCAT(servicos.ser_id) as age_servico_ids")
            ->join('pacientes', 'pacientes.paciente_id = agendamentos.paciente_id')
            ->join('clientes', 'clientes.id = pacientes.cliente_id', 'left')
            ->join('agendamento_servicos', 'agendamento_servicos.age_id = agendamentos.age_id', 'left')
            ->join('servicos', 'servicos.ser_id = agendamento_servicos.ser_id', 'left')
            ->where('age_data >=', $now)
            ->where('age_data <=', $end)
            ->groupBy('agendamentos.age_id')
            ->orderBy('age_data', 'ASC')
            ->orderBy('age_hora', 'ASC')
            ->findAll(10);

        foreach ($results as &$item) {
            $item['paciente_endereco'] = $this->formatEndereco($item);
        }

        return $results;
    }

    /**
     * Lista agendamentos aplicando filtros opcionais (status, período, serviço,
     * veterinário responsável e termo de busca livre por paciente/tutor/serviço).
     *
     * @param array{status?: string, data_inicio?: string, data_fim?: string, servico_id?: int|string, veterinario_id?: int|string, search?: string} $filters
     * @return array
     */
    public function getFiltered(array $filters = []): array
    {
        $builder = $this->model
            ->select('agendamentos.*, pacientes.paciente_nome, pacientes.paciente_avatar, pacientes.paciente_sexo, pacientes.paciente_endereco, clientes.nome as tutor_nome, clientes.rua as tutor_rua, clientes.numero as tutor_numero, clientes.complemento as tutor_complemento, clientes.bairro as tutor_bairro, clientes.cidade as tutor_cidade, clientes.estado as tutor_estado, clientes.cep as tutor_cep')
            ->select("GROUP_CONCAT(CONCAT(IFNULL(servicos.ser_icone, '🐾'), ' ', servicos.ser_nome) SEPARATOR ', ') as age_servico")
            ->select("GROUP_CONCAT(servicos.ser_id) as age_servico_ids")
            ->join('pacientes', 'pacientes.paciente_id = agendamentos.paciente_id')
            ->join('clientes', 'clientes.id = pacientes.cliente_id', 'left')
            ->join('agendamento_servicos', 'agendamento_servicos.age_id = agendamentos.age_id', 'left')
            ->join('servicos', 'servicos.ser_id = agendamento_servicos.ser_id', 'left');

        if (!empty($filters['status'])) {
            $builder->where('agendamentos.age_status', $filters['status']);
        }

        if (!empty($filters['data_inicio'])) {
            $builder->where('agendamentos.age_data >=', $filters['data_inicio']);
        }

        if (!empty($filters['data_fim'])) {
            $builder->where('agendamentos.age_data <=', $filters['data_fim']);
        }

        if (!empty($filters['veterinario_id'])) {
            $builder->where('agendamentos.age_veterinario', (int) $filters['veterinario_id']);
        }

        if (!empty($filters['servico_id'])) {
            $servicoId = (int) $filters['servico_id'];
            $builder->where("agendamentos.age_id IN (SELECT age_id FROM agendamento_servicos WHERE ser_id = {$servicoId})", null, false);
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $builder->groupStart()
                ->like('pacientes.paciente_nome', $term)
                ->orLike('clientes.nome', $term)
                ->orLike('servicos.ser_nome', $term)
            ->groupEnd();
        }

        $results = $builder
            ->groupBy('agendamentos.age_id')
            ->orderBy('agendamentos.age_data', 'DESC')
            ->orderBy('agendamentos.age_hora', 'DESC')
            ->findAll(300);

        foreach ($results as &$item) {
            $item['paciente_endereco'] = $this->formatEndereco($item);
        }

        return $results;
    }

    /**
     * Get agenda stats.
     *
     * Note: We use separate count queries here to leverage indexes on
     * 'age_data' and 'age_status', avoiding a full table scan that
     * conditional aggregation would require.
     *
     * @return array
     */
    public function getStats(): array
    {
        $today = date('Y-m-d');

        $hoje      = $this->model->where('age_data', $today)->countAllResults();
        $pendentes = $this->model->where('age_status', 'pendente')->countAllResults();

        return [
            'hoje'      => $hoje,
            'pendentes' => $pendentes
        ];
    }

    /**
     * Get counts for each service type for the month
     *
     * @param string $month
     * @param string $year
     * @return array
     */
    public function getServiceStats(int|string $month, int|string $year): array
    {
        $month = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
        $start = "{$year}-{$month}-01";
        $end = date('Y-m-t', strtotime($start));

        return $this->model
            ->select('agendamento_servicos.ser_id as service_id, CONCAT(IFNULL(servicos.ser_icone, \'🐾\'), \' \', servicos.ser_nome) as service, COUNT(agendamento_servicos.id) as count')
            ->join('agendamento_servicos', 'agendamento_servicos.age_id = agendamentos.age_id')
            ->join('servicos', 'servicos.ser_id = agendamento_servicos.ser_id', 'left')
            ->where('age_data >=', $start)
            ->where('age_data <=', $end)
            ->groupBy('agendamento_servicos.ser_id')
            ->orderBy('count', 'DESC')
            ->findAll(5);
    }

    /**
     * Get unique days with appointments for a given month
     *
     * @param string $year
     * @param string $month
     * @return array
     */
    public function getDaysWithAppts(int|string $year, int|string $month): array
    {
        $month = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
        $start = "{$year}-{$month}-01";
        $end = date('Y-m-t', strtotime($start));
        return $this->model
            ->select('DISTINCT(age_data)')
            ->where('age_data >=', $start)
            ->where('age_data <=', $end)
            ->findAll();
    }

    /**
     * Busca todos os agendamentos de um pet, incluindo os nomes dos serviços.
     *
     * @param int $petId
     * @return array
     */
    public function findByPet(int $petId): array
    {
        return $this->model
            ->select('agendamentos.*')
            ->select("GROUP_CONCAT(CONCAT(IFNULL(servicos.ser_icone, '🐾'), ' ', servicos.ser_nome) SEPARATOR ', ') as age_servico")
            ->join('agendamento_servicos', 'agendamento_servicos.age_id = agendamentos.age_id', 'left')
            ->join('servicos', 'servicos.ser_id = agendamento_servicos.ser_id', 'left')
            ->where('paciente_id', $petId)
            ->groupBy('agendamentos.age_id')
            ->orderBy('age_data', 'DESC')
            ->findAll();
    }

    /**
     * Busca detalhes de um agendamento com dados do pet e tutor.
     *
     * @param int $id
     * @return array|null
     */
    public function findWithTutor(int $id): ?array
    {
        return $this->model
            ->select('agendamentos.*, pacientes.paciente_nome, clientes.id as cliente_id, clientes.nome as tutor_nome, clientes.telefones')
            ->select("GROUP_CONCAT(CONCAT(IFNULL(servicos.ser_icone, '🐾'), ' ', servicos.ser_nome) SEPARATOR ', ') as age_servico")
            ->join('pacientes', 'pacientes.paciente_id = agendamentos.paciente_id')
            ->join('clientes', 'clientes.id = pacientes.cliente_id')
            ->join('agendamento_servicos', 'agendamento_servicos.age_id = agendamentos.age_id', 'left')
            ->join('servicos', 'servicos.ser_id = agendamento_servicos.ser_id', 'left')
            ->where('agendamentos.age_id', $id)
            ->groupBy('agendamentos.age_id')
            ->first();
    }

    /**
     * Retorna agendamentos concluídos nas últimas 48 horas para pós-consulta.
     *
     * @return array
     */
    public function getPostCareCandidates(): array
    {
        $twoDaysAgo = date('Y-m-d', strtotime('-2 days'));
        $today = date('Y-m-d');

        return $this->model
            ->select('agendamentos.*, pacientes.paciente_nome, pacientes.paciente_avatar')
            ->select("GROUP_CONCAT(CONCAT(IFNULL(servicos.ser_icone, '🐾'), ' ', servicos.ser_nome) SEPARATOR ', ') as age_servico")
            ->join('pacientes', 'pacientes.paciente_id = agendamentos.paciente_id')
            ->join('agendamento_servicos', 'agendamento_servicos.age_id = agendamentos.age_id', 'left')
            ->join('servicos', 'servicos.ser_id = agendamento_servicos.ser_id', 'left')
            ->where('age_status', 'concluido')
            ->where('age_data >=', $twoDaysAgo)
            ->where('age_data <=', $today)
            ->groupBy('agendamentos.age_id')
            ->orderBy('age_data', 'DESC')
            ->findAll(5);
    }

    /**
     * Sync services for an appointment
     *
     * @param int $ageId
     * @param array $serviceIds
     * @return void
     */
    public function syncServices(int $ageId, array $serviceIds): void
    {
        $db = \Config\Database::connect();
        $db->table('agendamento_servicos')->where('age_id', $ageId)->delete();

        if (!empty($serviceIds)) {
            $data = [];
            foreach ($serviceIds as $serId) {
                if (!empty($serId)) {
                    $data[] = [
                        'age_id' => $ageId,
                        'ser_id' => (int) $serId,
                    ];
                }
            }
            if (!empty($data)) {
                $db->table('agendamento_servicos')->insertBatch($data);
            }
        }
    }

    /**
     * Get service IDs for an appointment
     *
     * @param int $ageId
     * @return array
     */
    public function getServiceIds(int $ageId): array
    {
        $db = \Config\Database::connect();
        $results = $db->table('agendamento_servicos')
            ->select('ser_id')
            ->where('age_id', $ageId)
            ->get()
            ->getResultArray();

        return array_column($results, 'ser_id');
    }
}
