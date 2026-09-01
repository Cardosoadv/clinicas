<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Repositories\AgendamentosRepository;
use App\Repositories\FatCobrancaRepository;
use App\Repositories\PetsRepository;
use App\Repositories\VacinasRepository;
use App\Services\EstoqueService;
use App\Services\PetsService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller responsável pelos indicadores (KPIs) do dashboard principal.
 */
class Home extends BaseController
{
    protected PetsService $petsService;
    protected AgendamentosRepository $agendamentosRepo;
    protected PetsRepository $petsRepo;
    protected FatCobrancaRepository $cobrancaRepo;
    protected EstoqueService $estoqueService;
    protected VacinasRepository $vacinasRepo;

    public function __construct(
        ?PetsService $petsService = null,
        ?AgendamentosRepository $agendamentosRepo = null,
        ?PetsRepository $petsRepo = null,
        ?FatCobrancaRepository $cobrancaRepo = null,
        ?EstoqueService $estoqueService = null,
        ?VacinasRepository $vacinasRepo = null
    ) {
        $this->petsService = $petsService ?? new PetsService();
        $this->agendamentosRepo = $agendamentosRepo ?? new AgendamentosRepository();
        $this->petsRepo = $petsRepo ?? new PetsRepository();
        $this->cobrancaRepo = $cobrancaRepo ?? new FatCobrancaRepository();
        $this->estoqueService = $estoqueService ?? new EstoqueService();
        $this->vacinasRepo = $vacinasRepo ?? new VacinasRepository();
    }

    /**
     * API: Retorna os KPIs e indicadores do dashboard principal.
     */
    public function dashboard(): ResponseInterface
    {
        $month = (int) date('m');
        $year = date('Y');

        $agendaStats = $this->agendamentosRepo->getStats();

        $data = [
            'kpis' => [
                'hoje'      => $agendaStats['hoje'],
                'ativos'    => $this->petsRepo->getModel()->countAllResults(),
                'receita'   => $this->cobrancaRepo->getTotalMonth($month, $year),
                'pendentes' => $agendaStats['pendentes'],
            ],
            'upcoming'         => $this->agendamentosRepo->getUpcoming(168), // 7 dias
            'hoje'             => $this->agendamentosRepo->getByDate(date('Y-m-d')),
            'servicos'         => $this->agendamentosRepo->getServiceStats($month, $year),
            'recentes'         => $this->petsRepo->getRecent(3),
            'aniversariantes'  => $this->petsService->getBirthdaysOfMonth($month),
            'alertasEstoque'   => $this->estoqueService->getLowStockAlerts(),
            'proximasVacinas'  => $this->vacinasRepo->getUpcomingExpirations(7),
            'posConsulta'      => $this->agendamentosRepo->getPostCareCandidates(),
        ];

        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }
}
