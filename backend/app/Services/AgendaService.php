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
        $diasSemana = (array) ($data['age_recorrencia_dias'] ?? []);
        unset($data['age_recorrencia_dias']);

        if ($recorrencia === 'nenhuma') {
            $result = parent::create($data);
            if ($result['status'] === 'success' && !empty($services)) {
                $this->agendamentosRepository->syncServices((int) $result['id'], $services);
            }
            return $result;
        }

        // É recorrente. Data limite: 1 ano por padrão se não informada
        $dataFimStr = !empty($data['age_recorrencia_fim']) ? $data['age_recorrencia_fim'] : date('Y-m-d', strtotime('+1 year'));
        $datas = $this->gerarDatasRecorrencia((string) $data['age_data'], $dataFimStr, $recorrencia, $diasSemana);

        if (empty($datas)) {
            return $this->error("Nenhuma data da série cai entre o início e o fim informados.");
        }

        try {
            $db = \Config\Database::connect();
            $db->transStart();

            $grupoId = bin2hex(random_bytes(16)); // UUID simplificado para agrupar as instâncias
            $data['age_grupo_id'] = $grupoId;
            
            $count = 0;
            $firstId = null;

            foreach ($datas as $dataOcorrencia) {
                $iterationData = $data;
                $iterationData['age_data'] = $dataOcorrencia;

                $id = $this->repository->create($iterationData);
                if (!$id) {
                    throw new \Exception("Erro ao criar instância da recorrência na data $dataOcorrencia");
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
     * Cria uma série recorrente com uma quantidade fixa de ocorrências (em vez
     * de uma data-fim), agrupadas pelo mesmo `age_grupo_id`. Usado para
     * pré-agendar as sessões de um pacote.
     *
     * Não abre transação própria: quem chama (ex.: PacoteService) controla a
     * transação e deve desfazê-la se este método retornar erro.
     *
     * @param array $data Campos do agendamento (inclui `age_servico` e `age_recorrencia`)
     * @param int $quantidade
     * @return array
     */
    public function createSeriePorQuantidade(array $data, int $quantidade): array
    {
        if ($quantidade < 1) {
            return $this->error('Quantidade de ocorrências inválida.');
        }

        $services = (array) ($data['age_servico'] ?? []);
        unset($data['age_servico']);
        $diasSemana = (array) ($data['age_recorrencia_dias'] ?? []);
        unset($data['age_recorrencia_dias']);

        $recorrencia = $data['age_recorrencia'] ?? 'semanal';
        // A quantidade é quem limita a série; a data-fim é só uma salvaguarda.
        $dataFim = date('Y-m-d', strtotime('+10 years', strtotime((string) $data['age_data'])));
        $datas = $this->gerarDatasRecorrencia((string) $data['age_data'], $dataFim, $recorrencia, $diasSemana, $quantidade);

        if (count($datas) < $quantidade) {
            return $this->error('Não foi possível gerar todas as datas da série.');
        }

        $data['age_grupo_id'] = bin2hex(random_bytes(16));
        $data['age_recorrencia_fim'] = end($datas);
        $ids = [];

        foreach ($datas as $dataOcorrencia) {
            $iterationData = $data;
            $iterationData['age_data'] = $dataOcorrencia;

            $id = $this->repository->create($iterationData);
            if (!$id) {
                return $this->error("Erro ao criar o agendamento da data $dataOcorrencia.");
            }

            if (!empty($services)) {
                $this->agendamentosRepository->syncServices((int) $id, $services);
            }

            $ids[] = (int) $id;
        }

        return $this->success(count($ids) . ' agendamento(s) criado(s).', ['ids' => $ids, 'datas' => $datas]);
    }

    /**
     * Gera as datas das ocorrências de uma série recorrente.
     *
     * Para recorrência semanal/quinzenal, é possível informar os dias da semana
     * (0 = domingo ... 6 = sábado) em que o agendamento se repete. Sem dias
     * informados, repete no mesmo dia da semana da data inicial.
     *
     * Na recorrência mensal, as datas são calculadas a partir do dia da data
     * inicial (ex.: dia 31 vira o último dia dos meses mais curtos), sem
     * acumular deslocamentos de um mês para o outro.
     *
     * @param string $dataInicio
     * @param string $dataFim
     * @param string $recorrencia
     * @param array $diasSemana
     * @param int|null $limite Máximo de ocorrências (padrão: 366 com dias da semana, 52 sem)
     * @return string[]
     */
    protected function gerarDatasRecorrencia(string $dataInicio, string $dataFim, string $recorrencia, array $diasSemana = [], ?int $limite = null): array
    {
        $maxOcorrencias = $limite ?? 366;
        $datas = [];

        $diasSemana = array_values(array_unique(array_filter(
            array_map('intval', $diasSemana),
            static fn (int $dia): bool => $dia >= 0 && $dia <= 6
        )));
        sort($diasSemana);

        if (in_array($recorrencia, ['semanal', 'quinzenal'], true) && !empty($diasSemana)) {
            $passoSemanas = $recorrencia === 'quinzenal' ? 2 : 1;
            $inicio = new \DateTimeImmutable($dataInicio);
            // Domingo da semana da data inicial
            $semana = $inicio->modify('-' . (int) $inicio->format('w') . ' days');

            while ($semana->format('Y-m-d') <= $dataFim && count($datas) < $maxOcorrencias) {
                foreach ($diasSemana as $dia) {
                    $data = $semana->modify("+{$dia} days")->format('Y-m-d');
                    if ($data < $dataInicio || $data > $dataFim) {
                        continue;
                    }
                    $datas[] = $data;
                    if (count($datas) >= $maxOcorrencias) {
                        break;
                    }
                }
                $semana = $semana->modify("+{$passoSemanas} weeks");
            }

            return $datas;
        }

        $maxIterations = $limite ?? 52; // Máximo de 1 ano para recorrência semanal

        if ($recorrencia === 'mensal') {
            $inicio = new \DateTimeImmutable($dataInicio);
            $diaDoMes = (int) $inicio->format('j');
            $primeiroDoMes = $inicio->modify('first day of this month');

            for ($n = 0; count($datas) < $maxIterations; $n++) {
                $mes = $primeiroDoMes->modify("+{$n} months");
                $dia = min($diaDoMes, (int) $mes->format('t'));
                $data = $mes->setDate((int) $mes->format('Y'), (int) $mes->format('n'), $dia)->format('Y-m-d');
                if ($data > $dataFim) {
                    break;
                }
                $datas[] = $data;
            }

            return $datas;
        }

        $intervalMap = [
            'semanal' => '+1 week',
            'quinzenal' => '+2 weeks',
        ];
        $interval = $intervalMap[$recorrencia] ?? '+1 week';

        $atual = $dataInicio;
        while ($atual <= $dataFim && count($datas) < $maxIterations) {
            $datas[] = $atual;
            $atual = date('Y-m-d', strtotime($interval, strtotime($atual)));
        }

        return $datas;
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
        unset($data['age_recorrencia_dias']);

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
