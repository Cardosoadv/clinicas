<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\RelatoriosRepository;
use App\Services\RelatoriosService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class RelatoriosServiceTest extends CIUnitTestCase
{
    private RelatoriosRepository $repository;
    private RelatoriosService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(RelatoriosRepository::class);
        $this->service = new RelatoriosService($this->repository);
    }

    public function testResolvePeriodoDefaultsToCurrentMonthWhenNoFilters(): void
    {
        $periodo = $this->service->resolvePeriodo([]);

        $this->assertSame(date('Y-m-01'), $periodo['inicio']);
        $this->assertSame(date('Y-m-t'), $periodo['fim']);
    }

    public function testResolvePeriodoUsesProvidedFilters(): void
    {
        $periodo = $this->service->resolvePeriodo(['inicio' => '2026-01-01', 'fim' => '2026-01-31']);

        $this->assertSame('2026-01-01', $periodo['inicio']);
        $this->assertSame('2026-01-31', $periodo['fim']);
        $this->assertSame('01/01/2026', $periodo['inicio_fmt']);
        $this->assertSame('31/01/2026', $periodo['fim_fmt']);
    }

    public function testResolvePeriodoSwapsInvertedDates(): void
    {
        $periodo = $this->service->resolvePeriodo(['inicio' => '2026-01-31', 'fim' => '2026-01-01']);

        $this->assertSame('2026-01-01', $periodo['inicio']);
        $this->assertSame('2026-01-31', $periodo['fim']);
    }

    public function testGetExtratoDataCombinesAndSortsLancamentos(): void
    {
        $this->repository->method('getCobrancasExtrato')->willReturn([
            ['id' => 1, 'data_servico' => '2026-01-15', 'paciente_nome' => 'Rex', 'servico' => 'Banho', 'valor' => '100.00', 'status' => 'Pago', 'forma_pagamento' => 'Pix'],
        ]);
        $this->repository->method('getDespesasExtrato')->willReturn([
            ['id' => 2, 'data_vencimento' => '2026-01-10', 'fornecedor' => 'Fornecedor', 'descricao' => 'Ração', 'valor' => '40.00', 'status' => 'Pendente', 'forma_pagamento' => 'Boleto', 'categoria' => 'Estoque'],
        ]);

        $result = $this->service->getExtratoData(['inicio' => '2026-01-01', 'fim' => '2026-01-31']);

        $this->assertCount(2, $result['lancamentos']);
        // Sorted chronologically: despesa (01-10) before cobranca (01-15)
        $this->assertSame('despesa', $result['lancamentos'][0]['tipo']);
        $this->assertSame('cobranca', $result['lancamentos'][1]['tipo']);
        $this->assertSame(100.0, $result['total_entradas']);
        $this->assertSame(40.0, $result['total_saidas']);
        $this->assertSame(60.0, $result['saldo_periodo']);
    }

    public function testGetExtratoDataFiltersByTipoEntradas(): void
    {
        $this->repository->method('getCobrancasExtrato')->willReturn([
            ['id' => 1, 'data_servico' => '2026-01-15', 'paciente_nome' => 'Rex', 'servico' => 'Banho', 'valor' => '100.00', 'status' => 'Pago', 'forma_pagamento' => 'Pix'],
        ]);
        $this->repository->method('getDespesasExtrato')->willReturn([
            ['id' => 2, 'data_vencimento' => '2026-01-10', 'fornecedor' => 'Fornecedor', 'descricao' => 'Ração', 'valor' => '40.00', 'status' => 'Pendente', 'forma_pagamento' => 'Boleto', 'categoria' => 'Estoque'],
        ]);

        $result = $this->service->getExtratoData(['inicio' => '2026-01-01', 'fim' => '2026-01-31', 'tipo' => 'entradas']);

        $this->assertCount(1, $result['lancamentos']);
        $this->assertSame('cobranca', $result['lancamentos'][0]['tipo']);
    }

    public function testGetExtratoDataFiltersByStatus(): void
    {
        $this->repository->method('getCobrancasExtrato')->willReturn([
            ['id' => 1, 'data_servico' => '2026-01-15', 'paciente_nome' => 'Rex', 'servico' => 'Banho', 'valor' => '100.00', 'status' => 'Pago', 'forma_pagamento' => 'Pix'],
        ]);
        $this->repository->method('getDespesasExtrato')->willReturn([
            ['id' => 2, 'data_vencimento' => '2026-01-10', 'fornecedor' => 'Fornecedor', 'descricao' => 'Ração', 'valor' => '40.00', 'status' => 'Pendente', 'forma_pagamento' => 'Boleto', 'categoria' => 'Estoque'],
        ]);

        $result = $this->service->getExtratoData(['inicio' => '2026-01-01', 'fim' => '2026-01-31', 'status' => 'pago']);

        $this->assertCount(1, $result['lancamentos']);
        $this->assertSame('Pago', $result['lancamentos'][0]['status']);
    }

    public function testGetExtratoDataListaCanceladosMasNaoSomaNosTotais(): void
    {
        $this->repository->method('getCobrancasExtrato')->willReturn([
            ['id' => 1, 'data_servico' => '2026-01-15', 'paciente_nome' => 'Rex', 'servico' => 'Banho', 'valor' => '100.00', 'status' => 'Pago', 'forma_pagamento' => 'Pix'],
            ['id' => 3, 'data_servico' => '2026-01-16', 'paciente_nome' => 'Rex', 'servico' => 'Tosa', 'valor' => '70.00', 'status' => 'Cancelado', 'forma_pagamento' => 'Pix'],
        ]);
        $this->repository->method('getDespesasExtrato')->willReturn([
            ['id' => 2, 'data_vencimento' => '2026-01-10', 'fornecedor' => 'Loja', 'descricao' => 'Equipamento', 'valor' => '40.00', 'status' => 'Pago', 'categoria' => 'Equipamentos', 'des_parcela_num' => 1, 'des_parcela_total' => 3],
            ['id' => 4, 'data_vencimento' => '2026-01-20', 'fornecedor' => 'Loja', 'descricao' => 'Equipamento', 'valor' => '40.00', 'status' => 'Cancelado', 'categoria' => 'Equipamentos', 'des_parcela_num' => 2, 'des_parcela_total' => 3],
        ]);

        $result = $this->service->getExtratoData(['inicio' => '2026-01-01', 'fim' => '2026-01-31']);

        $this->assertCount(4, $result['lancamentos']);
        $this->assertSame(100.0, $result['total_entradas']);
        $this->assertSame(40.0, $result['total_saidas']);
        $this->assertSame(60.0, $result['saldo_periodo']);
    }

    public function testGetExtratoDataIndicaNumeroDaParcelaNoHistorico(): void
    {
        $this->repository->method('getCobrancasExtrato')->willReturn([]);
        $this->repository->method('getDespesasExtrato')->willReturn([
            ['id' => 2, 'data_vencimento' => '2026-01-10', 'fornecedor' => 'Loja', 'descricao' => 'Equipamento', 'valor' => '40.00', 'status' => 'Pendente', 'categoria' => 'Equipamentos', 'des_parcela_num' => 2, 'des_parcela_total' => 6],
            ['id' => 3, 'data_vencimento' => '2026-01-11', 'fornecedor' => 'Loja', 'descricao' => 'Ração', 'valor' => '10.00', 'status' => 'Pendente', 'categoria' => 'Estoque', 'des_parcela_num' => null, 'des_parcela_total' => null],
        ]);

        $result = $this->service->getExtratoData(['inicio' => '2026-01-01', 'fim' => '2026-01-31']);

        $this->assertSame('Loja — Equipamento (2/6)', $result['lancamentos'][0]['historico']);
        $this->assertSame('Loja — Ração', $result['lancamentos'][1]['historico']);
    }

    public function testGetDREDataComputesMarginsAndVariations(): void
    {
        $this->repository->expects($this->exactly(2))
            ->method('getTotaisDRE')
            ->willReturnOnConsecutiveCalls(
                ['receita_bruta' => 1000.0, 'descontos' => 100.0, 'despesas' => 400.0],
                ['receita_bruta' => 500.0, 'descontos' => 0.0, 'despesas' => 200.0]
            );
        $this->repository->method('getReceitasBrutasDRE')->willReturn([['servico' => 'Banho', 'total' => 1000.0]]);
        $this->repository->method('getDespesasPorCategoriaDRE')->willReturn([['categoria' => 'Estoque', 'total' => 400.0]]);

        $result = $this->service->getDREData(['inicio' => '2026-01-01', 'fim' => '2026-01-31']);

        $this->assertSame(1000.0, $result['receita_bruta']);
        $this->assertSame(100.0, $result['descontos']);
        $this->assertSame(900.0, $result['receita_liquida']);
        $this->assertSame(400.0, $result['total_despesas']);
        $this->assertSame(500.0, $result['resultado_liquido']);
        $this->assertSame(50.0, $result['margem_liquida']);
        // Receita variou de 500 para 1000 => +100%
        $this->assertSame(100.0, $result['variacao_receita']);
        // Despesa variou de 200 para 400 => +100%
        $this->assertSame(100.0, $result['variacao_despesa']);
    }

    public function testGetLivroCaixaDataIndicaNumeroDaParcelaNasSaidas(): void
    {
        $this->repository->method('getSaldoAnterior')->willReturn(0.0);
        $this->repository->method('getEntradasLivroCaixa')->willReturn([]);
        $this->repository->method('getSaidasLivroCaixa')->willReturn([
            ['data_vencimento' => '2026-01-10', 'fornecedor' => 'Loja', 'descricao' => 'Equipamento', 'valor' => '40.00', 'forma_pagamento' => 'Pix', 'des_parcela_num' => 1, 'des_parcela_total' => 3],
        ]);

        $result = $this->service->getLivroCaixaData(['inicio' => '2026-01-01', 'fim' => '2026-01-31']);

        $this->assertSame('Loja — Equipamento (1/3)', $result['movimentos'][0]['historico']);
    }

    public function testGetDREDataHandlesZeroReceitaBrutaWithoutDivisionByZero(): void
    {
        $this->repository->method('getTotaisDRE')->willReturn(['receita_bruta' => 0.0, 'descontos' => 0.0, 'despesas' => 0.0]);
        $this->repository->method('getReceitasBrutasDRE')->willReturn([]);
        $this->repository->method('getDespesasPorCategoriaDRE')->willReturn([]);

        $result = $this->service->getDREData([]);

        $this->assertSame(0, $result['margem_liquida']);
        $this->assertSame(0, $result['variacao_receita']);
        $this->assertSame(0, $result['variacao_despesa']);
    }

    public function testGetLivroCaixaDataAccumulatesSaldo(): void
    {
        $this->repository->method('getSaldoAnterior')->willReturn(100.0);
        $this->repository->method('getEntradasLivroCaixa')->willReturn([
            ['data_servico' => '2026-01-20', 'paciente_nome' => 'Rex', 'servico' => 'Banho', 'valor' => '50.00', 'forma_pagamento' => 'Pix'],
        ]);
        $this->repository->method('getSaidasLivroCaixa')->willReturn([
            ['data_vencimento' => '2026-01-10', 'fornecedor' => 'Fornecedor', 'descricao' => 'Ração', 'valor' => '30.00', 'forma_pagamento' => 'Boleto'],
        ]);

        $result = $this->service->getLivroCaixaData(['inicio' => '2026-01-01', 'fim' => '2026-01-31']);

        $this->assertSame(100.0, $result['saldo_inicial']);
        $this->assertCount(2, $result['movimentos']);
        // Ordered chronologically: saida (01-10) then entrada (01-20)
        $this->assertSame(70.0, $result['movimentos'][0]['saldo_acumulado']);
        $this->assertSame(120.0, $result['movimentos'][1]['saldo_acumulado']);
        $this->assertSame(50.0, $result['total_entradas']);
        $this->assertSame(30.0, $result['total_saidas']);
        $this->assertSame(120.0, $result['saldo_final']);
    }
}
