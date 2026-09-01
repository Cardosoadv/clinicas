<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Services\AgendaService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller responsável pela gestão de agendamentos e horários da clínica.
 */
class Agenda extends BaseController
{
    protected AgendaService $service;

    public function __construct(?AgendaService $service = null)
    {
        $this->service = $service ?? new AgendaService();
    }

    /**
     * API: Retorna os agendamentos e estatísticas de uma data específica.
     */
    public function getDayData(): ResponseInterface
    {
        $date = $this->request->getGet('date') ?? date('Y-m-d');
        $data = $this->service->getDayData($date);
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Retorna os próximos agendamentos confirmados.
     */
    public function getUpcoming(): ResponseInterface
    {
        $data = $this->service->getUpcoming();
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Lista agendamentos com filtros opcionais (status, período, serviço,
     * veterinário responsável e busca livre) para a visão geral em cards.
     */
    public function index(): ResponseInterface
    {
        $filters = [
            'status'         => (string) ($this->request->getGet('status') ?? ''),
            'data_inicio'    => (string) ($this->request->getGet('data_inicio') ?? ''),
            'data_fim'       => (string) ($this->request->getGet('data_fim') ?? ''),
            'servico_id'     => $this->request->getGet('servico_id') ?? '',
            'veterinario_id' => $this->request->getGet('veterinario_id') ?? '',
            'search'         => (string) ($this->request->getGet('search') ?? ''),
        ];

        $data = $this->service->listAppointments($filters);
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Cria um novo agendamento (ou série recorrente).
     */
    public function create(): ResponseInterface
    {
        $data = $this->getRequestData();

        if (!$this->validateData($data, $this->getValidationRules('create'))) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->create($data);
        return $this->apiResponse($result, 201);
    }

    /**
     * API: Busca pacientes para preenchimento no formulário de agendamento.
     */
    public function searchPets(): ResponseInterface
    {
        $term = (string) ($this->request->getGet('term') ?? '');
        $data = $this->service->searchPets($term);
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Retorna datas únicas que possuem agendamentos em um determinado mês/ano.
     */
    public function getMonthDays(): ResponseInterface
    {
        $year = $this->request->getGet('year') ?? date('Y');
        $month = $this->request->getGet('month') ?? date('m');
        $data = $this->service->getMonthDaysWithAppts($year, $month);
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Atualiza um agendamento existente.
     */
    public function update(int $id): ResponseInterface
    {
        $data = $this->getRequestData();

        if (!$this->validateData($data, $this->getValidationRules('update'))) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        if (empty($data)) {
            return $this->apiError('Nenhum dado válido recebido para atualização.');
        }

        $result = $this->service->update($id, $data);
        return $this->apiResponse($result);
    }

    /**
     * API: Atualiza o status de um agendamento.
     */
    public function updateStatus(int $id): ResponseInterface
    {
        $data = $this->getRequestData();

        $rules = [
            'status' => 'required|in_list[pendente,confirmado,cancelado,concluido,Pendente,Confirmado,Cancelado,Concluido]',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->updateStatus($id, strtolower((string) $data['status']));
        return $this->apiResponse($result);
    }

    /**
     * API: Retorna a cobrança já lançada para um agendamento, se houver.
     */
    public function getFaturamento(int $id): ResponseInterface
    {
        $data = $this->service->getBilling($id);
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Realiza o faturamento de um agendamento.
     */
    public function faturar(int $id): ResponseInterface
    {
        $data = $this->getRequestData();

        if (!$this->validate($this->getValidationRules('faturar'), $data)) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->billAppointment($id, $data);
        return $this->apiResponse($result);
    }

    /**
     * Retorna as regras de validação para os métodos da agenda.
     */
    private function getValidationRules(string $method): array
    {
        $rules = [
            'create' => [
                'paciente_id' => 'required|is_natural_no_zero',
                'age_servico' => 'required',
                'age_data'    => 'required|valid_date[Y-m-d]',
                'age_hora'    => 'required',
                'age_status'  => 'permit_empty|in_list[pendente,confirmado,cancelado,concluido]',
            ],
            'update' => [
                'paciente_id' => 'permit_empty|is_natural_no_zero',
                'age_servico' => 'permit_empty',
                'age_data'    => 'permit_empty|valid_date[Y-m-d]',
                'age_hora'    => 'permit_empty',
                'age_status'  => 'permit_empty|in_list[pendente,confirmado,cancelado,concluido]',
            ],
            'faturar' => [
                'valor'           => 'required|numeric',
                'forma_pagamento' => 'required|string',
                'status'          => 'required|in_list[Pago,Pendente,Cancelado]',
                'data_vencimento' => 'permit_empty|valid_date[Y-m-d]',
                'pacote_id'       => 'permit_empty|is_natural',
            ],
        ];

        return $rules[$method] ?? [];
    }
}
