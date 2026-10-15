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

    public function testCreateDespesaParceladaRejectsInvalidLegacyParcelas(): void
    {
        $this->despesaRepo->expects($this->never())->method('create');

        $result = $this->service->createDespesa([
            'descricao'    => 'Equipamento',
            'categoria'    => 'Compras',
            'is_parcelado' => 'true',
            'parcelas'     => [['valor' => 0, 'vencimento' => '2026-09-01']],
        ]);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('parcela 1', $result['message']);
    }

    public function testCreateDespesaParceladaRejectsInvalidLegacyVencimento(): void
    {
        $this->despesaRepo->expects($this->never())->method('create');

        $result = $this->service->createDespesa([
            'descricao'    => 'Equipamento',
            'categoria'    => 'Compras',
            'is_parcelado' => 'true',
            'parcelas'     => [['valor' => 10, 'vencimento' => '2026-02-31']],
        ]);

        $this->assertSame('error', $result['status']);
    }

    public function testCreateDespesaParceladaRejectsParceladaERecorrente(): void
    {
        $this->despesaRepo->expects($this->never())->method('create');

        $result = $this->service->createDespesa([
            'descricao'     => 'Equipamento',
            'categoria'     => 'Compras',
            'is_parcelado'  => 'true',
            'is_recorrente' => 'true',
            'recorrencia'   => 'mensal',
            'parcelas_qtd'  => 3,
            'valor'         => 100,
        ]);

        $this->assertSame('error', $result['status']);
    }

    /**
     * @return array<int, array<string, mixed>> Dados enviados a cada create()
     */
    private function capturarParcelasGeradas(array $payload): array
    {
        $capturado = [];
        $this->despesaRepo->method('create')->willReturnCallback(
            function (array $row) use (&$capturado): int {
                $capturado[] = $row;
                return count($capturado);
            }
        );

        $result = $this->service->createDespesa($payload + [
            'descricao'    => 'Equipamento',
            'categoria'    => 'Compras',
            'is_parcelado' => 'true',
        ]);
        $this->assertSame('success', $result['status'], $result['message']);

        return $capturado;
    }

    public function testCreateDespesaParceladaGeraParcelasEDivideCentavos(): void
    {
        $rows = $this->capturarParcelasGeradas([
            'valor'           => 100,
            'parcelas_qtd'    => 3,
            'data_vencimento' => '2026-09-10',
        ]);

        $this->assertSame([33.33, 33.33, 33.34], array_column($rows, 'valor'));
        $this->assertEqualsWithDelta(100.0, array_sum(array_column($rows, 'valor')), 0.001);
        $this->assertSame([1, 2, 3], array_column($rows, 'des_parcela_num'));
        $this->assertSame([3, 3, 3], array_column($rows, 'des_parcela_total'));
        $this->assertSame(['2026-09-10', '2026-10-10', '2026-11-10'], array_column($rows, 'data_vencimento'));
        $this->assertCount(1, array_unique(array_column($rows, 'des_grupo_id')));
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $rows[0]['des_grupo_id']
        );
    }

    public function testCreateDespesaParceladaMensalNaoPulaMesNoFimDoMes(): void
    {
        $rows = $this->capturarParcelasGeradas([
            'valor'           => 300,
            'parcelas_qtd'    => 4,
            'data_vencimento' => '2026-01-31',
        ]);

        $this->assertSame(
            ['2026-01-31', '2026-02-28', '2026-03-31', '2026-04-30'],
            array_column($rows, 'data_vencimento')
        );
    }

    public function testCreateDespesaParceladaSemanalEQuinzenal(): void
    {
        $semanal = $this->capturarParcelasGeradas([
            'valor' => 30, 'parcelas_qtd' => 3, 'data_vencimento' => '2026-09-01', 'periodicidade' => 'semanal',
        ]);
        $this->assertSame(['2026-09-01', '2026-09-08', '2026-09-15'], array_column($semanal, 'data_vencimento'));
    }

    public function testCreateDespesaParceladaPrimeiraParcelaMantemStatusEnviado(): void
    {
        $rows = $this->capturarParcelasGeradas([
            'valor' => 60, 'parcelas_qtd' => 3, 'data_vencimento' => '2026-09-01', 'status' => 'Pago',
        ]);

        $this->assertSame(['Pago', 'Pendente', 'Pendente'], array_column($rows, 'status'));
    }

    /**
     * @dataProvider payloadsInvalidosProvider
     */
    public function testCreateDespesaParceladaRejeitaPayloadInvalido(array $payload): void
    {
        $this->despesaRepo->expects($this->never())->method('create');

        $result = $this->service->createDespesa($payload + [
            'descricao' => 'Equipamento', 'categoria' => 'Compras', 'is_parcelado' => 'true',
        ]);

        $this->assertSame('error', $result['status']);
    }

    public static function payloadsInvalidosProvider(): array
    {
        $ok = ['valor' => 100, 'parcelas_qtd' => 3, 'data_vencimento' => '2026-09-10'];

        return [
            'sem quantidade'       => [array_diff_key($ok, ['parcelas_qtd' => 1])],
            'quantidade 1'         => [['parcelas_qtd' => 1] + $ok],
            'acima do limite'      => [['parcelas_qtd' => 61] + $ok],
            'quantidade fracionada' => [['parcelas_qtd' => 2.5] + $ok],
            'valor zero'           => [['valor' => 0] + $ok],
            'valor menor que qtd'  => [['valor' => 0.02] + $ok],
            'data invalida'        => [['data_vencimento' => '10/09/2026'] + $ok],
            'periodicidade'        => [['periodicidade' => 'anual'] + $ok],
        ];
    }

    public function testCreateDespesaParceladaFalhaNaSegundaParcelaRetornaErro(): void
    {
        $this->despesaRepo->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls(10, 0);

        $result = $this->service->createDespesa([
            'descricao'       => 'Equipamento',
            'categoria'       => 'Compras',
            'is_parcelado'    => 'true',
            'valor'           => 90,
            'parcelas_qtd'    => 3,
            'data_vencimento' => '2026-09-01',
        ]);

        $this->assertSame('error', $result['status']);
        $this->assertArrayNotHasKey('ids', $result);
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

    // -- despesas parceladas: escopo de edição/cancelamento -----------------

    /** Parcelas 1..4 de um grupo: a 1ª já paga. */
    private function grupoParcelas(array $status = ['Pago', 'Pendente', 'Pendente', 'Pendente']): array
    {
        $rows = [];
        foreach ($status as $i => $st) {
            $rows[] = [
                'id'                => 101 + $i,
                'des_grupo_id'      => 'g-1',
                'des_parcela_num'   => $i + 1,
                'des_parcela_total' => count($status),
                'data_vencimento'   => sprintf('2026-%02d-10', 9 + $i),
                'status'            => $st,
            ];
        }

        return $rows;
    }

    /** Registra as chamadas a update() em $calls como [id => dados]. */
    private function mockUpdateRecorder(array &$calls, bool $ok = true): FatDespesaModel
    {
        $model = $this->getMockBuilder(FatDespesaModel::class)->onlyMethods(['update'])->getMock();
        $model->method('update')->willReturnCallback(
            function ($id, $data = null) use (&$calls, $ok): bool {
                $calls[(int) $id] = $data;
                return $ok;
            }
        );
        $this->despesaRepo->method('getModel')->willReturn($model);

        return $model;
    }

    private function givenGrupo(int $idAtual, array $grupo): void
    {
        $atual = null;
        foreach ($grupo as $d) {
            if ($d['id'] === $idAtual) {
                $atual = $d;
            }
        }
        $this->despesaRepo->method('findById')->willReturn($atual);
        $this->despesaRepo->method('getByGrupo')->willReturn($grupo);
    }

    public function testUpdateDespesaEscopoTodasPropagaCamposCadastraisInclusivePagas(): void
    {
        $calls = [];
        $this->mockUpdateRecorder($calls);
        $this->givenGrupo(102, $this->grupoParcelas());

        $result = $this->service->updateDespesa(102, ['fornecedor' => 'Nova', 'escopo' => 'todas']);

        $this->assertSame('success', $result['status']);
        $this->assertSame(4, $result['atualizadas']);
        $ids = array_keys($calls);
        sort($ids);
        $this->assertSame([101, 102, 103, 104], $ids);
        $this->assertSame(['fornecedor' => 'Nova'], $calls[101]);
    }

    public function testUpdateDespesaEscopoNaoAlteraFormaDePagamentoDeParcelaPaga(): void
    {
        $calls = [];
        $this->mockUpdateRecorder($calls);
        $this->givenGrupo(102, $this->grupoParcelas());

        $this->service->updateDespesa(102, ['forma_pagamento' => 'Boleto', 'escopo' => 'todas']);

        $this->assertArrayNotHasKey(101, $calls);
        $this->assertSame(['forma_pagamento' => 'Boleto'], $calls[103]);
    }

    public function testUpdateDespesaEstaESeguintesAtingeSoAPartirDaParcela(): void
    {
        $calls = [];
        $this->mockUpdateRecorder($calls);
        $this->givenGrupo(103, $this->grupoParcelas());

        $result = $this->service->updateDespesa(103, ['descricao' => 'Novo nome', 'escopo' => 'esta_e_seguintes']);

        $this->assertEqualsCanonicalizing([103, 104], array_keys($calls));
        $this->assertSame(2, $result['atualizadas']);
    }

    public function testUpdateDespesaValorEVencimentoValemSoParaAPropriaParcela(): void
    {
        $calls = [];
        $this->mockUpdateRecorder($calls);
        $this->givenGrupo(102, $this->grupoParcelas());

        $result = $this->service->updateDespesa(102, ['valor' => 50, 'escopo' => 'todas']);

        $this->assertSame('success', $result['status']);
        $this->assertSame([102], array_keys($calls));
        $this->assertSame(['valor' => 50], $calls[102]);
        $this->assertSame(1, $result['atualizadas']);
    }

    public function testUpdateDespesaEscopoEmDespesaSemGrupoAtualizaSoEla(): void
    {
        $calls = [];
        $this->mockUpdateRecorder($calls);
        $this->despesaRepo->method('findById')->willReturn(['id' => 7, 'des_grupo_id' => null, 'status' => 'Pendente']);
        $this->despesaRepo->expects($this->never())->method('getByGrupo');

        $result = $this->service->updateDespesa(7, ['descricao' => 'X', 'escopo' => 'todas']);

        $this->assertSame('success', $result['status']);
        $this->assertSame([7], array_keys($calls));
    }

    public function testUpdateDespesaEscopoUsaVencimentoEmRecorrencia(): void
    {
        $calls = [];
        $this->mockUpdateRecorder($calls);
        $grupo = [];
        foreach ([1 => '2026-09-05', 2 => '2026-10-05', 3 => '2026-11-05'] as $i => $data) {
            $grupo[] = ['id' => 200 + $i, 'des_grupo_id' => 'FIX_1', 'des_parcela_num' => null, 'data_vencimento' => $data, 'status' => 'Pendente'];
        }
        $this->givenGrupo(202, $grupo);

        $this->service->updateDespesa(202, ['categoria' => 'Aluguel', 'escopo' => 'esta_e_seguintes']);

        $this->assertEqualsCanonicalizing([202, 203], array_keys($calls));
    }

    public function testUpdateDespesaRejeitaEscopoInvalido(): void
    {
        $this->despesaRepo->expects($this->never())->method('getModel');

        $result = $this->service->updateDespesa(1, ['descricao' => 'X', 'escopo' => 'tudo']);

        $this->assertSame('error', $result['status']);
    }

    public function testUpdateDespesaEscopoRetornaErroQuandoDespesaNaoExiste(): void
    {
        $this->despesaRepo->method('findById')->willReturn(null);

        $result = $this->service->updateDespesa(999, ['descricao' => 'X', 'escopo' => 'todas']);

        $this->assertSame('error', $result['status']);
    }

    public function testUpdateDespesaEscopoFalhaNoMeioRetornaErro(): void
    {
        $calls = [];
        $this->mockUpdateRecorder($calls, false);
        $this->givenGrupo(102, $this->grupoParcelas());

        $result = $this->service->updateDespesa(102, ['descricao' => 'X', 'escopo' => 'todas']);

        $this->assertSame('error', $result['status']);
    }

    public function testCancelarDespesaTodasCancelaPendentesEPreservaPagas(): void
    {
        $calls = [];
        $this->mockUpdateRecorder($calls);
        $this->givenGrupo(102, $this->grupoParcelas(['Pago', 'Pendente', 'Pago', 'Pendente']));

        $result = $this->service->cancelarDespesa(102, 'todas');

        $this->assertSame('success', $result['status']);
        $this->assertSame(2, $result['canceladas']);
        $this->assertSame(2, $result['ignoradas_pagas']);
        $this->assertSame([102 => ['status' => 'Cancelado'], 104 => ['status' => 'Cancelado']], $calls);
    }

    public function testCancelarDespesaEstaESeguintesNaParcela3(): void
    {
        $calls = [];
        $this->mockUpdateRecorder($calls);
        $this->givenGrupo(103, $this->grupoParcelas());

        $result = $this->service->cancelarDespesa(103, 'esta_e_seguintes');

        $this->assertSame([103, 104], array_keys($calls));
        $this->assertSame(2, $result['canceladas']);
        $this->assertSame(0, $result['ignoradas_pagas']);
    }

    public function testCancelarDespesaPagaIsoladaRetornaErro(): void
    {
        $calls = [];
        $this->mockUpdateRecorder($calls);
        $this->givenGrupo(101, $this->grupoParcelas());

        $result = $this->service->cancelarDespesa(101);

        $this->assertSame('error', $result['status']);
        $this->assertSame([], $calls);
    }

    public function testCancelarDespesaSemGrupoCancelaSoEla(): void
    {
        $calls = [];
        $this->mockUpdateRecorder($calls);
        $this->despesaRepo->method('findById')->willReturn(['id' => 5, 'des_grupo_id' => null, 'status' => 'Pendente']);

        $result = $this->service->cancelarDespesa(5, 'todas');

        $this->assertSame('success', $result['status']);
        $this->assertSame([5], array_keys($calls));
    }

    public function testCancelarDespesaRejeitaEscopoInvalidoEDespesaInexistente(): void
    {
        $this->despesaRepo->method('findById')->willReturn(null);

        $this->assertSame('error', $this->service->cancelarDespesa(1, 'tudo')['status']);
        $this->assertSame('error', $this->service->cancelarDespesa(999)['status']);
    }

    public function testGetGrupoDespesasRetornaParcelasOuErro(): void
    {
        $this->despesaRepo->method('getByGrupo')->willReturnMap([
            ['g-1', $this->grupoParcelas()],
            ['nao-existe', []],
        ]);

        $ok = $this->service->getGrupoDespesas('g-1');
        $this->assertSame('success', $ok['status']);
        $this->assertCount(4, $ok['parcelas']);

        $this->assertSame('error', $this->service->getGrupoDespesas('nao-existe')['status']);
    }

    // -- listagem de despesas -------------------------------------------------

    public function testGetDespesasRepassaFiltrosEPaginacao(): void
    {
        $this->despesaRepo->expects($this->once())
            ->method('listar')
            ->with('2026-10', 'Pendente', 2, 10)
            ->willReturn(['items' => [['id' => 1]], 'total' => 25]);

        $result = $this->service->getDespesas(['mes' => '2026-10', 'status' => 'Pendente', 'page' => '2', 'per_page' => '10']);

        $this->assertSame('success', $result['status']);
        $this->assertSame(3, $result['data']['total_pages']);
        $this->assertSame(25, $result['data']['total']);
        $this->assertSame(2, $result['data']['page']);
    }

    public function testGetDespesasUsaPadroesQuandoSemFiltros(): void
    {
        $this->despesaRepo->expects($this->once())
            ->method('listar')
            ->with(null, null, 1, 20)
            ->willReturn(['items' => [], 'total' => 0]);

        $result = $this->service->getDespesas(['mes' => '', 'status' => '']);

        $this->assertSame(1, $result['data']['total_pages']);
    }

    public function testGetDespesasLimitaPorPaginaEPaginaMinima(): void
    {
        $this->despesaRepo->expects($this->once())
            ->method('listar')
            ->with(null, null, 1, 100)
            ->willReturn(['items' => [], 'total' => 0]);

        $this->service->getDespesas(['page' => '-3', 'per_page' => '5000']);
    }

    /**
     * @dataProvider filtrosInvalidosProvider
     */
    public function testGetDespesasRejeitaFiltroInvalido(array $filtros): void
    {
        $this->despesaRepo->expects($this->never())->method('listar');

        $this->assertSame('error', $this->service->getDespesas($filtros)['status']);
    }

    public static function filtrosInvalidosProvider(): array
    {
        return [
            'mes sem zero'   => [['mes' => '2026-1']],
            'mes 13'         => [['mes' => '2026-13']],
            'mes com dia'    => [['mes' => '2026-10-01']],
            'status inexistente' => [['status' => 'Atrasado']],
        ];
    }
}
