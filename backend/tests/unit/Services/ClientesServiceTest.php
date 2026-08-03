<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\ClienteNotaRepository;
use App\Repositories\ClientesRepository;
use App\Repositories\ComunicacaoRepository;
use App\Repositories\PetsRepository;
use App\Services\ClientesService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ClientesServiceTest extends CIUnitTestCase
{
    private ClientesRepository $clientesRepo;
    private PetsRepository $petsRepo;
    private ClienteNotaRepository $notaRepo;
    private ComunicacaoRepository $comunicacaoRepo;
    private ClientesService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientesRepo = $this->createMock(ClientesRepository::class);
        $this->petsRepo = $this->createMock(PetsRepository::class);
        $this->notaRepo = $this->createMock(ClienteNotaRepository::class);
        $this->comunicacaoRepo = $this->createMock(ComunicacaoRepository::class);

        $this->service = new ClientesService(
            $this->clientesRepo,
            $this->petsRepo,
            $this->notaRepo,
            $this->comunicacaoRepo
        );
    }

    public function testGetPacientesDelegatesToPetsRepository(): void
    {
        $this->petsRepo->expects($this->once())
            ->method('findByCliente')
            ->with(1)
            ->willReturn([['paciente_id' => 10]]);

        $this->assertSame([['paciente_id' => 10]], $this->service->getPacientes(1));
    }

    public function testGetNotasDelegatesToNotaRepository(): void
    {
        $this->notaRepo->expects($this->once())
            ->method('findByCliente')
            ->with(1)
            ->willReturn([['id' => 5, 'texto' => 'Nota']]);

        $this->assertSame([['id' => 5, 'texto' => 'Nota']], $this->service->getNotas(1));
    }

    public function testAddNotaReturnsSuccess(): void
    {
        $this->notaRepo->expects($this->once())
            ->method('create')
            ->with(['cliente_id' => 1, 'texto' => 'Ligou hoje', 'autor' => 'Fabiano'])
            ->willReturn(9);

        $result = $this->service->addNota(1, 'Ligou hoje', 'Fabiano');

        $this->assertSame('success', $result['status']);
        $this->assertSame(9, $result['id']);
    }

    public function testAddNotaReturnsErrorWhenCreateFails(): void
    {
        $this->notaRepo->method('create')->willReturn(0);

        $result = $this->service->addNota(1, 'Ligou hoje', null);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro ao salvar a nota.', $result['message']);
    }

    public function testDeleteNotaReturnsErrorWhenNotFound(): void
    {
        $this->notaRepo->method('findById')->willReturn(null);
        $this->notaRepo->expects($this->never())->method('delete');

        $result = $this->service->deleteNota(1, 99);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Nota não encontrada.', $result['message']);
    }

    public function testDeleteNotaReturnsErrorWhenNotaBelongsToAnotherClient(): void
    {
        $this->notaRepo->method('findById')->willReturn(['id' => 99, 'cliente_id' => 2]);
        $this->notaRepo->expects($this->never())->method('delete');

        $result = $this->service->deleteNota(1, 99);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Nota não encontrada.', $result['message']);
    }

    public function testDeleteNotaSucceeds(): void
    {
        $this->notaRepo->method('findById')->willReturn(['id' => 99, 'cliente_id' => 1]);
        $this->notaRepo->expects($this->once())->method('delete')->with(99)->willReturn(true);

        $result = $this->service->deleteNota(1, 99);

        $this->assertSame('success', $result['status']);
        $this->assertSame('Nota removida com sucesso.', $result['message']);
    }

    public function testDeleteNotaReturnsErrorWhenDeleteFails(): void
    {
        $this->notaRepo->method('findById')->willReturn(['id' => 99, 'cliente_id' => 1]);
        $this->notaRepo->method('delete')->willReturn(false);

        $result = $this->service->deleteNota(1, 99);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro ao excluir a nota.', $result['message']);
    }

    public function testGetComunicacoesDelegatesToComunicacaoRepository(): void
    {
        $this->comunicacaoRepo->expects($this->once())
            ->method('findByCliente')
            ->with(1)
            ->willReturn([['id' => 1, 'mensagem' => 'Oi']]);

        $this->assertSame([['id' => 1, 'mensagem' => 'Oi']], $this->service->getComunicacoes(1));
    }

    public function testEnviarComunicacaoReturnsErrorWhenClienteNotFound(): void
    {
        $this->clientesRepo->method('findById')->willReturn(null);
        $this->comunicacaoRepo->expects($this->never())->method('create');

        $result = $this->service->enviarComunicacao(1, 'Olá');

        $this->assertSame('error', $result['status']);
        $this->assertSame('Cliente não encontrado.', $result['message']);
    }

    public function testEnviarComunicacaoBuildsWhatsAppLinkWithDddPrefix(): void
    {
        $this->clientesRepo->method('findById')->willReturn([
            'id'        => 1,
            'telefones' => '(11) 98888-7777',
        ]);

        $this->comunicacaoRepo->expects($this->once())
            ->method('create')
            ->with([
                'cliente_id' => 1,
                'canal'      => 'whatsapp',
                'tipo'       => 'manual',
                'mensagem'   => 'Olá cliente',
            ]);

        $result = $this->service->enviarComunicacao(1, 'Olá cliente');

        $this->assertSame('success', $result['status']);
        $this->assertSame(
            'https://wa.me/5511988887777/?text=' . urlencode('Olá cliente'),
            $result['link']
        );
    }

    public function testSearchDelegatesToClientesRepository(): void
    {
        $this->clientesRepo->expects($this->once())
            ->method('getFiltered')
            ->with('ativos', 'PF', 'joao')
            ->willReturn([['id' => 1]]);

        $this->assertSame([['id' => 1]], $this->service->search('ativos', 'PF', 'joao'));
    }

    public function testGetPainelDataAggregatesRepositoryCalls(): void
    {
        $this->clientesRepo->method('getStats')->willReturn(['total' => 10]);
        $this->clientesRepo->method('getRecent')->with(5)->willReturn([['id' => 1]]);
        $this->clientesRepo->method('getBirthdaysByMonth')->willReturn([['id' => 2]]);

        $result = $this->service->getPainelData();

        $this->assertSame(['total' => 10], $result['stats']);
        $this->assertSame([['id' => 1]], $result['recentes']);
        $this->assertSame([['id' => 2]], $result['aniversariantes']);
    }
}
