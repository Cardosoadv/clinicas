<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\ServicoProdutosRepository;
use App\Repositories\ServicosRepository;
use App\Services\ServicosService;
use CodeIgniter\Test\CIUnitTestCase;
use Exception;

/**
 * @internal
 */
final class ServicosServiceTest extends CIUnitTestCase
{
    private ServicosRepository $servicosRepo;
    private ServicoProdutosRepository $servicoProdutosRepo;
    private ServicosService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicosRepo = $this->createMock(ServicosRepository::class);
        $this->servicoProdutosRepo = $this->createMock(ServicoProdutosRepository::class);
        $this->service = new ServicosService($this->servicosRepo, $this->servicoProdutosRepo);
    }

    public function testGetAllActiveDelegatesToRepository(): void
    {
        $this->servicosRepo->expects($this->once())
            ->method('findAllActive')
            ->willReturn([['ser_id' => 1, 'ser_nome' => 'Banho']]);

        $this->assertSame([['ser_id' => 1, 'ser_nome' => 'Banho']], $this->service->getAllActive());
    }

    public function testGetServiceWithProductsReturnsNullWhenServiceNotFound(): void
    {
        $this->servicosRepo->method('findById')->willReturn(null);
        $this->servicoProdutosRepo->expects($this->never())->method('findByServico');

        $this->assertNull($this->service->getServiceWithProducts(99));
    }

    public function testGetServiceWithProductsAttachesLinkedProducts(): void
    {
        $this->servicosRepo->method('findById')->with(1)->willReturn(['ser_id' => 1, 'ser_nome' => 'Banho']);
        $this->servicoProdutosRepo->method('findByServico')->with(1)->willReturn([['produto_id' => 5]]);

        $result = $this->service->getServiceWithProducts(1);

        $this->assertSame(1, $result['ser_id']);
        $this->assertSame([['produto_id' => 5]], $result['produtos']);
    }

    public function testSyncProductsReturnsSuccess(): void
    {
        $this->servicoProdutosRepo->expects($this->once())
            ->method('sync')
            ->with(1, [5, 6]);

        $result = $this->service->syncProducts(1, [5, 6]);

        $this->assertSame('success', $result['status']);
        $this->assertSame('Produtos vinculados com sucesso.', $result['message']);
    }

    public function testSyncProductsReturnsErrorWhenSyncThrows(): void
    {
        $this->servicoProdutosRepo->method('sync')->willThrowException(new Exception('fk violation'));

        $result = $this->service->syncProducts(1, [5, 6]);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro ao vincular produtos: fk violation', $result['message']);
    }
}
