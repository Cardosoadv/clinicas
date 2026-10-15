<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\FatCobrancaRepository;
use App\Repositories\FatDespesaRepository;
use App\Repositories\FatNotaRepository;
use App\Repositories\PetsRepository;
use App\Repositories\RelatoriosRepository;
use App\Services\EstoqueService;
use App\Services\FatService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Despesas parceladas de ponta a ponta no serviço, contra um banco real (SQLite em memória):
 * criação, atomicidade do rollback, edição/cancelamento por escopo, listagem e relatórios.
 * Os testes de FatServiceTest usam mocks e não conseguem provar o rollback nem o SQL.
 *
 * @internal
 */
final class FatServiceDespesasParceladasDbTest extends CIUnitTestCase
{
    private string $tabela;
    private FatDespesaRepository $repo;
    private FatService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $db = \Config\Database::connect();
        $this->fecharTransacaoPendente($db);
        $this->tabela = $db->prefixTable('fat_despesas');
        $db->query("DROP TABLE IF EXISTS {$this->tabela}");
        $db->query(
            "CREATE TABLE {$this->tabela} (id INTEGER PRIMARY KEY AUTOINCREMENT, descricao TEXT, categoria TEXT, fornecedor TEXT,
             valor REAL, data_vencimento TEXT, forma_pagamento TEXT, status TEXT DEFAULT 'Pendente', comprovante TEXT,
             des_grupo_id TEXT, des_parcela_num INT, des_parcela_total INT, des_recorrencia TEXT, des_recorrencia_fim TEXT,
             created_at TEXT, updated_at TEXT, deleted_at TEXT)"
        );

        $this->repo    = new FatDespesaRepository();
        $this->service = $this->criarService($this->repo);
    }

    protected function tearDown(): void
    {
        \Config\Database::connect()->query("DROP TABLE IF EXISTS {$this->tabela}");
        parent::tearDown();
    }

    /**
     * A conexão é compartilhada entre os testes e o CodeIgniter só desfaz a transação mais externa:
     * se outro teste deixar uma transação aberta, o rollback do serviço viraria apenas um decremento.
     * Fecha qualquer transação herdada para este teste começar de uma conexão limpa.
     */
    private function fecharTransacaoPendente(\CodeIgniter\Database\BaseConnection $db): void
    {
        $profundidade = new \ReflectionProperty(\CodeIgniter\Database\BaseConnection::class, 'transDepth');
        if ($profundidade->getValue($db) > 0) {
            $profundidade->setValue($db, 1);
            $db->transRollback();
        }
        $db->resetTransStatus();
    }

    private function criarService(FatDespesaRepository $despesaRepo): FatService
    {
        return new FatService(
            $this->createMock(FatCobrancaRepository::class),
            $despesaRepo,
            $this->createMock(FatNotaRepository::class),
            $this->createMock(PetsRepository::class),
            $this->createMock(EstoqueService::class)
        );
    }

    private function linhas(): array
    {
        return \Config\Database::connect()->query("SELECT * FROM {$this->tabela} ORDER BY id")->getResultArray();
    }

    /** Cria um 3x de R$ 100,00 com 1ª parcela em 2026-09-10 e devolve a resposta. */
    private function criarTresParcelas(array $extra = []): array
    {
        return $this->service->createDespesa($extra + [
            'descricao'       => 'Equipamento',
            'categoria'       => 'Equipamentos',
            'fornecedor'      => 'Loja A',
            'forma_pagamento' => 'Boleto',
            'is_parcelado'    => 'true',
            'valor'           => 100,
            'parcelas_qtd'    => 3,
            'data_vencimento' => '2026-09-10',
        ]);
    }

    public function testCriaParcelasPersistidasComMesmoGrupo(): void
    {
        $result = $this->criarTresParcelas(['status' => 'Pago']);

        $this->assertSame('success', $result['status']);
        $linhas = $this->linhas();
        $this->assertCount(3, $linhas);
        $this->assertEqualsWithDelta(100.0, array_sum(array_column($linhas, 'valor')), 0.001);
        $this->assertSame([1, 2, 3], array_map('intval', array_column($linhas, 'des_parcela_num')));
        $this->assertSame(['2026-09-10', '2026-10-10', '2026-11-10'], array_column($linhas, 'data_vencimento'));
        $this->assertSame(['Pago', 'Pendente', 'Pendente'], array_column($linhas, 'status'));
        $this->assertCount(1, array_unique(array_column($linhas, 'des_grupo_id')));
        $this->assertSame($result['des_grupo_id'], $linhas[0]['des_grupo_id']);
    }

    public function testFalhaNaTerceiraParcelaDesfazTudo(): void
    {
        $falhaNaTerceira = new class extends FatDespesaRepository {
            private int $chamadas = 0;

            public function create(array $data): int
            {
                return ++$this->chamadas === 3 ? 0 : parent::create($data);
            }
        };

        $result = $this->criarService($falhaNaTerceira)->createDespesa([
            'descricao'       => 'Equipamento',
            'categoria'       => 'Equipamentos',
            'is_parcelado'    => 'true',
            'valor'           => 100,
            'parcelas_qtd'    => 3,
            'data_vencimento' => '2026-09-10',
        ]);

        $this->assertSame('error', $result['status']);
        $this->assertCount(0, $this->linhas(), 'as duas primeiras parcelas deveriam ter sido desfeitas');
    }

    public function testCancelarTodasPreservaParcelasPagas(): void
    {
        $this->criarTresParcelas();
        $this->repo->getModel()->update(2, ['status' => 'Pago']);

        $result = $this->service->cancelarDespesa(1, 'todas');

        $this->assertSame('success', $result['status']);
        $this->assertSame(2, $result['canceladas']);
        $this->assertSame(1, $result['ignoradas_pagas']);
        $this->assertSame(['Cancelado', 'Pago', 'Cancelado'], array_column($this->linhas(), 'status'));
    }

    public function testCancelarEstaESeguintesNaoAtingeParcelasAnteriores(): void
    {
        $this->criarTresParcelas();

        $result = $this->service->cancelarDespesa(2, 'esta_e_seguintes');

        $this->assertSame(2, $result['canceladas']);
        $this->assertSame(['Pendente', 'Cancelado', 'Cancelado'], array_column($this->linhas(), 'status'));
    }

    public function testCancelarParcelaSemNenhumaPendenteNoEscopoRetornaErro(): void
    {
        $this->criarTresParcelas(['status' => 'Pago']);

        $result = $this->service->cancelarDespesa(1, 'apenas_esta');

        $this->assertSame('error', $result['status']);
        $this->assertSame('Pago', $this->linhas()[0]['status']);
    }

    public function testEditarEscopoTodasPropagaCamposSemMexerEmValorNemFormaDeParcelaPaga(): void
    {
        $this->criarTresParcelas();
        $this->repo->getModel()->update(1, ['status' => 'Pago']);

        $result = $this->service->updateDespesa(2, [
            'fornecedor'      => 'Loja B',
            'forma_pagamento' => 'Pix',
            'valor'           => 50,
            'escopo'          => 'todas',
        ]);

        $this->assertSame('success', $result['status']);
        $linhas = $this->linhas();
        $this->assertSame(['Loja B', 'Loja B', 'Loja B'], array_column($linhas, 'fornecedor'));
        $this->assertSame(['Boleto', 'Pix', 'Pix'], array_column($linhas, 'forma_pagamento'), 'a parcela paga mantém a forma de pagamento');
        $this->assertEquals([33.33, 50.0, 33.34], array_map('floatval', array_column($linhas, 'valor')), 'valor só muda na parcela editada');
    }

    public function testEditarEstaESeguintesNaoAlteraParcelasAnteriores(): void
    {
        $this->criarTresParcelas();

        $this->service->updateDespesa(2, ['descricao' => 'Novo nome', 'escopo' => 'esta_e_seguintes']);

        $this->assertSame(['Equipamento', 'Novo nome', 'Novo nome'], array_column($this->linhas(), 'descricao'));
    }

    public function testListagemPorMesMostraAParcelaDoMesComTotalEPaginacao(): void
    {
        $this->criarTresParcelas();

        $outubro = $this->service->getDespesas(['mes' => '2026-10']);
        $this->assertSame(1, $outubro['data']['total']);
        $this->assertSame(2, (int) $outubro['data']['items'][0]['des_parcela_num']);

        $todas = $this->service->getDespesas(['per_page' => '2', 'page' => '2']);
        $this->assertSame(3, $todas['data']['total']);
        $this->assertSame(2, $todas['data']['total_pages']);
        $this->assertCount(1, $todas['data']['items']);
        $this->assertSame('success', $this->service->getGrupoDespesas($this->linhas()[0]['des_grupo_id'])['status']);
    }

    public function testRelatoriosContamCadaParcelaPagaUmaVezNoMesDoVencimento(): void
    {
        $this->criarTresParcelas();
        $model = $this->repo->getModel();
        $model->update(1, ['status' => 'Pago']);
        $model->update(2, ['status' => 'Pago']);
        $model->update(3, ['status' => 'Cancelado']);

        $relatorios = new RelatoriosRepository();

        $dre = $relatorios->getDespesasPorCategoriaDRE('2026-10-01', '2026-10-31');
        $this->assertCount(1, $dre);
        $this->assertEqualsWithDelta(33.33, (float) $dre[0]['total'], 0.001, 'só a parcela 2, paga, entra em outubro');

        $this->assertCount(0, $relatorios->getSaidasLivroCaixa('2026-11-01', '2026-11-30'), 'parcela cancelada fora do Livro Caixa');
        $this->assertCount(1, $relatorios->getSaidasLivroCaixa('2026-09-01', '2026-09-30'));

        $extrato = $relatorios->getDespesasExtrato('2026-09-01', '2026-11-30');
        $this->assertCount(3, $extrato, 'o Extrato lista todos os status');
    }

    public function testDashboardInformaValoresAPagarDasParcelasPendentes(): void
    {
        $this->criarTresParcelas();
        $this->repo->getModel()->update(1, ['status' => 'Pago']);

        $resumo = $this->repo->getResumoAPagar('2026-10-15');
        $this->assertEqualsWithDelta(66.67, $resumo['a_pagar'], 0.001);
        $this->assertEqualsWithDelta(33.33, $resumo['vencidas'], 0.001, 'parcela 2 (10/10) vencida em 15/10');

        $porMes = $this->repo->getPendentesPorMes('2026-10-01');
        $this->assertSame(['2026-10', '2026-11'], array_column($porMes, 'mes'));
    }
}
