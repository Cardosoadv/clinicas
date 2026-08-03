<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\FatCobrancaModel;
use App\Models\FatDespesaModel;
use App\Models\FatNotaModel;
use App\Repositories\FatCobrancaRepository;
use App\Repositories\FatDespesaRepository;
use App\Repositories\FatNotaRepository;
use App\Repositories\PetsRepository;
use App\Services\EstoqueService;
use App\Services\FatService;
use CodeIgniter\Events\Events;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 *
 * The "Pacote" payment branch of createCobranca() is intentionally not
 * covered: it hard-instantiates `new PacoteService()` internally instead of
 * accepting an injected instance, so it always hits a real PacotesRepository
 * pointed at the (unmigrated, table-less) in-memory test database.
 */
final class FatServiceTest extends CIUnitTestCase
{
    private FatCobrancaRepository $cobrancaRepo;
    private FatDespesaRepository $despesaRepo;
    private FatNotaRepository $notaRepo;
    private PetsRepository $petsRepo;
    private EstoqueService $estoqueService;
    private FatService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cobrancaRepo = $this->createMock(FatCobrancaRepository::class);
        $this->despesaRepo = $this->createMock(FatDespesaRepository::class);
        $this->notaRepo = $this->createMock(FatNotaRepository::class);
        $this->petsRepo = $this->createMock(PetsRepository::class);
        $this->estoqueService = $this->createMock(EstoqueService::class);

        $this->service = new FatService(
            $this->cobrancaRepo,
            $this->despesaRepo,
            $this->notaRepo,
            $this->petsRepo,
            $this->estoqueService
        );
    }

    protected function tearDown(): void
    {
        Events::removeAllListeners('cobranca_paga');
        parent::tearDown();
    }

    private function mockCobrancaModelFindAll(array $rows): FatCobrancaModel
    {
        $model = $this->getMockBuilder(FatCobrancaModel::class)->onlyMethods(['findAll'])->getMock();
        $model->method('findAll')->willReturn($rows);

        return $model;
    }

    private function mockCobrancaModelUpdate(bool $return = true): FatCobrancaModel
    {
        $model = $this->getMockBuilder(FatCobrancaModel::class)->onlyMethods(['update'])->getMock();
        $model->method('update')->willReturn($return);

        return $model;
    }

    private function mockDespesaModelUpdate(bool $return = true): FatDespesaModel
    {
        $model = $this->getMockBuilder(FatDespesaModel::class)->onlyMethods(['update'])->getMock();
        $model->method('update')->willReturn($return);

        return $model;
    }

    private function mockNotaModelFindAll(array $rows): FatNotaModel
    {
        $model = $this->getMockBuilder(FatNotaModel::class)->onlyMethods(['findAll'])->getMock();
        $model->method('findAll')->willReturn($rows);

        return $model;
    }

    // -- createCobranca ------------------------------------------------

    public function testCreateCobrancaSucceedsForNonPacotePayment(): void
    {
        $this->cobrancaRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $d): bool => !array_key_exists('pacote_id', $d)))
            ->willReturn(1);
        $this->estoqueService->expects($this->never())->method('deduzirEstoquePorServico');

        $result = $this->service->createCobranca(['forma_pagamento' => 'Dinheiro', 'pacote_id' => 5, 'valor' => 100]);

        $this->assertSame('success', $result['status']);
        $this->assertSame(1, $result['id']);
    }

    public function testCreateCobrancaReturnsErrorWhenPacoteIdMissingForPacotePayment(): void
    {
        $this->cobrancaRepo->expects($this->never())->method('create');

        $result = $this->service->createCobranca(['forma_pagamento' => 'Pacote']);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('ID do pacote não informado', $result['message']);
    }

    public function testCreateCobrancaDeductsStockWhenPaidWithLinkedService(): void
    {
        $this->cobrancaRepo->method('create')->willReturn(2);
        $this->estoqueService->expects($this->once())
            ->method('deduzirEstoquePorServico')
            ->with(9);

        $result = $this->service->createCobranca([
            'forma_pagamento' => 'Dinheiro',
            'status'          => 'Pago',
            'servico_id'      => 9,
            'valor'           => 100,
        ]);

        $this->assertSame('success', $result['status']);
    }

    public function testCreateCobrancaDoesNotDeductStockWhenPending(): void
    {
        $this->cobrancaRepo->method('create')->willReturn(3);
        $this->estoqueService->expects($this->never())->method('deduzirEstoquePorServico');

        $this->service->createCobranca(['forma_pagamento' => 'Dinheiro', 'servico_id' => 9, 'valor' => 100]);
    }

    public function testCreateCobrancaReturnsErrorWhenRepositoryFails(): void
    {
        $this->cobrancaRepo->method('create')->willReturn(0);

        $result = $this->service->createCobranca(['forma_pagamento' => 'Dinheiro', 'valor' => 100]);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro ao criar cobrança', $result['message']);
    }

    // -- updateCobranca --------------------------------------------------

    public function testUpdateCobrancaReturnsErrorWhenNotFound(): void
    {
        $this->cobrancaRepo->method('findById')->willReturn(null);

        $result = $this->service->updateCobranca(1, ['valor' => 100]);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Cobrança não encontrada.', $result['message']);
    }

    public function testUpdateCobrancaReturnsErrorWhenNoAllowedFields(): void
    {
        $this->cobrancaRepo->method('findById')->willReturn(['id' => 1, 'status' => 'Pendente']);

        $result = $this->service->updateCobranca(1, ['nao_permitido' => 'x']);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Nenhum campo válido para atualizar.', $result['message']);
    }

    public function testUpdateCobrancaSucceedsWithoutTriggeringPaidSideEffects(): void
    {
        $this->cobrancaRepo->method('findById')->willReturn(['id' => 1, 'status' => 'Pendente']);
        $this->cobrancaRepo->method('getModel')->willReturn($this->mockCobrancaModelUpdate(true));
        $this->estoqueService->expects($this->never())->method('deduzirEstoquePorServico');

        $result = $this->service->updateCobranca(1, ['valor' => 150]);

        $this->assertSame('success', $result['status']);
    }

    public function testUpdateCobrancaTriggersPacoteEventAndStockDeductionWhenBecomingPago(): void
    {
        $this->cobrancaRepo->method('findById')->willReturn([
            'id' => 1, 'status' => 'Pendente', 'pacote_id' => 7, 'servico_id' => 9,
        ]);
        $this->cobrancaRepo->method('getModel')->willReturn($this->mockCobrancaModelUpdate(true));

        $captured = null;
        Events::on('cobranca_paga', static function ($pacoteId) use (&$captured): void {
            $captured = $pacoteId;
        });

        $this->estoqueService->expects($this->once())->method('deduzirEstoquePorServico')->with(9);

        $result = $this->service->updateCobranca(1, ['status' => 'Pago']);

        $this->assertSame('success', $result['status']);
        $this->assertSame(7, $captured);
    }

    public function testUpdateCobrancaDoesNotRetriggerWhenAlreadyPago(): void
    {
        $this->cobrancaRepo->method('findById')->willReturn(['id' => 1, 'status' => 'Pago', 'pacote_id' => 7]);
        $this->cobrancaRepo->method('getModel')->willReturn($this->mockCobrancaModelUpdate(true));
        $this->estoqueService->expects($this->never())->method('deduzirEstoquePorServico');

        $result = $this->service->updateCobranca(1, ['status' => 'Pago']);

        $this->assertSame('success', $result['status']);
    }

    public function testUpdateCobrancaReturnsErrorWhenModelUpdateFails(): void
    {
        $this->cobrancaRepo->method('findById')->willReturn(['id' => 1, 'status' => 'Pendente']);
        $this->cobrancaRepo->method('getModel')->willReturn($this->mockCobrancaModelUpdate(false));

        $result = $this->service->updateCobranca(1, ['valor' => 100]);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro ao atualizar cobrança.', $result['message']);
    }

    // -- createDespesa -----------------------------------------------------

    public function testCreateDespesaSimpleSucceeds(): void
    {
        $this->despesaRepo->expects($this->once())
            ->method('create')
            ->with(['descricao' => 'Ração'])
            ->willReturn(1);

        $result = $this->service->createDespesa(['descricao' => 'Ração']);

        $this->assertSame('success', $result['status']);
        $this->assertSame(1, $result['id']);
    }

    public function testCreateDespesaReturnsErrorWhenRepositoryFails(): void
    {
        $this->despesaRepo->method('create')->willReturn(0);

        $result = $this->service->createDespesa(['descricao' => 'Ração']);

        $this->assertSame('error', $result['status']);
    }

    public function testCreateDespesaParceladaCreatesOneRowPerInstallment(): void
    {
        $this->despesaRepo->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls(10, 11);

        $result = $this->service->createDespesa([
            'descricao'     => 'Equipamento',
            'categoria'     => 'Compras',
            'is_parcelado'  => 'true',
            'parcelas'      => [
                ['valor' => 50, 'vencimento' => '2026-09-01'],
                ['valor' => 50, 'vencimento' => '2026-10-01'],
            ],
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertSame([10, 11], $result['ids']);
        $this->assertStringContainsString('2x', $result['message']);
    }

    public function testCreateDespesaParceladaAcceptsJsonEncodedParcelas(): void
    {
        $this->despesaRepo->expects($this->once())->method('create')->willReturn(20);

        $result = $this->service->createDespesa([
            'descricao'    => 'Equipamento',
            'categoria'    => 'Compras',
            'is_parcelado' => 'true',
            'parcelas'     => json_encode([['valor' => 100, 'vencimento' => '2026-09-01']]),
        ]);

        $this->assertSame('success', $result['status']);
    }

    public function testCreateDespesaRecorrenteCreatesInstancesUntilEndDate(): void
    {
        $this->despesaRepo->expects($this->exactly(3))
            ->method('create')
            ->willReturnOnConsecutiveCalls(30, 31, 32);

        $result = $this->service->createDespesa([
            'descricao'        => 'Aluguel',
            'categoria'        => 'Fixas',
            'valor'            => 500,
            'is_recorrente'    => 'true',
            'recorrencia'      => 'mensal',
            'data_vencimento'  => '2026-01-05',
            'recorrencia_fim'  => '2026-03-05',
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertStringContainsString('3 ocorrências', $result['message']);
        $this->assertSame([30, 31, 32], $result['ids']);
    }

    // -- updateDespesa -----------------------------------------------------

    public function testUpdateDespesaReturnsErrorWhenNoAllowedFields(): void
    {
        $result = $this->service->updateDespesa(1, ['campo_invalido' => 'x']);

        $this->assertSame('error', $result['status']);
    }

    public function testUpdateDespesaSucceeds(): void
    {
        $this->despesaRepo->method('getModel')->willReturn($this->mockDespesaModelUpdate(true));

        $result = $this->service->updateDespesa(1, ['valor' => 200]);

        $this->assertSame('success', $result['status']);
    }

    public function testUpdateDespesaReturnsErrorWhenModelUpdateFails(): void
    {
        $this->despesaRepo->method('getModel')->willReturn($this->mockDespesaModelUpdate(false));

        $result = $this->service->updateDespesa(1, ['valor' => 200]);

        $this->assertSame('error', $result['status']);
    }

    // -- uploadComprovante ---------------------------------------------------

    public function testUploadComprovanteReturnsErrorForInvalidFile(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(false);

        $result = $this->service->uploadComprovante(1, $file);

        $this->assertSame('error', $result['status']);
    }

    public function testUploadComprovanteReturnsErrorWhenNoFileProvided(): void
    {
        $result = $this->service->uploadComprovante(1, null);

        $this->assertSame('error', $result['status']);
    }

    public function testUploadComprovanteMovesFileAndUpdatesRecord(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('guessExtension')->willReturn('pdf');
        $file->expects($this->once())->method('move')->willReturn(true);

        $this->despesaRepo->method('getModel')->willReturn($this->mockDespesaModelUpdate(true));

        $result = $this->service->uploadComprovante(1, $file);

        $this->assertSame('success', $result['status']);
        $this->assertStringStartsWith('despesas/1/comprovante_', $result['path']);
    }

    public function testUploadComprovanteReturnsErrorWhenMoveFails(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('guessExtension')->willReturn('pdf');
        $file->method('move')->willReturn(false);

        $result = $this->service->uploadComprovante(1, $file);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Falha ao salvar o arquivo.', $result['message']);
    }

    // -- createNota ----------------------------------------------------------

    public function testCreateNotaGeneratesHashWhenMissing(): void
    {
        $this->notaRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $d): bool => !empty($d['hash'])))
            ->willReturn(1);

        $result = $this->service->createNota(['paciente_id' => 1]);

        $this->assertSame('success', $result['status']);
    }

    public function testCreateNotaKeepsProvidedHash(): void
    {
        $this->notaRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $d): bool => $d['hash'] === 'custom-hash'))
            ->willReturn(1);

        $this->service->createNota(['hash' => 'custom-hash']);
    }

    public function testCreateNotaReturnsErrorWhenRepositoryFails(): void
    {
        $this->notaRepo->method('create')->willReturn(0);

        $result = $this->service->createNota([]);

        $this->assertSame('error', $result['status']);
    }

    // -- other read methods ------------------------------------------------

    public function testGetLancamentosDataAggregatesCobrancasAndDespesas(): void
    {
        $this->cobrancaRepo->method('getRecent')->with(20)->willReturn([['id' => 1]]);
        $this->despesaRepo->method('getRecent')->with(20)->willReturn([['id' => 2]]);

        $result = $this->service->getLancamentosData();

        $this->assertSame([['id' => 1]], $result['cobrancas']);
        $this->assertSame([['id' => 2]], $result['despesas']);
    }

    public function testGetNotasDelegatesToModel(): void
    {
        $this->notaRepo->method('getModel')->willReturn($this->mockNotaModelFindAll([['id' => 1]]));

        $this->assertSame([['id' => 1]], $this->service->getNotas());
    }

    public function testGetRecebiveisDelegatesToModel(): void
    {
        $this->cobrancaRepo->method('getModel')->willReturn($this->mockCobrancaModelFindAll([['id' => 1, 'status' => 'Pendente']]));

        $this->assertSame([['id' => 1, 'status' => 'Pendente']], $this->service->getRecebiveis());
    }

    public function testGetDashboardDataAssemblesAllSections(): void
    {
        $this->cobrancaRepo->method('getTotalMonth')->willReturn(1000.0);
        $this->despesaRepo->method('getTotalMonth')->willReturn(400.0);
        $this->cobrancaRepo->method('getSummaryStats')->willReturn(['a_receber' => 200.0, 'vencidos' => 50.0]);
        $this->cobrancaRepo->method('getRecent')->willReturn([]);
        $this->despesaRepo->method('getRecent')->willReturn([]);
        $this->notaRepo->method('getRecent')->willReturn([['id' => 1]]);
        $this->petsRepo->method('findForLookup')->willReturn([['paciente_id' => 1]]);
        $this->cobrancaRepo->method('getModel')->willReturn($this->mockCobrancaModelFindAll([]));

        $result = $this->service->getDashboardData();

        $this->assertSame(1000.0, $result['kpis']['receitas']);
        $this->assertSame(400.0, $result['kpis']['despesas']);
        $this->assertSame(600.0, $result['kpis']['saldo']);
        $this->assertSame(200.0, $result['kpis']['a_receber']);
        $this->assertSame(50.0, $result['kpis']['vencidos']);
        $this->assertSame([['id' => 1]], $result['notas']);
        $this->assertSame([['paciente_id' => 1]], $result['pets']);
        $this->assertArrayHasKey('chart_data', $result['stats']);
        $this->assertCount(7, $result['stats']['chart_data']['points']);
    }

    // -- generateQR ----------------------------------------------------------

    public function testGenerateQRReturnsBase64Image(): void
    {
        $result = $this->service->generateQR('https://example.com/recibo/123');

        $this->assertStringStartsWith('data:image/', $result);
    }
}
