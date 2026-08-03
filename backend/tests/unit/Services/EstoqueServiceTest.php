<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\EstoqueMovimentacoesRepository;
use App\Repositories\EstoqueProdutosRepository;
use App\Repositories\ServicoProdutosRepository;
use App\Services\EstoqueService;
use App\Services\FatService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class EstoqueServiceTest extends CIUnitTestCase
{
    private EstoqueProdutosRepository $produtosRepo;
    private EstoqueMovimentacoesRepository $movimentacoesRepo;
    private ServicoProdutosRepository $servicoProdutosRepo;
    private FatService $fatService;
    private EstoqueService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->produtosRepo = $this->createMock(EstoqueProdutosRepository::class);
        $this->movimentacoesRepo = $this->createMock(EstoqueMovimentacoesRepository::class);
        $this->servicoProdutosRepo = $this->createMock(ServicoProdutosRepository::class);
        $this->fatService = $this->createMock(FatService::class);

        $this->service = new EstoqueService(
            $this->produtosRepo,
            $this->movimentacoesRepo,
            $this->servicoProdutosRepo,
            $this->fatService
        );
    }

    public function testRegistrarEntradaIncreasesStock(): void
    {
        $this->produtosRepo->method('findById')->with(1)->willReturn(['id' => 1, 'quantidade_atual' => 10]);
        $this->movimentacoesRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $d): bool => $d['tipo'] === 'Entrada' && $d['quantidade'] === 5));
        $this->produtosRepo->expects($this->once())
            ->method('update')
            ->with(1, ['quantidade_atual' => 15]);

        $result = $this->service->registrarEntrada(['produto_id' => 1, 'quantidade' => 5]);

        $this->assertSame('success', $result['status']);
    }

    public function testRegistrarEntradaCreatesDespesaWhenLancarFinanceiro(): void
    {
        $this->produtosRepo->method('findById')->willReturn(['id' => 1, 'quantidade_atual' => 10]);
        $this->fatService->expects($this->once())
            ->method('createDespesa')
            ->willReturn(['status' => 'success', 'id' => 55]);

        $this->movimentacoesRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $d): bool => ($d['despesa_id'] ?? null) === 55));

        $result = $this->service->registrarEntrada([
            'produto_id'         => 1,
            'quantidade'         => 5,
            'lancar_financeiro'  => true,
            'valor_total'        => 100,
        ]);

        $this->assertSame('success', $result['status']);
    }

    public function testRegistrarEntradaFailsWhenFinanceiroLancamentoFails(): void
    {
        $this->produtosRepo->method('findById')->willReturn(['id' => 1, 'quantidade_atual' => 10]);
        $this->fatService->method('createDespesa')->willReturn(['status' => 'error', 'message' => 'fail']);
        $this->movimentacoesRepo->expects($this->never())->method('create');

        $result = $this->service->registrarEntrada([
            'produto_id'        => 1,
            'quantidade'        => 5,
            'lancar_financeiro' => true,
            'valor_total'       => 100,
        ]);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro ao lançar no financeiro.', $result['message']);
    }

    public function testRegistrarEntradaFailsWhenProductNotFound(): void
    {
        $this->produtosRepo->method('findById')->willReturn(null);
        $this->produtosRepo->expects($this->never())->method('update');

        $result = $this->service->registrarEntrada(['produto_id' => 99, 'quantidade' => 5]);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Produto não encontrado.', $result['message']);
    }

    public function testRegistrarSaidaDecreasesStock(): void
    {
        $this->produtosRepo->method('findById')->willReturn(['id' => 1, 'quantidade_atual' => 10]);
        $this->produtosRepo->expects($this->once())->method('update')->with(1, ['quantidade_atual' => 7]);

        $result = $this->service->registrarSaida(['produto_id' => 1, 'quantidade' => 3]);

        $this->assertSame('success', $result['status']);
    }

    public function testRegistrarSaidaFailsWhenInsufficientStock(): void
    {
        $this->produtosRepo->method('findById')->willReturn(['id' => 1, 'quantidade_atual' => 2]);
        $this->produtosRepo->expects($this->never())->method('update');

        $result = $this->service->registrarSaida(['produto_id' => 1, 'quantidade' => 3]);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Quantidade em estoque insuficiente.', $result['message']);
    }

    public function testRegistrarSaidaFailsWhenProductNotFound(): void
    {
        $this->produtosRepo->method('findById')->willReturn(null);

        $result = $this->service->registrarSaida(['produto_id' => 99, 'quantidade' => 3]);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Produto não encontrado.', $result['message']);
    }

    public function testGetLowStockAlertsDelegatesToRepository(): void
    {
        $this->produtosRepo->expects($this->once())
            ->method('getLowStockAlerts')
            ->willReturn([['id' => 1, 'nome' => 'Ração']]);

        $this->assertSame([['id' => 1, 'nome' => 'Ração']], $this->service->getLowStockAlerts());
    }

    public function testDeduzirEstoquePorServicoReturnsSuccessWhenNoLinkedProducts(): void
    {
        $this->servicoProdutosRepo->method('findByServico')->with(1)->willReturn([]);

        $result = $this->service->deduzirEstoquePorServico(1);

        $this->assertSame('success', $result['status']);
        $this->assertSame('Nenhum produto vinculado a este serviço.', $result['message']);
    }

    public function testDeduzirEstoquePorServicoDeductsAllLinkedProducts(): void
    {
        $this->servicoProdutosRepo->method('findByServico')->willReturn([
            ['produto_id' => 1, 'quantidade' => '2', 'produto_nome' => 'Shampoo'],
        ]);
        $this->produtosRepo->method('findById')->willReturn(['id' => 1, 'quantidade_atual' => 10]);
        $this->produtosRepo->expects($this->once())->method('update')->with(1, ['quantidade_atual' => 8.0]);

        $result = $this->service->deduzirEstoquePorServico(1, 1.0);

        $this->assertSame('success', $result['status']);
        $this->assertStringContainsString('1 produtos vinculados', $result['message']);
    }

    public function testDeduzirEstoquePorServicoReportsPartialFailures(): void
    {
        $this->servicoProdutosRepo->method('findByServico')->willReturn([
            ['produto_id' => 1, 'quantidade' => '2', 'produto_nome' => 'Shampoo'],
        ]);
        $this->produtosRepo->method('findById')->willReturn(null);

        $result = $this->service->deduzirEstoquePorServico(1);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('Dedução parcial', $result['message']);
    }
}
