<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\PacoteItemModel;
use App\Models\PacoteModel;
use App\Repositories\FatCobrancaRepository;
use App\Repositories\PacoteItensRepository;
use App\Repositories\PacotesRepository;
use App\Repositories\PacoteUsoRepository;
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
    private PacoteService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pacotesRepo = $this->createMock(PacotesRepository::class);
        $this->itensRepo = $this->createMock(PacoteItensRepository::class);
        $this->cobrancaRepo = $this->createMock(FatCobrancaRepository::class);
        $this->usoRepo = $this->createMock(PacoteUsoRepository::class);

        $this->service = new PacoteService($this->pacotesRepo, $this->itensRepo, $this->cobrancaRepo, $this->usoRepo);
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

    public function testCreatePacoteReturnsErrorWhenRepositoryThrows(): void
    {
        $this->pacotesRepo->method('create')->willThrowException(new Exception('db error'));

        $result = $this->service->createPacote(['paciente_id' => 1, 'nome' => 'Pacote', 'valor_total' => 100]);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('db error', $result['message']);
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
