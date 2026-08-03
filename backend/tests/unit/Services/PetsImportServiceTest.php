<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\ClientesRepository;
use App\Repositories\PetsRepository;
use App\Services\PetsImportService;
use CodeIgniter\Test\CIUnitTestCase;
use Exception;

/**
 * @internal
 */
final class PetsImportServiceTest extends CIUnitTestCase
{
    private PetsRepository $repository;
    private ClientesRepository $clientesRepository;
    private PetsImportService $service;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(PetsRepository::class);
        $this->clientesRepository = $this->createMock(ClientesRepository::class);
        $this->service = new PetsImportService($this->repository, $this->clientesRepository);
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
        $path = tempnam(sys_get_temp_dir(), 'pets_csv_');
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;

        return $path;
    }

    public function testReturnsErrorWhenFileIsEmpty(): void
    {
        $result = $this->service->importFromCsv($this->createCsv(''));

        $this->assertSame('error', $result['status']);
        $this->assertSame('Arquivo CSV vazio ou inválido.', $result['message']);
    }

    public function testRecordsErrorWhenIdClienteIsMissing(): void
    {
        $csv = "Nome Pet;Espécie;ID Cliente\nRex;Cão;\n";
        $path = $this->createCsv($csv);

        $this->clientesRepository->expects($this->never())->method('findByExportacao');

        $result = $this->service->importFromCsv($path);

        $this->assertSame(0, $result['importados']);
        $this->assertCount(1, $result['erros']);
        $this->assertStringContainsString('ID Cliente não informado', $result['erros'][0]);
    }

    public function testIgnoresRowWhenTutorNotFound(): void
    {
        $csv = "Nome Pet;Espécie;ID Cliente\nRex;Cão;123\n";
        $path = $this->createCsv($csv);

        $this->clientesRepository->method('findByExportacao')->with('123')->willReturn(null);
        $this->repository->expects($this->never())->method('create');

        $result = $this->service->importFromCsv($path);

        $this->assertSame(0, $result['importados']);
        $this->assertCount(1, $result['ignorados']);
        $this->assertStringContainsString('Tutor não encontrado', $result['ignorados'][0]['motivo']);
    }

    public function testIgnoresDuplicatePetForSameTutor(): void
    {
        $csv = "Nome Pet;Espécie;ID Cliente\nRex;Cão;123\n";
        $path = $this->createCsv($csv);

        $this->clientesRepository->method('findByExportacao')->willReturn(['id' => 4]);
        $this->repository->method('findByNomeAndCliente')->with('Rex', 4)->willReturn(['paciente_id' => 1]);
        $this->repository->expects($this->never())->method('create');

        $result = $this->service->importFromCsv($path);

        $this->assertCount(1, $result['ignorados']);
        $this->assertStringContainsString('já cadastrado', $result['ignorados'][0]['motivo']);
    }

    public function testImportsValidRowAndNormalizesStatus(): void
    {
        $csv = "Nome Pet;Espécie;ID Cliente;Ativo\nRex;Cão;123;sim\n";
        $path = $this->createCsv($csv);

        $this->clientesRepository->method('findByExportacao')->willReturn(['id' => 4]);
        $this->repository->method('findByNomeAndCliente')->willReturn(null);

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(static function (array $data): bool {
                return $data['paciente_nome'] === 'Rex'
                    && $data['cliente_id'] === 4
                    && $data['paciente_status'] === 'Inativo';
            }))
            ->willReturn(10);

        $result = $this->service->importFromCsv($path);

        $this->assertSame(1, $result['importados']);
    }

    public function testRecordsErrorWhenRepositoryThrows(): void
    {
        $csv = "Nome Pet;Espécie;ID Cliente\nRex;Cão;123\n";
        $path = $this->createCsv($csv);

        $this->clientesRepository->method('findByExportacao')->willReturn(['id' => 4]);
        $this->repository->method('findByNomeAndCliente')->willReturn(null);
        $this->repository->method('create')->willThrowException(new Exception('db offline'));

        $result = $this->service->importFromCsv($path);

        $this->assertSame(0, $result['importados']);
        $this->assertStringContainsString('db offline', $result['erros'][0]);
    }
}
