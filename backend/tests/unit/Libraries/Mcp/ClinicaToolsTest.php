<?php

declare(strict_types=1);

namespace Tests\Unit\Libraries\Mcp;

use App\Libraries\Mcp\ClinicaTools;
use App\Libraries\Mcp\McpServer;
use App\Libraries\Mcp\ToolException;
use App\Services\AgendaService;
use App\Services\ClientesService;
use App\Services\PetsService;
use App\Services\ServicosService;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class ClinicaToolsTest extends CIUnitTestCase
{
    private ClientesService&MockObject $clientes;
    private PetsService&MockObject $pets;
    private AgendaService&MockObject $agenda;
    private ServicosService&MockObject $servicos;
    private ClinicaTools $tools;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientes = $this->createMock(ClientesService::class);
        $this->pets     = $this->createMock(PetsService::class);
        $this->agenda   = $this->createMock(AgendaService::class);
        $this->servicos = $this->createMock(ServicosService::class);
        $this->tools    = new ClinicaTools($this->clientes, $this->pets, $this->agenda, $this->servicos);
    }

    public function testRegistraAsSeisFerramentas(): void
    {
        $server = new McpServer();
        $this->tools->register($server);

        $r = $server->handleMessage(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']);
        $this->assertEqualsCanonicalizing(
            ['incluir_cliente', 'incluir_paciente', 'agendar', 'buscar_clientes', 'buscar_pacientes', 'listar_servicos'],
            array_column($r['result']['tools'], 'name')
        );
    }

    public function testIncluirClienteCriaERetornaId(): void
    {
        $this->clientes->expects($this->once())->method('create')
            ->with($this->callback(fn (array $d) => $d['nome'] === 'Maria Silva' && $d['telefones'] === '11999990000' && !isset($d['cpf'])))
            ->willReturn(['status' => 'success', 'message' => 'Registro criado com sucesso', 'id' => 42]);

        $out = $this->tools->incluirCliente(['nome' => ' Maria Silva ', 'telefones' => '11999990000', 'cpf' => '', 'lixo' => 'x']);

        $this->assertSame(42, $out['cliente_id']);
    }

    public function testIncluirClienteRejeitaCpfInvalido(): void
    {
        $this->clientes->expects($this->never())->method('create');

        try {
            $this->tools->incluirCliente(['nome' => 'Maria Silva', 'cpf' => '111.111.111-11']);
            $this->fail('Deveria lançar ToolException');
        } catch (ToolException $e) {
            $this->assertArrayHasKey('cpf', $e->payload()['errors']);
        }
    }

    public function testIncluirClientePropagaErroDeNegocio(): void
    {
        $this->clientes->method('create')->willReturn(['status' => 'error', 'message' => 'Este CPF já está cadastrado para outro cliente.']);

        $this->expectException(ToolException::class);
        $this->expectExceptionMessage('CPF já está cadastrado');
        $this->tools->incluirCliente(['nome' => 'Maria Silva', 'cpf' => '529.982.247-25']);
    }

    public function testIncluirPacienteMapeiaCamposEVinculaCliente(): void
    {
        $this->clientes->method('findById')->with(7)->willReturn(['id' => 7]);
        $this->pets->expects($this->once())->method('create')
            ->with($this->callback(fn (array $d) => $d['cliente_id'] === 7
                && $d['paciente_nome'] === 'Rex' && $d['paciente_especie'] === 'Cão' && $d['paciente_status'] === 'Ativo'
                && !array_key_exists('paciente_nascimento', $d)))
            ->willReturn(['status' => 'success', 'message' => 'ok', 'id' => 11]);

        $out = $this->tools->incluirPaciente(['cliente_id' => 7, 'nome' => 'Rex', 'especie' => 'Cão', 'nascimento' => '']);

        $this->assertSame(11, $out['paciente_id']);
    }

    public function testIncluirPacienteComClienteInexistente(): void
    {
        $this->clientes->method('findById')->willReturn(null);
        $this->pets->expects($this->never())->method('create');

        $this->expectException(ToolException::class);
        $this->tools->incluirPaciente(['cliente_id' => 999, 'nome' => 'Rex']);
    }

    public function testIncluirPacienteValidaSexo(): void
    {
        $this->expectException(ToolException::class);
        $this->tools->incluirPaciente(['cliente_id' => 1, 'nome' => 'Rex', 'sexo' => 'X']);
    }

    public function testAgendarMontaPayloadDaAgenda(): void
    {
        $this->pets->method('findById')->with(11)->willReturn(['paciente_id' => 11]);
        $this->agenda->expects($this->once())->method('create')
            ->with([
                'paciente_id' => 11,
                'age_data'    => '2026-12-01',
                'age_hora'    => '14:30:00',
                'age_duracao' => '45',
                'age_status'  => 'confirmado',
                'age_obs'     => 'Vacina anual',
                'age_servico' => [3, 5],
            ])
            ->willReturn(['status' => 'success', 'message' => 'Registro criado com sucesso', 'id' => 99]);

        $out = $this->tools->agendar([
            'paciente_id' => 11, 'data' => '2026-12-01', 'hora' => '14:30', 'servico_ids' => [3, '5'],
            'duracao_minutos' => 45, 'status' => 'confirmado', 'observacoes' => 'Vacina anual',
        ]);

        $this->assertSame(99, $out['agendamento_id']);
    }

    /** @dataProvider agendarInvalidoProvider */
    public function testAgendarRejeitaEntradaInvalida(array $input): void
    {
        $this->pets->method('findById')->willReturn(['paciente_id' => 1]);
        $this->agenda->expects($this->never())->method('create');

        $this->expectException(ToolException::class);
        $this->tools->agendar($input);
    }

    public static function agendarInvalidoProvider(): iterable
    {
        $ok = ['paciente_id' => 1, 'data' => '2026-12-01', 'hora' => '10:00', 'servico_ids' => [1]];

        yield 'sem serviço'       => [array_merge($ok, ['servico_ids' => []])];
        yield 'data inválida'     => [array_merge($ok, ['data' => '01/12/2026'])];
        yield 'hora inválida'     => [array_merge($ok, ['hora' => '25:00'])];
        yield 'status inválido'   => [array_merge($ok, ['status' => 'concluido'])];
        yield 'sem paciente'      => [array_diff_key($ok, ['paciente_id' => 1])];
    }

    public function testAgendarComPacienteInexistente(): void
    {
        $this->pets->method('findById')->willReturn(null);
        $this->agenda->expects($this->never())->method('create');

        $this->expectException(ToolException::class);
        $this->tools->agendar(['paciente_id' => 5, 'data' => '2026-12-01', 'hora' => '10:00', 'servico_ids' => [1]]);
    }

    public function testBuscasLimitamResultadosEColunas(): void
    {
        $rows = array_map(fn ($i) => ['id' => $i, 'nome' => "C$i", 'senha_interna' => 'x'], range(1, 30));
        $this->clientes->method('search')->with('ativos', null, 'ana')->willReturn($rows);

        $out = $this->tools->buscarClientes(['termo' => 'ana']);

        $this->assertSame(30, $out['total']);
        $this->assertCount(20, $out['clientes']);
        $this->assertArrayNotHasKey('senha_interna', $out['clientes'][0]);
    }

    public function testListarServicos(): void
    {
        $this->servicos->method('getAllActive')->willReturn([['ser_id' => 1, 'ser_nome' => 'Consulta', 'ser_valor' => '100.00', 'x' => 1]]);

        $this->assertSame(
            [['ser_id' => 1, 'ser_nome' => 'Consulta', 'ser_valor' => '100.00']],
            $this->tools->listarServicos()['servicos']
        );
    }
}
