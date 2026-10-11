<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\PacoteItemModel;
use App\Models\PacoteModel;
use App\Repositories\AgendamentosRepository;
use App\Repositories\FatCobrancaRepository;
use App\Repositories\PacoteItensRepository;
use App\Repositories\PacotesRepository;
use App\Repositories\PacoteUsoRepository;
use App\Services\AgendaService;
use App\Services\PacoteService;
use CodeIgniter\Test\CIUnitTestCase;
use Exception;

/**
 * @internal
 */
final class PacoteServiceTest extends CIUnitTestCase
{
    private PacotesRepository $pacotesRepo;
    private PacoteItensRepository $itensRepo;
    private FatCobrancaRepository $cobrancaRepo;
    private PacoteUsoRepository $usoRepo;
    private AgendaService $agendaService;
    private AgendamentosRepository $agendamentosRepo;
    private PacoteService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pacotesRepo = $this->createMock(PacotesRepository::class);
        $this->itensRepo = $this->createMock(PacoteItensRepository::class);
        $this->cobrancaRepo = $this->createMock(FatCobrancaRepository::class);
        $this->usoRepo = $this->createMock(PacoteUsoRepository::class);

        $this->agendaService = $this->createMock(AgendaService::class);
        $this->agendamentosRepo = $this->createMock(AgendamentosRepository::class);

        $this->service = new PacoteService(
            $this->pacotesRepo,
            $this->itensRepo,
            $this->cobrancaRepo,
            $this->usoRepo,
            $this->agendaService,
            $this->agendamentosRepo
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function preagendamento(array $override = []): array
    {
        return array_merge([
            'data_inicial'   => '2026-11-02',
            'periodicidade'  => 'semanal',
            'hora'           => '14:00',
            'duracao'        => '60',
            'veterinario_id' => '3',
        ], $override);
    }

    /**
     * Captura as chamadas a AgendaService::createSeriePorQuantidade().
     *
     * @param array<int, array{0: array, 1: int}> $chamadas
     */
    private function capturarSeries(array &$chamadas): void
    {
        $this->agendaService->method('createSeriePorQuantidade')
            ->willReturnCallback(static function (array $data, int $quantidade) use (&$chamadas): array {
                $chamadas[] = [$data, $quantidade];
                return ['status' => 'success', 'ids' => range(1, $quantidade)];
            });
    }

    private function mockPacoteModelUpdate(): PacoteModel
    {
        $model = $this->getMockBuilder(PacoteModel::class)->onlyMethods(['update'])->getMock();
        $model->expects($this->once())->method('update')->willReturn(true);

        return $model;
    }

    private function mockPacoteItemModelUpdate(): PacoteItemModel
    {
        $model = $this->getMockBuilder(PacoteItemModel::class)->onlyMethods(['update'])->getMock();
        $model->method('update')->willReturn(true);

        return $model;
    }

    public function testGetPacotesRepositoryReturnsInjectedInstance(): void
    {
        $this->assertSame($this->pacotesRepo, $this->service->getPacotesRepository());
    }

    public function testGetItensRepositoryReturnsInjectedInstance(): void
    {
        $this->assertSame($this->itensRepo, $this->service->getItensRepository());
    }

    public function testCreatePacoteWithItensAndGeneratesCobranca(): void
    {
        $this->pacotesRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $d): bool => $d['tipo'] === 'Serviços' && $d['saldo_valor'] === 0))
            ->willReturn(10);

        $this->itensRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $d): bool => $d['pacote_id'] === 10 && $d['servico_id'] === 5));

        $this->cobrancaRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $d): bool => $d['pacote_id'] === 10));

        $result = $this->service->createPacote([
            'paciente_id' => 1,
            'nome'        => 'Pacote Banho x5',
            'valor_total' => 250,
            'itens'       => [['servico_id' => 5, 'quantidade' => 5, 'valor_unitario' => 50]],
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertSame(10, $result['id']);
    }

    public function testCreatePacoteCreditTypeSetsInitialBalance(): void
    {
        $this->pacotesRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $d): bool => $d['tipo'] === 'Crédito' && $d['saldo_valor'] === 300))
            ->willReturn(11);
        $this->itensRepo->expects($this->never())->method('create');
        $this->cobrancaRepo->method('create')->willReturn(1);

        $result = $this->service->createPacote([
            'paciente_id' => 1,
            'nome'        => 'Crédito de Serviços',
            'tipo'        => 'Crédito',
            'valor_total' => 300,
        ]);

        $this->assertSame('success', $result['status']);
    }

    public function testCreatePacoteWithoutPreagendarDoesNotTouchAgenda(): void
    {
        $this->pacotesRepo->method('create')->willReturn(10);
        $this->agendaService->expects($this->never())->method('createSeriePorQuantidade');

        $result = $this->service->createPacote([
            'paciente_id' => 1,
            'nome'        => 'Banho x4',
            'valor_total' => 200,
            'itens'       => [['servico_id' => 5, 'quantidade' => 4, 'valor_unitario' => 50]],
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertSame(0, $result['preagendados']);
    }

    public function testCreatePacoteWithPreagendarSchedulesEachItemInConsecutiveSlots(): void
    {
        $this->pacotesRepo->method('create')->willReturn(10);
        $chamadas = [];
        $this->capturarSeries($chamadas);

        $result = $this->service->createPacote([
            'paciente_id'    => 1,
            'nome'           => 'Banho e Tosa',
            'valor_total'    => 300,
            'itens'          => [
                ['servico_id' => 5, 'quantidade' => 4, 'valor_unitario' => 50],
                ['servico_id' => null, 'item_nome' => 'Mimo', 'quantidade' => 1, 'valor_unitario' => 10],
                ['servico_id' => 6, 'quantidade' => 2, 'valor_unitario' => 40],
            ],
            'preagendar'     => true,
            'preagendamento' => $this->preagendamento(),
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertSame(6, $result['preagendados']);
        $this->assertCount(2, $chamadas);

        [$banho, $qtdBanho] = $chamadas[0];
        $this->assertSame(4, $qtdBanho);
        $this->assertSame([5], $banho['age_servico']);
        $this->assertSame(10, $banho['pacote_id']);
        $this->assertSame(1, $banho['paciente_id']);
        $this->assertSame('2026-11-02', $banho['age_data']);
        $this->assertSame('14:00:00', $banho['age_hora']);
        $this->assertSame('60', $banho['age_duracao']);
        $this->assertSame(3, $banho['age_veterinario']);
        $this->assertSame('semanal', $banho['age_recorrencia']);

        [$tosa, $qtdTosa] = $chamadas[1];
        $this->assertSame(2, $qtdTosa);
        $this->assertSame([6], $tosa['age_servico']);
        $this->assertSame('15:00:00', $tosa['age_hora'], 'O 2º item começa quando termina o 1º');
    }

    public function testCreatePacoteWithPreagendarRequiresDateAndTime(): void
    {
        $this->pacotesRepo->method('create')->willReturn(10);
        $this->agendaService->expects($this->never())->method('createSeriePorQuantidade');

        $result = $this->service->createPacote([
            'paciente_id'    => 1,
            'nome'           => 'Banho x4',
            'valor_total'    => 200,
            'itens'          => [['servico_id' => 5, 'quantidade' => 4]],
            'preagendar'     => true,
            'preagendamento' => $this->preagendamento(['hora' => '']),
        ]);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('horário', $result['message']);
    }

    public function testCreatePacoteWithPreagendarRejectsQuinzenal(): void
    {
        $this->pacotesRepo->method('create')->willReturn(10);
        $this->agendaService->expects($this->never())->method('createSeriePorQuantidade');

        $result = $this->service->createPacote([
            'paciente_id'    => 1,
            'nome'           => 'Banho x4',
            'valor_total'    => 200,
            'itens'          => [['servico_id' => 5, 'quantidade' => 4]],
            'preagendar'     => true,
            'preagendamento' => $this->preagendamento(['periodicidade' => 'quinzenal']),
        ]);

        $this->assertSame('error', $result['status']);
    }

    public function testCreatePacoteWithPreagendarFailsWhenNoItemHasCatalogService(): void
    {
        $this->pacotesRepo->method('create')->willReturn(10);

        $result = $this->service->createPacote([
            'paciente_id'    => 1,
            'nome'           => 'Avulso',
            'valor_total'    => 50,
            'itens'          => [['servico_id' => null, 'item_nome' => 'Mimo', 'quantidade' => 2]],
            'preagendar'     => true,
            'preagendamento' => $this->preagendamento(),
        ]);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('serviço do catálogo', $result['message']);
    }

    public function testCreatePacoteFailsWhenAgendaFailsToCreateSeries(): void
    {
        $this->pacotesRepo->method('create')->willReturn(10);
        $this->agendaService->method('createSeriePorQuantidade')
            ->willReturn(['status' => 'error', 'message' => 'Erro ao criar o agendamento da data 2026-11-09.']);

        $result = $this->service->createPacote([
            'paciente_id'    => 1,
            'nome'           => 'Banho x4',
            'valor_total'    => 200,
            'itens'          => [['servico_id' => 5, 'quantidade' => 4]],
            'preagendar'     => true,
            'preagendamento' => $this->preagendamento(),
        ]);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('2026-11-09', $result['message']);
    }

    public function testCreatePacoteFailsWhenSlotsPassMidnight(): void
    {
        $this->pacotesRepo->method('create')->willReturn(10);
        $chamadas = [];
        $this->capturarSeries($chamadas);

        $result = $this->service->createPacote([
            'paciente_id'    => 1,
            'nome'           => 'Noturno',
            'valor_total'    => 100,
            'itens'          => [['servico_id' => 5, 'quantidade' => 1], ['servico_id' => 6, 'quantidade' => 1]],
            'preagendar'     => true,
            'preagendamento' => $this->preagendamento(['hora' => '23:30', 'duracao' => '60']),
        ]);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('fim do dia', $result['message']);
    }

    public function testPreAgendarSchedulesOnlyRemainingSessions(): void
    {
        $this->pacotesRepo->method('findById')->willReturn([
            'id' => 10, 'paciente_id' => 1, 'tipo' => 'Serviços', 'status' => 'Ativo',
        ]);
        $this->itensRepo->method('getPorPacote')->with(10)->willReturn([
            ['servico_id' => 5, 'quantidade_total' => 4, 'quantidade_usada' => 1],
            ['servico_id' => 6, 'quantidade_total' => 2, 'quantidade_usada' => 0],
            ['servico_id' => 7, 'quantidade_total' => 3, 'quantidade_usada' => 3],
        ]);
        // 1 sessão de banho já agendada e ainda não faturada
        $this->agendamentosRepo->method('countPendentesPorPacote')->with(10)->willReturn([5 => 1]);

        $chamadas = [];
        $this->capturarSeries($chamadas);

        $result = $this->service->preAgendar(10, $this->preagendamento(['periodicidade' => 'mensal']));

        $this->assertSame('success', $result['status']);
        $this->assertSame(4, $result['preagendados']);
        $this->assertCount(2, $chamadas);
        $this->assertSame([5], $chamadas[0][0]['age_servico']);
        $this->assertSame(2, $chamadas[0][1]);
        $this->assertSame('mensal', $chamadas[0][0]['age_recorrencia']);
        $this->assertSame([6], $chamadas[1][0]['age_servico']);
        $this->assertSame(2, $chamadas[1][1]);
    }

    public function testPreAgendarFailsWhenEverySessionIsAlreadyScheduled(): void
    {
        $this->pacotesRepo->method('findById')->willReturn([
            'id' => 10, 'paciente_id' => 1, 'tipo' => 'Serviços', 'status' => 'Pendente',
        ]);
        $this->itensRepo->method('getPorPacote')->willReturn([
            ['servico_id' => 5, 'quantidade_total' => 4, 'quantidade_usada' => 0],
        ]);
        $this->agendamentosRepo->method('countPendentesPorPacote')->willReturn([5 => 4]);
        $this->agendaService->expects($this->never())->method('createSeriePorQuantidade');

        $result = $this->service->preAgendar(10, $this->preagendamento());

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('já estão agendadas', $result['message']);
    }

    public function testPreAgendarRejectsCreditAndCancelledPackages(): void
    {
        $this->pacotesRepo->method('findById')->willReturnOnConsecutiveCalls(
            null,
            ['id' => 10, 'paciente_id' => 1, 'tipo' => 'Crédito', 'status' => 'Ativo'],
            ['id' => 11, 'paciente_id' => 1, 'tipo' => 'Serviços', 'status' => 'Cancelado'],
        );
        $this->agendaService->expects($this->never())->method('createSeriePorQuantidade');

        $this->assertSame('error', $this->service->preAgendar(99, $this->preagendamento())['status']);
        $this->assertSame('error', $this->service->preAgendar(10, $this->preagendamento())['status']);
        $this->assertSame('error', $this->service->preAgendar(11, $this->preagendamento())['status']);
    }

    public function testCreatePacoteReturnsErrorWhenRepositoryThrows(): void
    {
        $this->pacotesRepo->method('create')->willThrowException(new Exception('db error'));

        $result = $this->service->createPacote(['paciente_id' => 1, 'nome' => 'Pacote', 'valor_total' => 100]);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('db error', $result['message']);
    }

    public function testUpdatePacoteReturnsErrorWhenNotFound(): void
    {
        $this->pacotesRepo->method('findById')->willReturn(null);

        $result = $this->service->updatePacote(99, ['nome' => 'Novo nome']);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Pacote não encontrado.', $result['message']);
    }

    public function testUpdatePacoteUpdatesBasicFields(): void
    {
        $this->pacotesRepo->method('findById')->willReturn(['id' => 1, 'tipo' => 'Serviços', 'status' => 'Ativo']);
        $this->pacotesRepo->expects($this->once())
            ->method('update')
            ->with(1, $this->callback(static fn (array $d): bool => $d['nome'] === 'Pacote Renomeado' && $d['status'] === 'Cancelado'))
            ->willReturn(true);

        $result = $this->service->updatePacote(1, ['nome' => 'Pacote Renomeado', 'status' => 'Cancelado']);

        $this->assertSame('success', $result['status']);
    }

    public function testUpdatePacoteSyncsItensWhenPendente(): void
    {
        $this->pacotesRepo->method('findById')->willReturn(['id' => 1, 'tipo' => 'Serviços', 'status' => 'Pendente']);
        $this->itensRepo->expects($this->once())
            ->method('sync')
            ->with(1, [['servico_id' => 5, 'quantidade' => 2, 'valor_unitario' => 50]]);

        $result = $this->service->updatePacote(1, ['itens' => [['servico_id' => 5, 'quantidade' => 2, 'valor_unitario' => 50]]]);

        $this->assertSame('success', $result['status']);
    }

    public function testUpdatePacoteReturnsErrorWhenChangingItensOfActivePacote(): void
    {
        $this->pacotesRepo->method('findById')->willReturn(['id' => 1, 'tipo' => 'Serviços', 'status' => 'Ativo']);
        $this->itensRepo->expects($this->never())->method('sync');

        $result = $this->service->updatePacote(1, ['itens' => [['servico_id' => 5, 'quantidade' => 2]]]);

        $this->assertSame('error', $result['status']);
    }

    public function testDeletePacoteReturnsErrorWhenNotFound(): void
    {
        $this->pacotesRepo->method('findById')->willReturn(null);

        $result = $this->service->deletePacote(99);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Pacote não encontrado.', $result['message']);
    }

    public function testDeletePacoteReturnsErrorWhenUsoRegistrado(): void
    {
        $this->pacotesRepo->method('findById')->willReturn(['id' => 1, 'tipo' => 'Serviços', 'status' => 'Esgotado']);
        $this->usoRepo->method('getPorPacote')->willReturn([['id' => 1]]);
        $this->pacotesRepo->expects($this->never())->method('delete');

        $result = $this->service->deletePacote(1);

        $this->assertSame('error', $result['status']);
    }

    public function testDeletePacoteRemovesItensAndPacoteWhenNoUso(): void
    {
        $this->pacotesRepo->method('findById')->willReturn(['id' => 1, 'tipo' => 'Serviços', 'status' => 'Pendente']);
        $this->usoRepo->method('getPorPacote')->willReturn([]);

        $this->itensRepo->expects($this->once())->method('deleteByPacote')->with(1);
        $this->pacotesRepo->expects($this->once())->method('delete')->with(1)->willReturn(true);

        $result = $this->service->deletePacote(1);

        $this->assertSame('success', $result['status']);
    }

    public function testActivatePacoteDelegatesToModelUpdate(): void
    {
        $this->pacotesRepo->method('getModel')->willReturn($this->mockPacoteModelUpdate());

        $this->assertTrue($this->service->activatePacote(10));
    }

    public function testGetDisponiveisPorPetReturnsEmptyWhenNonePacotes(): void
    {
        $this->pacotesRepo->method('getAtivosPorPet')->willReturn([]);
        $this->itensRepo->expects($this->never())->method('getPorPacotes');

        $this->assertSame([], $this->service->getDisponiveisPorPet(1));
    }

    public function testGetDisponiveisPorPetAttachesItensToServicePacotes(): void
    {
        $this->pacotesRepo->method('getAtivosPorPet')->willReturn([
            ['id' => 1, 'tipo' => 'Serviços'],
            ['id' => 2, 'tipo' => 'Crédito'],
        ]);
        $this->itensRepo->expects($this->once())
            ->method('getPorPacotes')
            ->with([1])
            ->willReturn([
                ['id' => 100, 'pacote_id' => 1, 'servico_id' => 5],
            ]);

        $result = $this->service->getDisponiveisPorPet(1);

        $this->assertSame([['id' => 100, 'pacote_id' => 1, 'servico_id' => 5]], $result[0]['itens']);
        $this->assertArrayNotHasKey('itens', $result[1]);
    }

    public function testConsumirReturnsErrorWhenPacoteNotFound(): void
    {
        $this->pacotesRepo->method('findById')->willReturn(null);

        $result = $this->service->consumir(1, 5, 50);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Pacote não encontrado ou inativo.', $result['message']);
    }

    public function testConsumirReturnsErrorWhenPacoteNotActive(): void
    {
        $this->pacotesRepo->method('findById')->willReturn(['id' => 1, 'status' => 'Esgotado']);

        $result = $this->service->consumir(1, 5, 50);

        $this->assertSame('error', $result['status']);
    }

    public function testConsumirCreditoReturnsErrorWhenInsufficientBalance(): void
    {
        $this->pacotesRepo->method('findById')->willReturn(['id' => 1, 'status' => 'Ativo', 'tipo' => 'Crédito', 'saldo_valor' => 10]);
        $this->usoRepo->expects($this->never())->method('create');

        $result = $this->service->consumir(1, null, 50);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Saldo insuficiente no pacote.', $result['message']);
    }

    public function testConsumirCreditoDeductsBalanceAndLogsUso(): void
    {
        $this->pacotesRepo->method('findById')->willReturn(['id' => 1, 'status' => 'Ativo', 'tipo' => 'Crédito', 'saldo_valor' => 100]);
        $this->pacotesRepo->method('getModel')->willReturn($this->mockPacoteModelUpdate());
        $this->usoRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $d): bool => $d['valor_descontado'] === 40.0));

        $result = $this->service->consumir(1, null, 40);

        $this->assertSame('success', $result['status']);
    }

    public function testConsumirServicoReturnsErrorWhenNoMatchingItem(): void
    {
        $this->pacotesRepo->method('findById')->willReturn(['id' => 1, 'status' => 'Ativo', 'tipo' => 'Serviços']);
        $this->itensRepo->method('getPorPacote')->willReturn([
            ['id' => 1, 'servico_id' => 9, 'quantidade_total' => 3, 'quantidade_usada' => 3],
        ]);

        $result = $this->service->consumir(1, 5, 0);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Serviço não disponível neste pacote ou esgotado.', $result['message']);
    }

    public function testConsumirServicoRegistersUsageAndMarksEsgotadoWhenFullyUsed(): void
    {
        $this->pacotesRepo->method('findById')->willReturn(['id' => 1, 'status' => 'Ativo', 'tipo' => 'Serviços']);
        // First call (inside consumir()) finds the item with capacity left; the
        // second call (inside checkEsgotado()) simulates the just-persisted
        // update, showing the item is now fully used.
        $this->itensRepo->expects($this->exactly(2))
            ->method('getPorPacote')
            ->willReturnOnConsecutiveCalls(
                [['id' => 42, 'servico_id' => 5, 'quantidade_total' => 1, 'quantidade_usada' => 0, 'valor_unitario' => 60]],
                [['id' => 42, 'servico_id' => 5, 'quantidade_total' => 1, 'quantidade_usada' => 1, 'valor_unitario' => 60]]
            );
        $this->itensRepo->method('getModel')->willReturn($this->mockPacoteItemModelUpdate());
        $this->usoRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $d): bool => $d['quantidade'] === 1 && $d['valor_descontado'] === 60));

        // checkEsgotado() re-reads items: now fully used -> marks pacote as Esgotado
        $this->pacotesRepo->method('getModel')->willReturn($this->mockPacoteModelUpdate());

        $result = $this->service->consumir(1, 5, 0);

        $this->assertSame('success', $result['status']);
        $this->assertSame('Consumo de serviço realizado.', $result['message']);
    }
}
