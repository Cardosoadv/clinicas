<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AgendamentosRepository;
use App\Repositories\PetsRepository;

/**
 * @property AgendamentosRepository $repository
 */
class AgendaService extends BaseService
{
    protected AgendamentosRepository $agendamentosRepository;
    protected PetsRepository $petsRepository;
    protected FatService $fatService;
    protected PacoteService $pacoteService;

    /**
     * @param AgendamentosRepository|null $agendamentosRepository
     * @param PetsRepository|null $petsRepository
     * @param FatService|null $fatService
     * @param PacoteService|null $pacoteService
     */
    public function __construct(
        ?AgendamentosRepository $agendamentosRepository = null,
        ?PetsRepository $petsRepository = null,
        ?FatService $fatService = null,
        ?PacoteService $pacoteService = null
    ) {
        $this->agendamentosRepository   = $agendamentosRepository ?? new AgendamentosRepository();
        $this->repository               = $this->agendamentosRepository;
        $this->petsRepository           = $petsRepository ?? new PetsRepository();
        $this->fatService               = $fatService ?? new FatService();
        $this->pacoteService            = $pacoteService ?? new PacoteService();
    }

    /**
     * Retorna os dados para a agenda de um dia específico
     *
     * @param string $date
     * @return array
     */
    public function getDayData(string $date): array
    {
        return [
            'appointments' => $this->agendamentosRepository->getByDate($date),
            'stats' => $this->agendamentosRepository->getStats(),
            'date' => $date
        ];
    }

    /**
     * Retorna os próximos agendamentos
     *
     * @return array
     */
    public function getUpcoming(): array
    {
        return $this->agendamentosRepository->getUpcoming();
    }

    /**
     * Lista agendamentos com filtros opcionais (status, período, serviço, veterinário e busca).
     *
     * @param array $filters
     * @return array
     */
    public function listAppointments(array $filters): array
    {
        return $this->agendamentosRepository->getFiltered($filters);
    }

    /**
     * Busca pets para lookup
     *
     * @param string $term
     * @return array
     */
    public function searchPets(string $term): array
    {
        return $this->petsRepository->findForLookup($term);
    }

    /**
     * Retorna os dias que possuem agendamentos em um mês
     *
     * @param string $year
     * @param string $month
     * @return array
     */
    public function getMonthDaysWithAppts(string $year, string $month): array
    {
        return $this->agendamentosRepository->getDaysWithAppts($year, $month);
    }

    /**
     * Cria um novo agendamento com múltiplos serviços.
     *
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        $services = (array) ($data['age_servico'] ?? []);
        unset($data['age_servico']);

        $recorrencia = $data['age_recorrencia'] ?? 'nenhuma';

        if ($recorrencia === 'nenhuma') {
            $result = parent::create($data);
            if ($result['status'] === 'success' && !empty($services)) {
                $this->agendamentosRepository->syncServices((int) $result['id'], $services);
            }
            return $result;
        }

        // É recorrente
        try {
            $db = \Config\Database::connect();
            $db->transStart();

            $grupoId = bin2hex(random_bytes(16)); // UUID simplificado para agrupar as instâncias
            $data['age_grupo_id'] = $grupoId;

            // Define data limite: 1 ano por padrão se não informado
            $dataFimStr = !empty($data['age_recorrencia_fim']) ? $data['age_recorrencia_fim'] : date('Y-m-d', strtotime('+1 year'));
            $maxIterations = 52; // Máximo de 1 ano para recorrência semanal

            $dates = $this->buildRecurrenceDates($data['age_data'], $recorrencia, $dataFimStr, $maxIterations);

            $count = 0;
            $firstId = null;

            foreach ($dates as $currentDateStr) {
                $iterationData = $data;
                $iterationData['age_data'] = $currentDateStr;

                $id = $this->repository->create($iterationData);
                if (!$id) {
                    throw new \Exception("Erro ao criar instância da recorrência na data $currentDateStr");
                }

                if ($count === 0) {
                    $firstId = $id;
                }

                if (!empty($services)) {
                    $this->agendamentosRepository->syncServices((int) $id, $services);
                }

                $count++;
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->error("Erro ao processar série de agendamentos no banco de dados.");
            }

            $msg = $count > 1
                ? "Série de agendamentos criada com sucesso ($count ocorrências)."
                : "Agendamento criado com sucesso.";

            return $this->success($msg, ['id' => $firstId]);

        } catch (\Exception $e) {
            return $this->error("Erro ao criar recorrência: " . $e->getMessage());
        }
    }

    /**
     * Cria uma série de agendamentos recorrentes limitada por quantidade de
     * ocorrências (em vez de uma data-fim), agrupando-os pelo mesmo
     * `age_grupo_id`. Usado para pré-agendar as sessões de um pacote.
     *
     * @param array $data
     * @param int $quantidade
     * @return array
     */
    public function createRecorrenciaPorQuantidade(array $data, int $quantidade): array
    {
        $services = (array) ($data['age_servico'] ?? []);
        unset($data['age_servico']);

        if ($quantidade < 1) {
            return $this->error('Quantidade de ocorrências inválida.');
        }

        $recorrencia = $data['age_recorrencia'] ?? 'semanal';

        try {
            $db = \Config\Database::connect();
            $db->transStart();

            $grupoId = bin2hex(random_bytes(16));
            $data['age_grupo_id'] = $grupoId;

            // A quantidade de ocorrências manda; usamos uma data-fim bem distante
            // apenas como salvaguarda contra periodicidades inválidas.
            $limiteMaximo = date('Y-m-d', strtotime('+5 years', strtotime($data['age_data'])));
            $dates = $this->buildRecurrenceDates($data['age_data'], $recorrencia, $limiteMaximo, $quantidade);

            $ids = [];

            foreach ($dates as $currentDateStr) {
                $iterationData = $data;
                $iterationData['age_data'] = $currentDateStr;

                $id = $this->repository->create($iterationData);
                if (!$id) {
                    throw new \Exception("Erro ao criar instância da recorrência na data $currentDateStr");
                }

                $ids[] = $id;

                if (!empty($services)) {
                    $this->agendamentosRepository->syncServices((int) $id, $services);
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->error("Erro ao processar série de agendamentos no banco de dados.");
            }

            return $this->success('Série de agendamentos criada com sucesso.', ['ids' => $ids, 'grupo_id' => $grupoId]);

        } catch (\Exception $e) {
            return $this->error("Erro ao criar recorrência: " . $e->getMessage());
        }
    }

    /**
     * Gera a lista de datas de uma série recorrente, respeitando a
     * periodicidade, a data-fim e um limite máximo de ocorrências.
     *
     * @param string $startDate
     * @param string $recorrencia
     * @param string $endDate
     * @param int $maxOccurrences
     * @return string[]
     */
    protected function buildRecurrenceDates(string $startDate, string $recorrencia, string $endDate, int $maxOccurrences): array
    {
        $intervalMap = [
            'semanal' => '+1 week',
            'quinzenal' => '+2 weeks',
            'mensal' => '+1 month'
        ];
        $interval = $intervalMap[$recorrencia] ?? '+1 week';

        $dates = [];
        $currentDateStr = $startDate;

        while ($currentDateStr <= $endDate && count($dates) < $maxOccurrences) {
            $dates[] = $currentDateStr;
            $currentDateStr = date('Y-m-d', strtotime($interval, strtotime($currentDateStr)));
        }

        return $dates;
    }

    /**
     * Atualiza um agendamento existente e seus serviços.
     *
     * @param int $id
     * @param array $data
     * @return array
     */
    public function update(int $id, array $data): array
    {
        $services = null;
        if (isset($data['age_servico'])) {
            $services = (array) $data['age_servico'];
            unset($data['age_servico']);
        }

        $result = parent::update($id, $data);

        if ($result['status'] === 'success' && $services !== null) {
            $this->agendamentosRepository->syncServices($id, $services);
        }

        return $result;
    }

    /**
     * Atualiza o status de um agendamento
     *
     * @param int $id
     * @param string $status
     * @return array
     */
    public function updateStatus(int $id, string $status): array
    {
        $updated = $this->repository->update($id, ['age_status' => $status]);
        if ($updated) {
            return $this->success("Status atualizado para $status");
        }
        return $this->error("Erro ao atualizar status");
    }

    /**
     * Realiza o faturamento de um agendamento
     *
     * @param int $id
     * @param array $billingData
     * @return array
     */
    public function billAppointment(int $id, array $billingData): array
    {
        $appointment = $this->repository->findById($id);
        if (!$appointment) {
            return $this->error("Agendamento não encontrado");
        }

        // Get service IDs from pivot table
        $serviceIds = $this->agendamentosRepository->getServiceIds($id);
        $primaryServiceId = !empty($serviceIds) ? (int)$serviceIds[0] : null;

        // Normaliza pacote_id para null se vier vazio (evita erro de FK)
        if (empty($billingData['pacote_id'])) {
            $billingData['pacote_id'] = null;
        }

        // Se já existe uma cobrança para este agendamento, é uma edição: não deve
        // criar uma cobrança duplicada nem consumir o pacote de novo.
        $existingCobranca = $this->fatService->findByAgendamento($id);

        if ($billingData['forma_pagamento'] === 'Pacote' && !$existingCobranca) {
            // Usar o primeiro serviço do agendamento para o consumo do pacote
            $this->pacoteService->consumir((int)$billingData['pacote_id'], $primaryServiceId, (float)$billingData['valor']);
        }

        // Prepare billing data
        $billingData['paciente_id'] = $appointment['paciente_id'];
        $billingData['servico'] = $appointment['age_servico'] ?? 'Serviços Diversos';
        $billingData['servico_id'] = $primaryServiceId; // Pass primary service ID to avoid redundant or failed lookups
        $billingData['data_servico'] = $appointment['age_data'];
        $billingData['agendamento_id'] = $id;

        $result = $existingCobranca
            ? $this->fatService->updateCobranca((int)$existingCobranca['id'], $billingData)
            : $this->fatService->createCobranca($billingData);

        if ($result['status'] === 'success') {
            // Mark as billed
            $this->repository->update($id, [
                'age_faturado' => 1,
                'age_status' => 'concluido'
            ]);
            $message = $existingCobranca ? "Faturamento atualizado com sucesso!" : "Faturamento realizado com sucesso!";
            return $this->success($message, ['id' => $existingCobranca['id'] ?? $result['id'] ?? null]);
        }

        return $result;
    }

    /**
     * Retorna a cobrança vinculada a um agendamento, se houver.
     *
     * @param int $id
     * @return array|null
     */
    public function getBilling(int $id): ?array
    {
        return $this->fatService->findByAgendamento($id);
    }
}
