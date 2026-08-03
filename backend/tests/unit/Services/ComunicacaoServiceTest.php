<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\AgendamentosRepository;
use App\Repositories\ComunicacaoRepository;
use App\Repositories\ConfigRepository;
use App\Repositories\FatCobrancaRepository;
use App\Repositories\PetsRepository;
use App\Services\ComunicacaoService;
use CodeIgniter\Test\CIUnitTestCase;
use Exception;

/**
 * @internal
 *
 * getWhatsAppLinkVacina() is intentionally not covered here: it queries the
 * `vacinas`/`pacientes`/`clientes` tables directly through the query builder
 * (`\Config\Database::connect()->table(...)`) instead of a mockable
 * repository, and this project's migrations are not SQLite-compatible
 * (named foreign keys), so the schema can't be built in the in-memory test
 * database.
 */
final class ComunicacaoServiceTest extends CIUnitTestCase
{
    private AgendamentosRepository $ageRepo;
    private FatCobrancaRepository $fatRepo;
    private PetsRepository $petsRepo;
    private ConfigRepository $configRepo;
    private ComunicacaoRepository $comunicacaoRepo;
    private ComunicacaoService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ageRepo = $this->createMock(AgendamentosRepository::class);
        $this->fatRepo = $this->createMock(FatCobrancaRepository::class);
        $this->petsRepo = $this->createMock(PetsRepository::class);
        $this->configRepo = $this->createMock(ConfigRepository::class);
        $this->comunicacaoRepo = $this->createMock(ComunicacaoRepository::class);

        $this->service = new ComunicacaoService(
            $this->ageRepo,
            $this->fatRepo,
            $this->petsRepo,
            $this->configRepo,
            $this->comunicacaoRepo
        );
    }

    public function testGetWhatsAppLinkAgendamentoThrowsWhenNotFound(): void
    {
        $this->ageRepo->method('findWithTutor')->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Agendamento não encontrado.');

        $this->service->getWhatsAppLinkAgendamento(1);
    }

    public function testGetWhatsAppLinkAgendamentoBuildsMessageAndLogsIt(): void
    {
        $this->ageRepo->method('findWithTutor')->with(1)->willReturn([
            'tutor_nome'    => 'Maria Silva',
            'paciente_nome' => 'Rex',
            'age_servico'   => 'Banho',
            'age_data'      => '2026-08-10',
            'age_hora'      => '14:30:00',
            'telefones'     => '11988887777',
            'cliente_id'    => 3,
        ]);
        $this->configRepo->method('getMetaValue')->willReturn('Olá {tutor}, {pet} tem {servico} em {data} às {hora}.');

        $this->comunicacaoRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(static function (array $data): bool {
                return $data['cliente_id'] === 3
                    && $data['tipo'] === 'agendamento'
                    && $data['paciente_nome'] === 'Rex';
            }));

        $link = $this->service->getWhatsAppLinkAgendamento(1);

        $expectedMessage = 'Olá Maria, Rex tem Banho em 10/08/2026 às 14:30.';
        $this->assertSame('https://wa.me/5511988887777/?text=' . urlencode($expectedMessage), $link);
    }

    public function testGetWhatsAppLinkAgendamentoDoesNotLogWithoutClienteId(): void
    {
        $this->ageRepo->method('findWithTutor')->willReturn([
            'tutor_nome'    => 'Maria Silva',
            'paciente_nome' => 'Rex',
            'age_servico'   => 'Banho',
            'age_data'      => '2026-08-10',
            'age_hora'      => '14:30:00',
            'telefones'     => '11988887777',
        ]);
        $this->configRepo->method('getMetaValue')->willReturn('Oi {tutor}');
        $this->comunicacaoRepo->expects($this->never())->method('create');

        $this->service->getWhatsAppLinkAgendamento(1);
    }

    public function testGetWhatsAppLinkCobrancaThrowsWhenNotFound(): void
    {
        $this->fatRepo->method('findWithTutor')->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cobrança não encontrada.');

        $this->service->getWhatsAppLinkCobranca(1);
    }

    public function testGetWhatsAppLinkCobrancaFormatsCurrencyAndDate(): void
    {
        $this->fatRepo->method('findWithTutor')->willReturn([
            'tutor_nome'    => 'João Souza',
            'paciente_nome' => 'Bidu',
            'valor'         => 125.5,
            'vencimento'    => '2026-08-15',
            'telefones'     => '11977776666',
            'cliente_id'    => 4,
        ]);
        $this->configRepo->method('getMetaValue')->willReturn('{tutor}: R$ {valor} até {data}');

        $link = $this->service->getWhatsAppLinkCobranca(1);

        $expectedMessage = 'João: R$ 125,50 até 15/08/2026';
        $this->assertSame('https://wa.me/5511977776666/?text=' . urlencode($expectedMessage), $link);
    }

    public function testGetWhatsAppLinkPosConsultaThrowsWhenNotFound(): void
    {
        $this->ageRepo->method('findWithTutor')->willReturn(null);

        $this->expectException(Exception::class);
        $this->service->getWhatsAppLinkPosConsulta(1);
    }

    public function testGetWhatsAppLinkPosConsultaBuildsMessage(): void
    {
        $this->ageRepo->method('findWithTutor')->willReturn([
            'tutor_nome'    => 'Maria Silva',
            'paciente_nome' => 'Rex',
            'age_servico'   => 'Cirurgia',
            'telefones'     => '11988887777',
            'cliente_id'    => 3,
        ]);
        $this->configRepo->method('getMetaValue')->willReturn('{tutor}, como está {pet} após {servico}?');

        $link = $this->service->getWhatsAppLinkPosConsulta(1);

        $expectedMessage = 'Maria, como está Rex após Cirurgia?';
        $this->assertSame('https://wa.me/5511988887777/?text=' . urlencode($expectedMessage), $link);
    }

    public function testGetWhatsAppLinkAniversarioThrowsWhenPetNotFound(): void
    {
        $this->petsRepo->method('findById')->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Pet não encontrado.');

        $this->service->getWhatsAppLinkAniversario(1);
    }

    public function testGetWhatsAppLinkAniversarioBuildsMessage(): void
    {
        $this->petsRepo->method('findById')->willReturn([
            'pet_resp_nome' => 'Carla Souza',
            'paciente_nome' => 'Nina',
            'pet_resp_tel'  => '11966665555',
            'cliente_id'    => 8,
        ]);
        $this->configRepo->method('getMetaValue')->willReturn('Feliz aniversário, {pet}! De: {tutor}');

        $link = $this->service->getWhatsAppLinkAniversario(1);

        $expectedMessage = 'Feliz aniversário, Nina! De: Carla';
        $this->assertSame('https://wa.me/5511966665555/?text=' . urlencode($expectedMessage), $link);
    }

    public function testPhoneWithoutDddPrefixIsUsedAsIs(): void
    {
        $this->ageRepo->method('findWithTutor')->willReturn([
            'tutor_nome'    => 'Maria',
            'paciente_nome' => 'Rex',
            'age_servico'   => 'Banho',
            'age_data'      => '2026-08-10',
            'age_hora'      => '14:30:00',
            'telefones'     => '+5511988887777',
        ]);
        $this->configRepo->method('getMetaValue')->willReturn('{tutor}');

        $link = $this->service->getWhatsAppLinkAgendamento(1);

        $this->assertStringStartsWith('https://wa.me/5511988887777/', $link);
    }

    public function testPhoneListPicksFirstValidNumber(): void
    {
        $this->ageRepo->method('findWithTutor')->willReturn([
            'tutor_nome'    => 'Maria',
            'paciente_nome' => 'Rex',
            'age_servico'   => 'Banho',
            'age_data'      => '2026-08-10',
            'age_hora'      => '14:30:00',
            'telefones'     => '123,11988887777',
        ]);
        $this->configRepo->method('getMetaValue')->willReturn('{tutor}');

        $link = $this->service->getWhatsAppLinkAgendamento(1);

        $this->assertStringStartsWith('https://wa.me/5511988887777/', $link);
    }
}
