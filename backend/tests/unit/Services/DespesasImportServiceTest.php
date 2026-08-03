<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\FatDespesaModel;
use App\Repositories\FatDespesaRepository;
use App\Services\DespesasImportService;
use CodeIgniter\Test\CIUnitTestCase;
use Exception;

/**
 * @internal
 */
final class DespesasImportServiceTest extends CIUnitTestCase
{
    private FatDespesaRepository $repository;
    private DespesasImportService $service;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(FatDespesaRepository::class);
        $this->service = new DespesasImportService($this->repository);
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }

    private function createCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'despesas_csv_');
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;

        return $path;
    }

    /**
     * Builds a real Model instance with only findAll() overridden, so the
     * magic query-builder methods (where/orGroupStart/groupEnd) used by the
     * service run for real (they never touch the DB) while the terminal
     * findAll() call is fully controlled by the test.
     */
    private function mockModelFindAll(array $returnValue): FatDespesaModel
    {
        $model = $this->getMockBuilder(FatDespesaModel::class)
            ->onlyMethods(['findAll'])
            ->getMock();
        $model->method('findAll')->willReturn($returnValue);

        return $model;
    }

    public function testReturnsErrorWhenFileCannotBeOpened(): void
    {
        $this->expectException(\ErrorException::class);
        $this->service->importFromCsv('/path/does/not/exist.csv');
    }

    public function testReturnsErrorWhenFileIsEmpty(): void
    {
        $result = $this->service->importFromCsv($this->createCsv(''));

        $this->assertSame('error', $result['status']);
        $this->assertSame('Arquivo CSV vazio ou inválido.', $result['message']);
    }

    public function testIgnoresRowWithZeroOrInvalidValue(): void
    {
        $csv = "Situação;Vencimento;Razão;Valor documento\nPendente;10/08/2026;Fornecedor X;0\n";
        $path = $this->createCsv($csv);

        $this->repository->method('getModel')->willReturn($this->mockModelFindAll([]));
        $this->repository->expects($this->never())->method('create');

        $result = $this->service->importFromCsv($path);

        $this->assertSame(0, $result['importados']);
        $this->assertCount(1, $result['ignorados']);
        $this->assertStringContainsString('Valor inválido', $result['ignorados'][0]['motivo']);
    }

    public function testImportsValidRowMappingStatusAndFormaPagamento(): void
    {
        $csv = "Situação;Vencimento;Razão;Valor documento;Tipo documento\n"
            . "Pago;10/08/2026;Fornecedor X;150,50;Pix\n";
        $path = $this->createCsv($csv);

        $this->repository->method('getModel')->willReturn($this->mockModelFindAll([]));
        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(static function (array $data): bool {
                return $data['fornecedor'] === 'Fornecedor X'
                    && $data['valor'] === 150.5
                    && $data['status'] === 'Pago'
                    && $data['forma_pagamento'] === 'pix'
                    && $data['data_vencimento'] === '2026-08-10';
            }))
            ->willReturn(1);

        $result = $this->service->importFromCsv($path);

        $this->assertSame(1, $result['importados']);
        $this->assertSame([], $result['ignorados']);
    }

    public function testIgnoresProbableDuplicateFoundInDatabase(): void
    {
        $csv = "Situação;Vencimento;Razão;Valor documento\nPendente;10/08/2026;Fornecedor X;150,50\n";
        $path = $this->createCsv($csv);

        $existing = [
            ['fornecedor' => 'Fornecedor X', 'valor' => '150.50', 'data_vencimento' => '2026-08-10'],
        ];
        $this->repository->method('getModel')->willReturn($this->mockModelFindAll($existing));
        $this->repository->expects($this->never())->method('create');

        $result = $this->service->importFromCsv($path);

        $this->assertSame(0, $result['importados']);
        $this->assertCount(1, $result['ignorados']);
        $this->assertStringContainsString('duplicado', $result['ignorados'][0]['motivo']);
    }

    public function testRecordsErrorWhenRepositoryThrows(): void
    {
        $csv = "Situação;Vencimento;Razão;Valor documento\nPendente;10/08/2026;Fornecedor X;150,50\n";
        $path = $this->createCsv($csv);

        $this->repository->method('getModel')->willReturn($this->mockModelFindAll([]));
        $this->repository->method('create')->willThrowException(new Exception('db offline'));

        $result = $this->service->importFromCsv($path);

        $this->assertSame(0, $result['importados']);
        $this->assertStringContainsString('db offline', $result['erros'][0]);
    }

    public function testSkipsCompletelyEmptyLines(): void
    {
        $csv = "Situação;Vencimento;Razão;Valor documento\n;;;\n";
        $path = $this->createCsv($csv);

        $this->repository->expects($this->never())->method('getModel');
        $this->repository->expects($this->never())->method('create');

        $result = $this->service->importFromCsv($path);

        $this->assertSame(0, $result['importados']);
        $this->assertSame([], $result['ignorados']);
        $this->assertSame([], $result['erros']);
    }
}
