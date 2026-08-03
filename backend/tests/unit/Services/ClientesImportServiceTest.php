<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\ClientesRepository;
use App\Services\ClientesImportService;
use CodeIgniter\Test\CIUnitTestCase;
use Exception;

/**
 * @internal
 */
final class ClientesImportServiceTest extends CIUnitTestCase
{
    private ClientesRepository $repository;
    private ClientesImportService $service;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ClientesRepository::class);
        $this->service = new ClientesImportService($this->repository);
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
        $path = tempnam(sys_get_temp_dir(), 'clientes_csv_');
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;

        return $path;
    }

    public function testRaisesErrorWhenFileCannotBeOpened(): void
    {
        // CodeIgniter's error handler converts the fopen() warning for a
        // missing file into an ErrorException before importFromCsv()'s own
        // "could not open file" branch can run.
        $this->expectException(\ErrorException::class);

        $this->service->importFromCsv('/path/does/not/exist.csv');
    }

    public function testReturnsErrorWhenFileIsEmpty(): void
    {
        $path = $this->createCsv('');

        $result = $this->service->importFromCsv($path);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Arquivo vazio.', $result['message']);
    }

    public function testImportsValidRowsAndConvertsDate(): void
    {
        $csv = "Nome;CPF;Nascimento;Telefones\n"
            . "João da Silva;11122233344;25/12/1990;11999998888\n";
        $path = $this->createCsv($csv);

        $this->repository->method('findByCpf')->willReturn(null);
        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(static function (array $data): bool {
                return $data['nome'] === 'João da Silva'
                    && $data['cpf'] === '11122233344'
                    && $data['nascimento'] === '1990-12-25';
            }))
            ->willReturn(1);

        $result = $this->service->importFromCsv($path);

        $this->assertSame('success', $result['status']);
        $this->assertSame(1, $result['importados']);
        $this->assertSame(1, $result['total_processado']);
        $this->assertSame([], $result['ignorados']);
        $this->assertSame([], $result['erros']);
    }

    public function testIgnoresRowsWithDuplicateCpf(): void
    {
        $csv = "Nome;CPF\nJoão da Silva;11122233344\n";
        $path = $this->createCsv($csv);

        $this->repository->method('findByCpf')->willReturn(['id' => 5, 'cpf' => '11122233344']);
        $this->repository->expects($this->never())->method('create');

        $result = $this->service->importFromCsv($path);

        $this->assertSame(0, $result['importados']);
        $this->assertCount(1, $result['ignorados']);
        $this->assertStringContainsString('CPF já cadastrado', $result['ignorados'][0]['motivo']);
    }

    public function testRecordsErrorWhenNameIsMissing(): void
    {
        $csv = "Nome;CPF\n;11122233344\n";
        $path = $this->createCsv($csv);

        $this->repository->expects($this->never())->method('create');

        $result = $this->service->importFromCsv($path);

        $this->assertSame(0, $result['importados']);
        $this->assertCount(1, $result['erros']);
        $this->assertStringContainsString('Nome não informado', $result['erros'][0]);
    }

    public function testRecordsErrorWhenRepositoryThrows(): void
    {
        $csv = "Nome;CPF\nJoão da Silva;11122233344\n";
        $path = $this->createCsv($csv);

        $this->repository->method('findByCpf')->willReturn(null);
        $this->repository->method('create')->willThrowException(new Exception('constraint violation'));

        $result = $this->service->importFromCsv($path);

        $this->assertSame(0, $result['importados']);
        $this->assertCount(1, $result['erros']);
        $this->assertStringContainsString('constraint violation', $result['erros'][0]);
    }

    public function testDetectsCommaDelimiterWhenNoSemicolonPresent(): void
    {
        $csv = "Nome,CPF\nJoão da Silva,11122233344\n";
        $path = $this->createCsv($csv);

        $this->repository->method('findByCpf')->willReturn(null);
        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $data): bool => $data['nome'] === 'João da Silva'))
            ->willReturn(1);

        $result = $this->service->importFromCsv($path);

        $this->assertSame(1, $result['importados']);
    }
}
