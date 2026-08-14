<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\AgendamentosRepository;
use App\Repositories\PetsRepository;
use App\Services\AgendaService;
use App\Services\FatService;
use App\Services\PacoteService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AgendaServiceTest extends CIUnitTestCase
{
    private AgendamentosRepository $agendamentosRepository;
    private PetsRepository $petsRepository;
    private FatService $fatService;
    private PacoteService $pacoteService;
    private AgendaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agendamentosRepository = $this->createMock(AgendamentosRepository::class);
        $this->petsRepository = $this->createMock(PetsRepository::class);
        $this->fatService = $this->createMock(FatService::class);
        $this->pacoteService = $this->createMock(PacoteService::class);

        $this->service = new AgendaService(
            $this->agendamentosRepository,
            $this->petsRepository,
            $this->fatService,
            $this->pacoteService
        );
    }

    public function testGetDayDataReturnsAppointmentsStatsAndDate(): void
    {
        $this->agendamentosRepository->method('getByDate')->with('2026-08-10')->willReturn([['id' => 1]]);
        $this->agendamentosRepository->method('getStats')->willReturn(['total' => 1]);

        $result = $this->service->getDayData('2026-08-10');

        $this->assertSame([['id' => 1]], $result['appointments']);
        $this->assertSame(['total' => 1], $result['stats']);
        $this->assertSame('2026-08-10', $result['date']);
    }

    public function testGetUpcomingDelegatesToRepository(): void
    {
        $this->agendamentosRepository->expects($this->once())->method('getUpcoming')->willReturn([['id' => 1]]);

        $this->assertSame([['id' => 1]], $this->service->getUpcoming());
    }

    public function testSearchPetsDelegatesToPetsRepository(): void
    {
        $this->petsRepository->expects($this->once())->method('findForLookup')->with('rex')->willReturn([['paciente_id' => 1]]);

        $this->assertSame([['paciente_id' => 1]], $this->service->searchPets('rex'));
    }

    public function testGetMonthDaysWithApptsDelegatesToRepository(): void
    {
        $this->agendamentosRepository->expects($this->once())
            ->method('getDaysWithAppts')
            ->with('2026', '08')
            ->willReturn(['10', '15']);

        $this->assertSame(['10', '15'], $this->service->getMonthDaysWithAppts('2026', '08'));
    }

    public function testCreateSingleAppointmentSyncsServices(): void
    {
        $this->agendamentosRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $d): bool => !isset($d['age_servico'])))
            ->willReturn(50);
        $this->agendamentosRepository->expects($this->once())
            ->method('syncServices')
            ->with(50, [1, 2]);

        $result = $this->service->create([
            'age_data'       => '2026-08-10',
            'age_recorrencia' => 'nenhuma',
            'age_servico'    => [1, 2],
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertSame(50, $result['id']);
    }

    public function testCreateSingleAppointmentSkipsSyncWhenNoServices(): void
    {
        $this->agendamentosRepository->method('create')->willReturn(51);
        $this->agendamentosRepository->expects($this->never())->method('syncServices');

        $result = $this->service->create(['age_data' => '2026-08-10']);

        $this->assertSame('success', $result['status']);
    }

    public function testCreateRecurringAppointmentsCreatesOneInstancePerOccurrence(): void
    {
        $this->agendamentosRepository->expects($this->exactly(3))
            ->method('create')
            ->willReturnOnConsecutiveCalls(100, 101, 102);
        $this->agendamentosRepository->expects($this->exactly(3))->method('syncServices');

        $result = $this->service->create([
            'age_data'             => '2026-08-01',
            'age_recorrencia'      => 'semanal',
            'age_recorrencia_fim'  => '2026-08-15',
            'age_servico'          => [1],
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertSame(100, $result['id']);
        $this->assertStringContainsString('3 ocorrências', $result['message']);
    }

    public function testCreateRecurringAppointmentReturnsErrorWhenInstanceCreationFails(): void
    {
        $this->agendamentosRepository->method('create')->willReturn(0);

        $result = $this->service->create([
            'age_data'        => '2026-08-01',
            'age_recorrencia' => 'semanal',
        ]);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('Erro ao criar recorrência', $result['message']);
    }

    public function testUpdateSyncsServicesOnSuccess(): void
    {
        $this->agendamentosRepository->expects($this->once())
            ->method('update')
            ->with(5, $this->callback(static fn (array $d): bool => !isset($d['age_servico'])))
            ->willReturn(true);
        $this->agendamentosRepository->expects($this->once())->method('syncServices')->with(5, [3, 4]);

        $result = $this->service->update(5, ['age_servico' => [3, 4]]);

        $this->assertSame('success', $result['status']);
    }

    public function testUpdateDoesNotSyncServicesWhenNotProvided(): void
    {
        $this->agendamentosRepository->method('update')->willReturn(true);
        $this->agendamentosRepository->expects($this->never())->method('syncServices');

        $this->service->update(5, ['age_status' => 'concluido']);
    }

    public function testUpdateDoesNotSyncServicesWhenUpdateFails(): void
    {
        $this->agendamentosRepository->method('update')->willReturn(false);
        $this->agendamentosRepository->expects($this->never())->method('syncServices');

        $result = $this->service->update(5, ['age_servico' => [1]]);

        $this->assertSame('error', $result['status']);
    }

    public function testUpdateStatusReturnsSuccess(): void
    {
        $this->agendamentosRepository->expects($this->once())
            ->method('update')
            ->with(5, ['age_status' => 'concluido'])
            ->willReturn(true);

        $result = $this->service->updateStatus(5, 'concluido');

        $this->assertSame('success', $result['status']);
        $this->assertSame('Status atualizado para concluido', $result['message']);
    }

    public function testUpdateStatusReturnsErrorWhenUpdateFails(): void
    {
        $this->agendamentosRepository->method('update')->willReturn(false);

        $result = $this->service->updateStatus(5, 'concluido');

        $this->assertSame('error', $result['status']);
    }

    public function testBillAppointmentReturnsErrorWhenNotFound(): void
    {
        $this->agendamentosRepository->method('findById')->willReturn(null);

        $result = $this->service->billAppointment(5, []);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Agendamento não encontrado', $result['message']);
    }

    public function testBillAppointmentCreatesCobrancaAndMarksAsBilled(): void
    {
        $this->agendamentosRepository->method('findById')->willReturn([
            'paciente_id' => 1,
            'age_servico' => 'Banho',
            'age_data'    => '2026-08-10',
        ]);
        $this->agendamentosRepository->method('getServiceIds')->willReturn([7, 8]);
        $this->pacoteService->expects($this->never())->method('consumir');

        $this->fatService->expects($this->once())
            ->method('createCobranca')
            ->with($this->callback(static function (array $d): bool {
                return $d['paciente_id'] === 1 && $d['servico_id'] === 7 && $d['forma_pagamento'] === 'Dinheiro';
            }))
            ->willReturn(['status' => 'success', 'id' => 99]);

        $this->agendamentosRepository->expects($this->once())
            ->method('update')
            ->with(5, ['age_faturado' => 1, 'age_status' => 'concluido']);

        $result = $this->service->billAppointment(5, ['forma_pagamento' => 'Dinheiro', 'valor' => 100]);

        $this->assertSame('success', $result['status']);
        $this->assertSame(99, $result['id']);
    }

    public function testBillAppointmentConsumesPacoteWhenFormaPagamentoIsPacote(): void
    {
        $this->agendamentosRepository->method('findById')->willReturn([
            'paciente_id' => 1,
            'age_servico' => 'Banho',
            'age_data'    => '2026-08-10',
        ]);
        $this->agendamentosRepository->method('getServiceIds')->willReturn([7]);

        $this->pacoteService->expects($this->once())
            ->method('consumir')
            ->with(3, 7, 100.0)
            ->willReturn(['status' => 'success']);

        $this->fatService->method('createCobranca')->willReturn(['status' => 'success', 'id' => 5]);
        $this->agendamentosRepository->method('update')->willReturn(true);

        $result = $this->service->billAppointment(5, ['forma_pagamento' => 'Pacote', 'pacote_id' => 3, 'valor' => 100]);

        $this->assertSame('success', $result['status']);
    }

    public function testBillAppointmentPropagatesCobrancaErrorWithoutMarkingBilled(): void
    {
        $this->agendamentosRepository->method('findById')->willReturn([
            'paciente_id' => 1,
            'age_servico' => 'Banho',
            'age_data'    => '2026-08-10',
        ]);
        $this->agendamentosRepository->method('getServiceIds')->willReturn([]);
        $this->fatService->method('createCobranca')->willReturn(['status' => 'error', 'message' => 'Falhou']);
        $this->agendamentosRepository->expects($this->never())->method('update');

        $result = $this->service->billAppointment(5, ['forma_pagamento' => 'Dinheiro', 'valor' => 100]);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Falhou', $result['message']);
    }

    public function testBillAppointmentUpdatesExistingCobrancaInsteadOfCreatingDuplicate(): void
    {
        $this->agendamentosRepository->method('findById')->willReturn([
            'paciente_id' => 1,
            'age_servico' => 'Banho',
            'age_data'    => '2026-08-10',
        ]);
        $this->agendamentosRepository->method('getServiceIds')->willReturn([7]);
        $this->fatService->method('findByAgendamento')->with(5)->willReturn(['id' => 42, 'status' => 'Pago']);

        $this->fatService->expects($this->never())->method('createCobranca');
        $this->fatService->expects($this->once())
            ->method('updateCobranca')
            ->with(42, $this->callback(static function (array $d): bool {
                return $d['agendamento_id'] === 5 && $d['valor'] === 150;
            }))
            ->willReturn(['status' => 'success']);

        $this->agendamentosRepository->expects($this->once())
            ->method('update')
            ->with(5, ['age_faturado' => 1, 'age_status' => 'concluido']);

        $result = $this->service->billAppointment(5, ['forma_pagamento' => 'Dinheiro', 'valor' => 150]);

        $this->assertSame('success', $result['status']);
        $this->assertSame(42, $result['id']);
    }

    public function testBillAppointmentDoesNotReconsumePacoteWhenEditingExistingCobranca(): void
    {
        $this->agendamentosRepository->method('findById')->willReturn([
            'paciente_id' => 1,
            'age_servico' => 'Banho',
            'age_data'    => '2026-08-10',
        ]);
        $this->agendamentosRepository->method('getServiceIds')->willReturn([7]);
        $this->fatService->method('findByAgendamento')->willReturn(['id' => 42, 'status' => 'Pago']);
        $this->fatService->method('updateCobranca')->willReturn(['status' => 'success']);

        $this->pacoteService->expects($this->never())->method('consumir');

        $result = $this->service->billAppointment(5, ['forma_pagamento' => 'Pacote', 'pacote_id' => 3, 'valor' => 100]);

        $this->assertSame('success', $result['status']);
    }
}
