<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\BaseRepository;
use App\Services\BaseService;
use CodeIgniter\Test\CIUnitTestCase;
use Exception;

/**
 * @internal
 */
final class BaseServiceTest extends CIUnitTestCase
{
    private BaseRepository $repository;
    private BaseService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(BaseRepository::class);

        $this->service = new class ($this->repository) extends BaseService {
            public function __construct(BaseRepository $repository)
            {
                $this->repository = $repository;
            }
        };
    }

    public function testFindAllDelegatesToRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with(10, 5)
            ->willReturn([['id' => 1]]);

        $this->assertSame([['id' => 1]], $this->service->findAll(10, 5));
    }

    public function testFindAllDefaultsToNoLimitAndOffset(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with(0, 0)
            ->willReturn([]);

        $this->assertSame([], $this->service->findAll());
    }

    public function testGetAllIsAnAliasForFindAll(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->with(20, 0)
            ->willReturn([['id' => 2]]);

        $this->assertSame([['id' => 2]], $this->service->getAll(20));
    }

    public function testFindByIdDelegatesToRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn(['id' => 42]);

        $this->assertSame(['id' => 42], $this->service->findById(42));
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->assertNull($this->service->findById(999));
    }

    public function testCreateReturnsSuccessWithNewId(): void
    {
        $this->repository->expects($this->once())
            ->method('create')
            ->with(['name' => 'Foo'])
            ->willReturn(7);

        $result = $this->service->create(['name' => 'Foo']);

        $this->assertSame('success', $result['status']);
        $this->assertSame('Registro criado com sucesso', $result['message']);
        $this->assertSame(7, $result['id']);
    }

    public function testCreateReturnsErrorWhenRepositoryReturnsZero(): void
    {
        $this->repository->method('create')->willReturn(0);

        $result = $this->service->create(['name' => 'Foo']);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro ao criar registro', $result['message']);
    }

    public function testCreateReturnsErrorWhenRepositoryThrows(): void
    {
        $this->repository->method('create')->willThrowException(new Exception('boom'));

        $result = $this->service->create(['name' => 'Foo']);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro interno: boom', $result['message']);
    }

    public function testUpdateReturnsSuccessWhenRepositoryReturnsTrue(): void
    {
        $this->repository->expects($this->once())
            ->method('update')
            ->with(1, ['name' => 'Bar'])
            ->willReturn(true);

        $result = $this->service->update(1, ['name' => 'Bar']);

        $this->assertSame('success', $result['status']);
        $this->assertSame('Registro atualizado com sucesso', $result['message']);
    }

    public function testUpdateReturnsErrorWhenRepositoryReturnsFalse(): void
    {
        $this->repository->method('update')->willReturn(false);

        $result = $this->service->update(1, ['name' => 'Bar']);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro ao atualizar registro', $result['message']);
    }

    public function testUpdateReturnsErrorWhenRepositoryThrows(): void
    {
        $this->repository->method('update')->willThrowException(new Exception('update failed'));

        $result = $this->service->update(1, ['name' => 'Bar']);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro interno: update failed', $result['message']);
    }

    public function testDeleteReturnsSuccessWhenRepositoryReturnsTrue(): void
    {
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(3)
            ->willReturn(true);

        $result = $this->service->delete(3);

        $this->assertSame('success', $result['status']);
        $this->assertSame('Registro excluído com sucesso', $result['message']);
    }

    public function testDeleteReturnsErrorWhenRepositoryReturnsFalse(): void
    {
        $this->repository->method('delete')->willReturn(false);

        $result = $this->service->delete(3);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro ao excluir registro', $result['message']);
    }

    public function testDeleteReturnsErrorWhenRepositoryThrows(): void
    {
        $this->repository->method('delete')->willThrowException(new Exception('delete failed'));

        $result = $this->service->delete(3);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro interno: delete failed', $result['message']);
    }

    public function testGetPagerDelegatesToRepository(): void
    {
        $pager = new \stdClass();

        $this->repository->expects($this->once())
            ->method('getPager')
            ->willReturn($pager);

        $this->assertSame($pager, $this->service->getPager());
    }

    public function testGetRepositoryReturnsInjectedRepository(): void
    {
        $this->assertSame($this->repository, $this->service->getRepository());
    }
}
