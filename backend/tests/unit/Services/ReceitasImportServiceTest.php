<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\FatCobrancaModel;
use App\Repositories\ClientesRepository;
use App\Repositories\FatCobrancaRepository;
use App\Repositories\FatNotaRepository;
use App\Repositories\PetsRepository;
use App\Services\ReceitasImportService;
use CodeIgniter\Test\CIUnitTestCase;
use Exception;

/**
 * @internal
 */
final class ReceitasImportServiceTest extends CIUnitTestCase
{
    private FatCobrancaRepository $repository;
    private PetsRepository $petsRepository;
    private ClientesRepository $clientesRepository;
    private FatNotaRepository $notaRepository;
    private ReceitasImportService $service;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(FatCobrancaRepository::class);
        $this->petsRepository = $this->createMock(PetsRepository::class);
        $this->clientesRepository = $this->createMock(ClientesRepository::class);
        $this->notaRepository = $this->createMock(FatNotaRepository::class);

        $this->service = new ReceitasImportService(
            $this->repository,
            $this->petsRepository,
            $this->clientesRepository,
            $this->notaRepository
        );
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
        $path = tempnam(sys_get_temp_dir(), 'receitas_csv_');
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;

        return $path;
    }

    private function mockModelFirst(?array $returnValue): FatCobrancaModel
    {
        $model = $this->getMockBuilder(FatCobrancaModel::class)
            ->onlyMethods(['first'])
            ->getMock();
        $model->method('first')->willReturn($returnValue);

        return $model;
    }

    public function testRecordsErrorWhenClienteOrPetMissingFromRow(): void
    {
        $csv = "Cliente;Pet;Valor documento\n;Rex;100\n";
        $path = $this->createCsv($csv);

        $this->clientesRepository->expects($this->never())->method('findByName');

        $result = $this->service->importFromCsv($path);

        $this->assertSame(0, $result['importados']);
        $this->assertCount(1, $result['erros']);
        $this->assertStringContainsString('não informados', $result['erros'][0]);
    }

    public function testIgnoresRowWhenClienteNotFound(): void
    {
        $csv = "Cliente;Pet;Valor documento\nMaria;Rex;100\n";
        $path = $this->createCsv($csv);

        $this->clientesRepository->method('findByName')->with('Maria')->willReturn(null);
        $this->petsRepository->expects($this->never())->method('findByNomeAndCliente');

        $result = $this->service->importFromCsv($path);

        $this->assertCount(1, $result['ignorados']);
        $this->assertSame('Cliente não encontrado', $result['ignorados'][0]['motivo']);
    }

    public function testIgnoresRowWhenPetNotFound(): void
    {
        $csv = "Cliente;Pet;Valor documento\nMaria;Rex;100\n";
        $path = $this->createCsv($csv);

        $this->clientesRepository->method('findByName')->willReturn(['id' => 1, 'cpf' => '123']);
        $this->petsRepository->method('findByNomeAndCliente')->with('Rex', 1)->willReturn(null);
        $this->repository->expects($this->never())->method('create');

        $result = $this->service->importFromCsv($path);

        $this->assertCount(1, $result['ignorados']);
        $this->assertSame('Pet não encontrado para este cliente', $result['ignorados'][0]['motivo']);
    }

    public function testImportsPaidRowAndCreatesReceiptNota(): void
    {
        $csv = "Cliente;Pet;Valor documento;Situação\nMaria;Rex;150,00;Pago\n";
        $path = $this->createCsv($csv);

        $this->clientesRepository->method('findByName')->willReturn(['id' => 1, 'cpf' => '11122233344']);
        $this->petsRepository->method('findByNomeAndCliente')->willReturn(['paciente_id' => 5]);
        $this->repository->method('getModel')->willReturn($this->mockModelFirst(null));

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(static function (array $data): bool {
                return $data['paciente_id'] === 5 && $data['status'] === 'Pago' && $data['valor'] === 150.0;
            }))
            ->willReturn(20);

        $this->notaRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $data): bool => $data['cobranca_id'] === 20));

        $result = $this->service->importFromCsv($path);

        $this->assertSame(1, $result['importados']);
    }

    public function testImportsPendingRowWithoutCreatingNota(): void
    {
        $csv = "Cliente;Pet;Valor documento;Situação\nMaria;Rex;150,00;Pendente\n";
        $path = $this->createCsv($csv);

        $this->clientesRepository->method('findByName')->willReturn(['id' => 1, 'cpf' => '11122233344']);
        $this->petsRepository->method('findByNomeAndCliente')->willReturn(['paciente_id' => 5]);
        $this->repository->method('getModel')->willReturn($this->mockModelFirst(null));
        $this->repository->method('create')->willReturn(21);

        $this->notaRepository->expects($this->never())->method('create');

        $result = $this->service->importFromCsv($path);

        $this->assertSame(1, $result['importados']);
    }

    public function testIgnoresDuplicateRow(): void
    {
        $csv = "Cliente;Pet;Valor documento;Situação\nMaria;Rex;150,00;Pendente\n";
        $path = $this->createCsv($csv);

        $this->clientesRepository->method('findByName')->willReturn(['id' => 1, 'cpf' => '11122233344']);
        $this->petsRepository->method('findByNomeAndCliente')->willReturn(['paciente_id' => 5]);
        $this->repository->method('getModel')->willReturn($this->mockModelFirst(['id' => 99]));
        $this->repository->expects($this->never())->method('create');

        $result = $this->service->importFromCsv($path);

        $this->assertCount(1, $result['ignorados']);
        $this->assertStringContainsString('duplicado', $result['ignorados'][0]['motivo']);
    }

    public function testRecordsErrorWhenRepositoryThrows(): void
    {
        $csv = "Cliente;Pet;Valor documento;Situação\nMaria;Rex;150,00;Pendente\n";
        $path = $this->createCsv($csv);

        $this->clientesRepository->method('findByName')->willReturn(['id' => 1, 'cpf' => '11122233344']);
        $this->petsRepository->method('findByNomeAndCliente')->willReturn(['paciente_id' => 5]);
        $this->repository->method('getModel')->willReturn($this->mockModelFirst(null));
        $this->repository->method('create')->willThrowException(new Exception('db offline'));

        $result = $this->service->importFromCsv($path);

        $this->assertSame(0, $result['importados']);
        $this->assertStringContainsString('db offline', $result['erros'][0]);
    }
}
