<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\FatCobrancaModel;
use App\Repositories\AgendamentosRepository;
use App\Repositories\EvolucoesRepository;
use App\Repositories\FatCobrancaRepository;
use App\Repositories\ImagensRepository;
use App\Repositories\OdontogramasRepository;
use App\Repositories\PesosRepository;
use App\Repositories\PetsRepository;
use App\Repositories\PrescricoesRepository;
use App\Repositories\VacinasRepository;
use App\Services\ProntuariosService;
use CodeIgniter\Test\CIUnitTestCase;
use Exception;

/**
 * @internal
 */
final class ProntuariosServiceTest extends CIUnitTestCase
{
    private PetsRepository $petsRepo;
    private OdontogramasRepository $odontogramasRepo;
    private EvolucoesRepository $evolucoesRepo;
    private ImagensRepository $imagensRepo;
    private AgendamentosRepository $agendamentosRepo;
    private FatCobrancaRepository $fatCobrancaRepo;
    private VacinasRepository $vacinasRepo;
    private PesosRepository $pesosRepo;
    private PrescricoesRepository $prescricoesRepo;
    private ProntuariosService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->petsRepo = $this->createMock(PetsRepository::class);
        $this->odontogramasRepo = $this->createMock(OdontogramasRepository::class);
        $this->evolucoesRepo = $this->createMock(EvolucoesRepository::class);
        $this->imagensRepo = $this->createMock(ImagensRepository::class);
        $this->agendamentosRepo = $this->createMock(AgendamentosRepository::class);
        $this->fatCobrancaRepo = $this->createMock(FatCobrancaRepository::class);
        $this->vacinasRepo = $this->createMock(VacinasRepository::class);
        $this->pesosRepo = $this->createMock(PesosRepository::class);
        $this->prescricoesRepo = $this->createMock(PrescricoesRepository::class);

        $this->service = new ProntuariosService(
            $this->petsRepo,
            $this->odontogramasRepo,
            $this->evolucoesRepo,
            $this->imagensRepo,
            $this->agendamentosRepo,
            $this->fatCobrancaRepo,
            $this->vacinasRepo,
            $this->pesosRepo,
            $this->prescricoesRepo
        );
    }

    private function mockCobrancaFindAll(array $rows): FatCobrancaModel
    {
        $model = $this->getMockBuilder(FatCobrancaModel::class)->onlyMethods(['findAll'])->getMock();
        $model->method('findAll')->willReturn($rows);

        return $model;
    }

    public function testGetPetsListWithoutTermReturnsAllWithClient(): void
    {
        $this->petsRepo->expects($this->once())->method('findAllWithClient')->willReturn([['paciente_id' => 1]]);
        $this->petsRepo->expects($this->never())->method('search');

        $this->assertSame([['paciente_id' => 1]], $this->service->getPetsList());
    }

    public function testGetPetsListWithTermSearches(): void
    {
        $this->petsRepo->expects($this->once())->method('search')->with('rex')->willReturn([['paciente_id' => 1]]);

        $this->assertSame([['paciente_id' => 1]], $this->service->getPetsList('rex'));
    }

    public function testGetFullRecordThrowsWhenPetNotFound(): void
    {
        $this->petsRepo->method('findById')->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Pet não encontrado.');

        $this->service->getFullRecord(1);
    }

    public function testGetFullRecordAggregatesAllDataAndStats(): void
    {
        $this->petsRepo->method('findById')->willReturn(['paciente_id' => 1, 'paciente_nome' => 'Rex']);
        $this->odontogramasRepo->method('findLatestByPet')->willReturn(['id' => 1]);
        $this->evolucoesRepo->method('findByPet')->willReturn([['id' => 1]]);
        $this->imagensRepo->method('findByPet')->willReturn([['id' => 1], ['id' => 2]]);
        $this->vacinasRepo->method('findByPet')->willReturn([['id' => 1]]);
        $this->pesosRepo->method('findByPet')->willReturn([['id' => 1]]);
        $this->prescricoesRepo->method('findByPet')->willReturn([['id' => 1]]);
        $this->agendamentosRepo->method('findByPet')->willReturn([['id' => 1], ['id' => 2], ['id' => 3]]);
        $this->fatCobrancaRepo->method('getModel')->willReturn($this->mockCobrancaFindAll([
            ['id' => 1, 'valor' => '100.00'],
            ['id' => 2, 'valor' => '50.50'],
        ]));

        $record = $this->service->getFullRecord(1);

        $this->assertSame('Rex', $record['pet']['paciente_nome']);
        $this->assertCount(2, $record['imagens']);
        $this->assertSame(3, $record['estatisticas']['consultas']);
        $this->assertSame(2, $record['estatisticas']['imagens']);
        $this->assertSame(150.5, $record['estatisticas']['total_investido']);
    }

    public function testSaveOdontogramaReturnsSuccess(): void
    {
        $this->odontogramasRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $d): bool => $d['paciente_id'] === 1))
            ->willReturn(5);

        $result = $this->service->saveOdontograma(1, ['mapa' => ['1' => 'ok'], 'observacoes' => 'Obs']);

        $this->assertSame('success', $result['status']);
        $this->assertSame(5, $result['id']);
    }

    public function testAddEvolucaoReturnsSuccess(): void
    {
        $this->evolucoesRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $d): bool => $d['paciente_id'] === 1 && $d['descricao'] === 'Melhorou'))
            ->willReturn(7);

        $result = $this->service->addEvolucao(1, ['descricao' => 'Melhorou']);

        $this->assertSame('success', $result['status']);
        $this->assertSame(7, $result['id']);
    }

    public function testAddEvolucaoReturnsErrorWhenRepositoryThrows(): void
    {
        $this->evolucoesRepo->method('create')->willThrowException(new Exception('fail'));

        $result = $this->service->addEvolucao(1, ['descricao' => 'X']);

        $this->assertSame('error', $result['status']);
    }

    public function testEditEvolucaoUpdatesFields(): void
    {
        $this->evolucoesRepo->expects($this->once())
            ->method('update')
            ->with(1, ['titulo' => 'T', 'descricao' => 'D']);

        $result = $this->service->editEvolucao(1, ['titulo' => 'T', 'descricao' => 'D']);

        $this->assertSame('success', $result['status']);
    }

    public function testEditEvolucaoIncludesVeterinarioIdWhenProvided(): void
    {
        $this->evolucoesRepo->expects($this->once())
            ->method('update')
            ->with(1, ['titulo' => 'T', 'descricao' => 'D', 'veterinario_id' => 3]);

        $this->service->editEvolucao(1, ['titulo' => 'T', 'descricao' => 'D', 'veterinario_id' => 3]);
    }

    public function testDeleteEvolucaoReturnsSuccess(): void
    {
        $this->evolucoesRepo->expects($this->once())->method('delete')->with(9);

        $result = $this->service->deleteEvolucao(9);

        $this->assertSame('success', $result['status']);
    }

    public function testAddVacinaReturnsSuccess(): void
    {
        $this->vacinasRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $d): bool => $d['vacina_nome'] === 'V10'))
            ->willReturn(3);

        $result = $this->service->addVacina(1, ['vacina_nome' => 'V10', 'data_aplicacao' => '2026-08-01']);

        $this->assertSame('success', $result['status']);
        $this->assertSame(3, $result['id']);
    }

    public function testDeleteVacinaReturnsSuccess(): void
    {
        $this->vacinasRepo->expects($this->once())->method('delete')->with(3);

        $result = $this->service->deleteVacina(3);

        $this->assertSame('success', $result['status']);
    }

    public function testAddPesoReturnsSuccess(): void
    {
        $this->pesosRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $d): bool => $d['peso'] === 12.5))
            ->willReturn(4);

        $result = $this->service->addPeso(1, ['peso' => '12.5']);

        $this->assertSame('success', $result['status']);
        $this->assertSame(4, $result['id']);
    }

    public function testDeletePesoReturnsSuccess(): void
    {
        $this->pesosRepo->expects($this->once())->method('delete')->with(4);

        $result = $this->service->deletePeso(4);

        $this->assertSame('success', $result['status']);
    }

    public function testAddImagemReturnsSuccess(): void
    {
        $this->imagensRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $d): bool => $d['titulo'] === 'Raio-X'))
            ->willReturn(6);

        $result = $this->service->addImagem(1, ['titulo' => 'Raio-X', 'arquivo_url' => 'x.png']);

        $this->assertSame('success', $result['status']);
        $this->assertSame(6, $result['id']);
    }

    public function testUpdateAnamneseReturnsSuccess(): void
    {
        $this->petsRepo->expects($this->once())->method('update')->with(1, ['anamnese' => 'texto'])->willReturn(true);

        $result = $this->service->updateAnamnese(1, ['anamnese' => 'texto']);

        $this->assertSame('success', $result['status']);
    }

    public function testUpdateAnamneseReturnsErrorWhenUpdateFails(): void
    {
        $this->petsRepo->method('update')->willReturn(false);

        $result = $this->service->updateAnamnese(1, ['anamnese' => 'texto']);

        $this->assertSame('error', $result['status']);
    }
}
