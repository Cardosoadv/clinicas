<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\PrescricoesRepository;
use App\Services\PrescricoesService;
use CodeIgniter\Test\CIUnitTestCase;
use Exception;

/**
 * @internal
 */
final class PrescricoesServiceTest extends CIUnitTestCase
{
    private PrescricoesRepository $repository;
    private PrescricoesService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(PrescricoesRepository::class);
        $this->service = new PrescricoesService($this->repository);
    }

    public function testCreatePrescricaoWithItemsSucceeds(): void
    {
        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(static function (array $data): bool {
                return $data['paciente_id'] === 1
                    && $data['veterinario_id'] === 2
                    && $data['observacoes'] === 'Obs';
            }))
            ->willReturn(50);

        $this->repository->expects($this->exactly(2))
            ->method('createItem')
            ->willReturn(1);

        $result = $this->service->createPrescricao([
            'paciente_id'    => 1,
            'veterinario_id' => 2,
            'observacoes'    => 'Obs',
            'itens'          => [
                ['medicamento' => 'Amoxicilina', 'dosagem' => '10ml'],
                ['medicamento' => 'Dipirona', 'dosagem' => '5ml'],
            ],
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertSame('Prescrição registrada com sucesso.', $result['message']);
        $this->assertSame(50, $result['id']);
    }

    public function testCreatePrescricaoWithoutItemsSucceeds(): void
    {
        $this->repository->method('create')->willReturn(51);
        $this->repository->expects($this->never())->method('createItem');

        $result = $this->service->createPrescricao(['paciente_id' => 1]);

        $this->assertSame('success', $result['status']);
        $this->assertSame(51, $result['id']);
    }

    public function testCreatePrescricaoReturnsErrorWhenHeaderCreationFails(): void
    {
        $this->repository->method('create')->willReturn(0);
        $this->repository->expects($this->never())->method('createItem');

        $result = $this->service->createPrescricao(['paciente_id' => 1]);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro: Falha ao criar cabeçalho da prescrição.', $result['message']);
    }

    public function testCreatePrescricaoReturnsErrorWhenRepositoryThrows(): void
    {
        $this->repository->method('create')->willThrowException(new Exception('db error'));

        $result = $this->service->createPrescricao(['paciente_id' => 1]);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro: db error', $result['message']);
    }

    public function testGetByPetDelegatesToRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('findByPet')
            ->with(7)
            ->willReturn([['id' => 1]]);

        $this->assertSame([['id' => 1]], $this->service->getByPet(7));
    }

    public function testGetDetailsDelegatesToRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('getWithItems')
            ->with(9)
            ->willReturn(['id' => 9, 'itens' => []]);

        $this->assertSame(['id' => 9, 'itens' => []], $this->service->getDetails(9));
    }

    public function testGetDetailsReturnsNullWhenNotFound(): void
    {
        $this->repository->method('getWithItems')->willReturn(null);

        $this->assertNull($this->service->getDetails(999));
    }
}
