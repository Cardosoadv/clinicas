<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\AgendamentosRepository;
use App\Repositories\ClientesRepository;
use App\Repositories\PetsRepository;
use App\Services\PetsService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PetsServiceTest extends CIUnitTestCase
{
    private PetsRepository $repository;
    private AgendamentosRepository $agendamentosRepository;
    private ClientesRepository $clientesRepository;
    private PetsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(PetsRepository::class);
        $this->agendamentosRepository = $this->createMock(AgendamentosRepository::class);
        $this->clientesRepository = $this->createMock(ClientesRepository::class);

        $this->service = new PetsService($this->repository, $this->agendamentosRepository, $this->clientesRepository);
    }

    public function testSearchDelegatesToRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('search')
            ->with('rex')
            ->willReturn([['paciente_id' => 1, 'paciente_nome' => 'Rex']]);

        $this->assertSame([['paciente_id' => 1, 'paciente_nome' => 'Rex']], $this->service->search('rex'));
    }

    public function testGetAllWithLastAppointmentDelegatesToRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('getAllWithLastAppointment')
            ->willReturn([['paciente_id' => 1]]);

        $this->assertSame([['paciente_id' => 1]], $this->service->getAllWithLastAppointment());
    }

    public function testGetBirthdaysOfMonthDelegatesToRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('getBirthdaysByMonth')
            ->with(8)
            ->willReturn([['paciente_id' => 1]]);

        $this->assertSame([['paciente_id' => 1]], $this->service->getBirthdaysOfMonth(8));
    }

    public function testListFilteredDelegatesToRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('getFiltered')
            ->with('ativos', 'Cão', 'rex')
            ->willReturn([['paciente_id' => 1]]);

        $this->assertSame([['paciente_id' => 1]], $this->service->listFiltered('ativos', 'Cão', 'rex'));
    }

    public function testGetPainelDataAggregatesRepositoryCalls(): void
    {
        $this->repository->method('getStats')->willReturn(['total' => 5]);
        $this->repository->method('getRecent')->with(5)->willReturn([['paciente_id' => 1]]);
        $this->repository->method('getBirthdaysByMonth')->willReturn([['paciente_id' => 2]]);
        $this->repository->method('getEspecies')->willReturn(['Cão', 'Gato']);

        $result = $this->service->getPainelData();

        $this->assertSame(['total' => 5], $result['stats']);
        $this->assertSame([['paciente_id' => 1]], $result['recentes']);
        $this->assertSame([['paciente_id' => 2]], $result['aniversariantes']);
        $this->assertSame(['Cão', 'Gato'], $result['especies']);
    }

    public function testMixCreateReusesExistingClientByCpf(): void
    {
        $this->clientesRepository->method('findByCpf')->with('12345678900')->willReturn(['id' => 7]);
        $this->clientesRepository->expects($this->never())->method('create');

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $data): bool => $data['cliente_id'] === 7))
            ->willReturn(30);

        $this->agendamentosRepository->expects($this->never())->method('create');

        $result = $this->service->mixCreate([
            'pet_resp_cpf'  => '12345678900',
            'pet_resp_nome' => 'Maria',
            'pet_nome'      => 'Rex',
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertSame(30, $result['id']);
    }

    public function testMixCreateCreatesNewClientWhenCpfNotFound(): void
    {
        $this->clientesRepository->method('findByCpf')->willReturn(null);
        $this->clientesRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $data): bool => $data['nome'] === 'Maria'))
            ->willReturn(8);

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $data): bool => $data['cliente_id'] === 8))
            ->willReturn(31);

        $result = $this->service->mixCreate([
            'pet_resp_cpf'  => '99988877766',
            'pet_resp_nome' => 'Maria',
            'pet_nome'      => 'Rex',
        ]);

        $this->assertSame('success', $result['status']);
    }

    public function testMixCreateCreatesAppointmentWhenScheduleDataProvided(): void
    {
        $this->clientesRepository->method('findByCpf')->willReturn(['id' => 7]);
        $this->repository->method('create')->willReturn(30);

        $this->agendamentosRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $data): bool => $data['paciente_id'] === 30 && $data['age_data'] === '2026-08-10'));

        $result = $this->service->mixCreate([
            'pet_resp_cpf'  => '12345678900',
            'pet_resp_nome' => 'Maria',
            'pet_nome'      => 'Rex',
            'age_data'      => '2026-08-10',
        ]);

        $this->assertSame('success', $result['status']);
    }

    public function testMixCreateReturnsErrorWhenPetCreationFails(): void
    {
        $this->clientesRepository->method('findByCpf')->willReturn(['id' => 7]);
        $this->repository->method('create')->willReturn(0);

        $result = $this->service->mixCreate([
            'pet_resp_cpf'  => '12345678900',
            'pet_resp_nome' => 'Maria',
            'pet_nome'      => 'Rex',
        ]);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro ao processar o cadastro do pet', $result['message']);
    }
}
