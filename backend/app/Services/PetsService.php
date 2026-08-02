<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PetsRepository;
use App\Repositories\AgendamentosRepository;
use App\Repositories\ClientesRepository;

/**
 * @property PetsRepository $repository
 */
class PetsService extends BaseService
{
    protected AgendamentosRepository $agendamentosRepository;
    protected ClientesRepository $clientesRepository;

    /**
     * @param PetsRepository|null $repository
     * @param AgendamentosRepository|null $agendamentosRepository
     * @param ClientesRepository|null $clientesRepository
     */
    public function __construct(
        ?PetsRepository $repository = null,
        ?AgendamentosRepository $agendamentosRepository = null,
        ?ClientesRepository $clientesRepository = null
    ) {
        $this->repository = $repository ?? new PetsRepository();
        $this->agendamentosRepository = $agendamentosRepository ?? new AgendamentosRepository();
        $this->clientesRepository = $clientesRepository ?? new ClientesRepository();
    }

    /**
     * Busca pets por um termo
     *
     * @param string $term
     * @return array
     */
    public function search(string $term): array
    {
        return $this->repository->search($term);
    }

    /**
     * Retorna todos os pets com as informações do último agendamento.
     *
     * @return array
     */
    public function getAllWithLastAppointment(): array
    {
        return $this->repository->getAllWithLastAppointment();
    }

    public function getBirthdaysOfMonth(int $month): array
    {
        return $this->repository->getBirthdaysByMonth($month);
    }

    /**
     * Lista pacientes filtrados pela tela de cadastro (status, espécie, busca).
     */
    public function listFiltered(string $status, ?string $especie, ?string $term): array
    {
        return $this->repository->getFiltered($status, $especie, $term);
    }

    /**
     * Dados do painel lateral da tela de pacientes: estatísticas, adições
     * recentes, aniversariantes do mês e espécies cadastradas (para o filtro).
     */
    public function getPainelData(): array
    {
        return [
            'stats'           => $this->repository->getStats(),
            'recentes'        => $this->repository->getRecent(5),
            'aniversariantes' => $this->getBirthdaysOfMonth((int) date('m')),
            'especies'        => $this->repository->getEspecies(),
        ];
    }

    /**
     * Cria cliente (se novo), pet e agendamento em uma única transação
     *
     * @param array $data
     * @return array
     */
    public function mixCreate(array $data): array
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $clientData = [];
        $petData    = [];
        $ageData    = [];

        // Mapping old pet_resp_* fields to new clientes table fields
        $clientMapping = [
            'pet_resp_nome'     => 'nome',
            'pet_resp_cpf'      => 'cpf',
            'pet_resp_tel'      => 'telefones',
            'pet_resp_email'    => 'emails',
            'pet_endereco'      => 'rua', // Simplificado para o exemplo
        ];

        foreach ($data as $key => $value) {
            $val = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;

            if (isset($clientMapping[$key])) {
                $clientData[$clientMapping[$key]] = $val;
            } elseif (str_starts_with($key, 'pet_')) {
                // Campos recebidos com prefixo legado 'pet_' são gravados na coluna 'paciente_' correspondente
                $petData['paciente_' . substr($key, 4)] = $val;
            } elseif (str_starts_with($key, 'age_')) {
                $ageData[$key] = $val;
            }
        }

        // 1. Encontrar ou Criar Cliente
        $clienteId = null;
        if (!empty($clientData['cpf'])) {
            $existing = $this->clientesRepository->findByCpf($clientData['cpf']);
            if ($existing) {
                $clienteId = $existing['id'];
            }
        }

        if (!$clienteId && !empty($clientData['nome'])) {
            $clienteId = $this->clientesRepository->create($clientData);
        }

        // 2. Criar Pet vinculado ao Cliente
        if ($clienteId) {
            $petData['cliente_id'] = $clienteId;
        }
        
        $petId = $this->repository->create($petData);

        // 3. Criar Agendamento se houver dados e pet criado
        if ($petId && !empty($ageData['age_data'])) {
            $ageData['paciente_id'] = $petId;
            $this->agendamentosRepository->create($ageData);
        }

        $db->transComplete();

        if ($db->transStatus() === false || !$petId) {
            return $this->error('Erro ao processar o cadastro do pet');
        }

        return $this->success('Pet cadastrado com sucesso', ['id' => $petId]);
    }
}
