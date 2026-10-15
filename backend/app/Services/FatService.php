<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\FatCobrancaRepository;
use App\Repositories\FatDespesaRepository;
use App\Repositories\FatNotaRepository;
use App\Repositories\PetsRepository;
use App\Models\ServicosModel;
use CodeIgniter\Events\Events;

/**
 * @property FatCobrancaRepository $repository
 */
class FatService extends BaseService
{
    /** Máximo de parcelas em uma despesa parcelada */
    public const MAX_PARCELAS = 60;

    public const ESCOPO_APENAS_ESTA = 'apenas_esta';
    public const ESCOPO_ESTA_E_SEGUINTES = 'esta_e_seguintes';
    public const ESCOPO_TODAS = 'todas';
    public const ESCOPOS = [self::ESCOPO_APENAS_ESTA, self::ESCOPO_ESTA_E_SEGUINTES, self::ESCOPO_TODAS];

    protected FatCobrancaRepository $cobrancaRepo;
    protected FatDespesaRepository $despesaRepo;
    protected FatNotaRepository $notaRepo;
    protected PetsRepository $petsRepo;
    protected ?EstoqueService $estoqueService = null;

    /**
     * Injeta as dependências do serviço de faturamento.
     *
     * @param FatCobrancaRepository|null $cobrancaRepo
     * @param FatDespesaRepository|null $despesaRepo
     * @param FatNotaRepository|null $notaRepo
     * @param PetsRepository|null $petsRepo
     * @param EstoqueService|null $estoqueService
     */
    public function __construct(
        ?FatCobrancaRepository $cobrancaRepo = null,
        ?FatDespesaRepository $despesaRepo = null,
        ?FatNotaRepository $notaRepo = null,
        ?PetsRepository $petsRepo = null,
        ?EstoqueService $estoqueService = null
    ) {
        $this->cobrancaRepo = $cobrancaRepo ?? new FatCobrancaRepository();
        $this->repository   = $this->cobrancaRepo;
        $this->despesaRepo  = $despesaRepo  ?? new FatDespesaRepository();
        $this->notaRepo     = $notaRepo     ?? new FatNotaRepository();
        $this->petsRepo     = $petsRepo     ?? new PetsRepository();
        $this->estoqueService = $estoqueService;
    }

    /**
     * Lazy loader for EstoqueService to avoid circular dependency
     *
     * @return EstoqueService
     */
    protected function getEstoqueService(): EstoqueService
    {
        if ($this->estoqueService === null) {
            $this->estoqueService = new EstoqueService();
        }
        return $this->estoqueService;
    }

    /**
     * Mantém uma despesa por grupo (parcelas/recorrência), preservando a ordem de chegada.
     * Dentro do grupo, fica a de menor número de parcela (ou a primeira encontrada).
     *
     * @param array $despesas
     * @return array
     */
    protected function colapsarGrupos(array $despesas): array
    {
        $porGrupo = [];
        $resultado = [];

        foreach ($despesas as $d) {
            $grupo = $d['des_grupo_id'] ?? null;
            if ($grupo === null) {
                $resultado[] = $d;
                continue;
            }
            if (!isset($porGrupo[$grupo])) {
                $porGrupo[$grupo] = count($resultado);
                $resultado[] = $d;
                continue;
            }
            $atual = $resultado[$porGrupo[$grupo]];
            if (($d['des_parcela_num'] ?? PHP_INT_MAX) < ($atual['des_parcela_num'] ?? PHP_INT_MAX)) {
                $resultado[$porGrupo[$grupo]] = $d;
            }
        }

        return $resultado;
    }

    /**
     * Retorna dados para o dashboard financeiro
     *
     * @return array
     */
    public function getDashboardData(): array
    {
        $month = date('m');
        $year = date('Y');

        $receitas = $this->cobrancaRepo->getTotalMonth($month, $year);
        $despesas = $this->despesaRepo->getTotalMonth($month, $year);
        $saldo = $receitas - $despesas;

        // Optimized: Fetch both Pendente and Atrasado totals in a single query
        $cobrancaStats = $this->cobrancaRepo->getSummaryStats();

        // Recent shared transactions
        $recentCobrancas = $this->cobrancaRepo->getRecent(5);
        // um parcelamento cria várias despesas de uma vez: mostra uma por grupo
        $recentDespesas = array_slice($this->colapsarGrupos($this->despesaRepo->getRecent(30)), 0, 5);

        $aPagar        = $this->despesaRepo->getResumoAPagar(date('Y-m-d'));
        $aPagarPorMes  = array_slice($this->despesaRepo->getPendentesPorMes(date('Y-m-01')), 0, 6);

        $transacoes = array_merge($recentCobrancas, $recentDespesas);
        usort($transacoes, static function ($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });

        return [
            'kpis' => [
                'receitas' => $receitas,
                'despesas' => $despesas,
                'saldo'    => $saldo,
                'a_receber' => $cobrancaStats['a_receber'],
                'vencidos'  => $cobrancaStats['vencidos'],
                'a_pagar'   => $aPagar['a_pagar'] ?? 0.0,
                'despesas_vencidas' => $aPagar['vencidas'] ?? 0.0,
            ],
            'transacoes' => array_slice($transacoes, 0, 5),
            'notas'      => $this->notaRepo->getRecent(5),
            'stats'      => [
                'por_servico'    => $this->getReceitaPorServico($month, $year),
                'formas_pgto'    => $this->getFormasPagamento($month, $year),
                'top_pets'       => $this->getTopPets($month, $year),
                'chart_data'     => $this->getChartReceitaDespesa(),
                'a_pagar_por_mes' => $aPagarPorMes,
            ],
            // Optimization: Fetch only ID and Name for dashboard lookups
            'pets'       => $this->petsRepo->findForLookup(),
            'recebiveis' => $this->getRecebiveis(),
        ];
    }

    /**
     * Retorna contas a receber
     *
     * @return array
     */
    public function getRecebiveis(): array
    {
        return $this->cobrancaRepo->getModel()
            ->select('fat_cobrancas.*, pacientes.paciente_nome, pacientes.paciente_avatar')
            ->join('pacientes', 'pacientes.paciente_id = fat_cobrancas.paciente_id')
            ->whereIn('fat_cobrancas.status', ['Pendente', 'Atrasado', 'Parcial'])
            ->orderBy('vencimento', 'ASC')
            ->findAll();
    }

    /**
     * Retorna lançamentos recentes
     *
     * @return array
     */
    public function getLancamentosData(): array
    {
        return [
            'cobrancas' => $this->cobrancaRepo->getRecent(20),
            'despesas'  => $this->despesaRepo->getRecent(20),
        ];
    }

    /**
     * Lista despesas ordenadas por vencimento, com filtro por mês/status e paginação.
     *
     * @param array $filtros mes (AAAA-MM), status, page, per_page
     * @return array
     */
    public function getDespesas(array $filtros): array
    {
        $mes = $filtros['mes'] ?? null;
        if ($mes === '' || $mes === null) {
            $mes = null;
        } elseif (!is_string($mes) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)) {
            return $this->error('Mês inválido. Use o formato AAAA-MM.');
        }

        $status = $filtros['status'] ?? null;
        if ($status === '' || $status === null) {
            $status = null;
        } elseif (!in_array($status, ['Pendente', 'Pago', 'Cancelado'], true)) {
            return $this->error('Status inválido.');
        }

        $page    = max(1, (int) ($filtros['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($filtros['per_page'] ?? 20)));

        $resultado = $this->despesaRepo->listar($mes, $status, $page, $perPage);

        return [
            'status' => 'success',
            'data'   => [
                'items'       => $resultado['items'],
                'total'       => $resultado['total'],
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => max(1, (int) ceil($resultado['total'] / $perPage)),
            ],
        ];
    }

    /**
     * Retorna todas as notas fiscais/recibos emitidos, mais recentes primeiro.
     *
     * @return array
     */
    public function getNotas(): array
    {
        return $this->notaRepo->getModel()->orderBy('created_at', 'DESC')->findAll();
    }

    /**
     * Retorna o total a receber
     *
     * @return float
     */
    protected function getAReceberTotal(): float
    {
        $result = $this->cobrancaRepo->getModel()->selectSum('valor')->where('status', 'Pendente')->first();
        return (float) ($result['valor'] ?? 0);
    }

    /**
     * Retorna o total vencido
     *
     * @return float
     */
    protected function getVencidosTotal(): float
    {
        $result = $this->cobrancaRepo->getModel()->selectSum('valor')->where('status', 'Atrasado')->first();
        return (float) ($result['valor'] ?? 0);
    }

    /**
     * Retorna receita agrupada por serviço
     *
     * @param string $month
     * @param string $year
     * @return array
     */
    protected function getReceitaPorServico(string $month, string $year): array
    {
        $start = "{$year}-{$month}-01";
        $end = date('Y-m-t', strtotime($start));

        return $this->cobrancaRepo->getModel()
            ->select('servico, SUM(valor) as total')
            ->where('data_servico >=', $start)
            ->where('data_servico <=', $end)
            ->groupBy('servico')
            ->orderBy('total', 'DESC')
            ->findAll();
    }

    /**
     * Retorna receita agrupada por forma de pagamento
     *
     * @param string $month
     * @param string $year
     * @return array
     */
    protected function getFormasPagamento(string $month, string $year): array
    {
        $start = "{$year}-{$month}-01";
        $end = date('Y-m-t', strtotime($start));

        return $this->cobrancaRepo->getModel()
            ->select('forma_pagamento, SUM(valor) as total')
            ->where('data_servico >=', $start)
            ->where('data_servico <=', $end)
            ->groupBy('forma_pagamento')
            ->orderBy('total', 'DESC')
            ->findAll();
    }

    /**
     * Retorna os pets com maior faturamento
     *
     * @param string $month
     * @param string $year
     * @return array
     */
    protected function getTopPets(string $month, string $year): array
    {
        $start = "{$year}-{$month}-01";
        $end = date('Y-m-t', strtotime($start));

        return $this->cobrancaRepo->getModel()
            ->select('pacientes.paciente_id, pacientes.paciente_nome, pacientes.paciente_avatar, COUNT(fat_cobrancas.id) as qtd, SUM(fat_cobrancas.valor) as total')
            ->join('pacientes', 'pacientes.paciente_id = fat_cobrancas.paciente_id')
            ->where('fat_cobrancas.data_servico >=', $start)
            ->where('fat_cobrancas.data_servico <=', $end)
            ->groupBy('fat_cobrancas.paciente_id')
            ->orderBy('total', 'DESC')
            ->findAll(3);
    }

    /**
     * Retorna dados para o gráfico de Receita vs Despesa (Últimos 7 meses)
     *
     * @return array
     */
    protected function getChartReceitaDespesa(): array
    {
        $data = [];
        $mesesPt = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        
        $max_value = 0;
        
        for ($i = 6; $i >= 0; $i--) {
            // Usa mktime para garantir que os meses sejam calculados corretamente
            // considerando meses com 31 dias (strtotime pode pular meses)
            $m = (int)date('m') - $i;
            $y = (int)date('Y');
            while ($m <= 0) {
                $m += 12;
                $y -= 1;
            }
            $label = $mesesPt[$m - 1];
            
            $receita = $this->cobrancaRepo->getTotalMonth($m, $y);
            $despesa = $this->despesaRepo->getTotalMonth($m, $y);
            
            $data[] = [
                'label'   => $label,
                'receita' => $receita,
                'despesa' => $despesa
            ];
            
            if ($receita > $max_value) {
                $max_value = $receita;
            }
            if ($despesa > $max_value) {
                $max_value = $despesa;
            }
        }
        
        return [
            'points'    => $data,
            'max_value' => $max_value > 0 ? $max_value : 1000
        ];
    }

    /**
     * Busca a cobrança mais recente vinculada a um agendamento.
     *
     * @param int $agendamentoId
     * @return array|null
     */
    public function findByAgendamento(int $agendamentoId): ?array
    {
        return $this->cobrancaRepo->findByAgendamento($agendamentoId);
    }

    /**
     * Cria uma nova cobrança
     *
     * @param array $data
     * @return array
     */
    public function createCobranca(array $data): array
    {
        // Se a forma de pagamento não for Pacote, limpar o campo do banco para evitar erro de FK
        if (isset($data['forma_pagamento']) && $data['forma_pagamento'] !== 'Pacote') {
            unset($data['pacote_id']);
        } elseif (isset($data['pacote_id']) && empty($data['pacote_id'])) {
            $data['pacote_id'] = null;
        }

        // Se a forma de pagamento for Pacote, validar e consumir
        if (isset($data['forma_pagamento']) && $data['forma_pagamento'] === 'Pacote') {
            if (empty($data['pacote_id'])) {
                return $this->error('ID do pacote não informado para pagamento com Pacote.');
            }

            $ps = new PacoteService();

            // Tenta identificar o servico_id se vier o nome do serviço
            $servicoId = null;
            if (!empty($data['servico_id'])) {
                // ✅ Preferência ao ID já enviado pela view
                $servicoId = (int) $data['servico_id'];
            } elseif (!empty($data['servico'])) {
                // Fallback: busca por nome (quando chamado de outros contextos)
                $servicoModel = new ServicosModel();
                $s = $servicoModel->where('ser_nome', $data['servico'])->first();
                if ($s) {
                    $servicoId = (int) $s['ser_id'];
                }
            }
            $consumo = $ps->consumir((int)$data['pacote_id'], $servicoId, (float)($data['valor'] ?? 0));

            if ($consumo['status'] === 'error') {
                return $this->error('Falha ao consumir pacote: ' . $consumo['message']);
            }

            // Se consumiu com sucesso, a cobrança já nasce como 'Pago'
            $data['status'] = 'Pago';
        }

        $id = $this->cobrancaRepo->create($data);
        if ($id) {
            // Se a cobrança for paga no ato e houver um serviço vinculado, deduzir estoque (BOM)
            if (($data['status'] ?? 'Pendente') === 'Pago' && !empty($data['servico_id'])) {
                $this->getEstoqueService()->deduzirEstoquePorServico((int) $data['servico_id']);
            }

            return $this->success('Cobrança criada com sucesso', ['id' => $id]);
        }
        return $this->error('Erro ao criar cobrança');
    }

    /**
     * Atualiza uma cobrança existente.
     * Se o status mudar para 'Pago' e houver um pacote vinculado, o pacote é ativado.
     *
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateCobranca(int $id, array $data): array
    {
        $cobranca = $this->cobrancaRepo->findById($id);
        if (!$cobranca) {
            return $this->error('Cobrança não encontrada.');
        }

        $allowed = ['servico', 'valor', 'desconto', 'data_servico', 'forma_pagamento', 'parcelas', 'vencimento', 'status', 'observacoes'];
        $clean   = array_intersect_key($data, array_flip($allowed));

        if (empty($clean)) {
            return $this->error('Nenhum campo válido para atualizar.');
        }

        $ok = $this->cobrancaRepo->getModel()->update($id, $clean);
        if ($ok) {
            // Se mudou para Pago (Dedução única para evitar duplicidade)
            if (isset($clean['status']) && $clean['status'] === 'Pago' && $cobranca['status'] !== 'Pago') {
                // Lógica de Ativação de Pacote
                if (!empty($cobranca['pacote_id'])) {
                    Events::trigger('cobranca_paga', (int) $cobranca['pacote_id']);
                }

                // Lógica de Dedução de Estoque (BOM)
                $servicoId = $clean['servico_id'] ?? $cobranca['servico_id'] ?? null;
                if ($servicoId) {
                    $this->getEstoqueService()->deduzirEstoquePorServico((int) $servicoId);
                }
            }

            return $this->success('Cobrança atualizada com sucesso.', ['id' => $id]);
        }
        return $this->error('Erro ao atualizar cobrança.');
    }

    /**
     * Cria uma nova despesa
     *
     * @param array $data
     * @return array
     */
    public function createDespesa(array $data): array
    {
        $isParcelado  = isset($data['is_parcelado']) && $data['is_parcelado'] == 'true';
        $isRecorrente = isset($data['is_recorrente']) && $data['is_recorrente'] == 'true';

        if ($isParcelado && $isRecorrente) {
            return $this->error('Uma despesa não pode ser parcelada e recorrente ao mesmo tempo.');
        }

        // Despesa parcelada: parcelas enviadas prontas ou geradas a partir de valor total + quantidade
        if ($isParcelado) {
            $parcelas = $this->resolverParcelas($data);
            if (is_string($parcelas)) {
                return $this->error($parcelas);
            }
            return $this->createDespesaParcelada($data, $parcelas);
        }

        // Se for uma despesa recorrente
        if (isset($data['is_recorrente']) && $data['is_recorrente'] == 'true' && !empty($data['recorrencia'])) {
            return $this->createDespesaRecorrente($data);
        }

        $id = $this->despesaRepo->create($data);
        if ($id) {
            return $this->success('Despesa criada com sucesso', ['id' => $id]);
        }
        return $this->error('Erro ao criar despesa');
    }

    /**
     * Resolve a lista de parcelas de uma despesa parcelada.
     *
     * Aceita parcelas prontas (`parcelas`: array ou JSON de {valor, vencimento}) ou gera
     * as parcelas a partir de `valor` (total), `parcelas_qtd`, `data_vencimento` e `periodicidade`.
     *
     * @param array $data
     * @return array|string Lista de parcelas [{valor, vencimento}] ou mensagem de erro
     */
    protected function resolverParcelas(array $data): array|string
    {
        if (!empty($data['parcelas'])) {
            $parcelas = $data['parcelas'];
            if (is_string($parcelas)) {
                $parcelas = json_decode($parcelas, true);
            }
            return $this->validarParcelas($parcelas);
        }

        $qtd = $data['parcelas_qtd'] ?? null;
        if (!is_numeric($qtd) || (int) $qtd != $qtd || (int) $qtd < 2 || (int) $qtd > self::MAX_PARCELAS) {
            return 'Informe a quantidade de parcelas (de 2 a ' . self::MAX_PARCELAS . ').';
        }

        $periodicidade = $data['periodicidade'] ?? 'mensal';
        if (!in_array($periodicidade, ['mensal', 'quinzenal', 'semanal'], true)) {
            return 'Periodicidade inválida. Use mensal, quinzenal ou semanal.';
        }

        $valor = $data['valor'] ?? null;
        if (!is_numeric($valor) || (float) $valor <= 0) {
            return 'O valor total da despesa deve ser maior que zero.';
        }

        $inicio = $this->parseData($data['data_vencimento'] ?? null);
        if ($inicio === null) {
            return 'Data de vencimento da primeira parcela inválida.';
        }

        $qtd        = (int) $qtd;
        $totalCents = (int) round((float) $valor * 100);
        if ($totalCents < $qtd) {
            return 'O valor total é pequeno demais para dividir em ' . $qtd . ' parcelas.';
        }

        // Divisão em centavos; a última parcela absorve a diferença
        $base     = intdiv($totalCents, $qtd);
        $diferenca = $totalCents - ($base * $qtd);

        $parcelas = [];
        for ($i = 0; $i < $qtd; $i++) {
            $cents = $base + ($i === $qtd - 1 ? $diferenca : 0);
            $parcelas[] = [
                'valor'      => $cents / 100,
                'vencimento' => $this->vencimentoDaParcela($inicio, $periodicidade, $i)->format('Y-m-d'),
            ];
        }

        return $parcelas;
    }

    /**
     * Valida parcelas enviadas prontas pelo cliente.
     *
     * @param mixed $parcelas
     * @return array|string Parcelas normalizadas ou mensagem de erro
     */
    protected function validarParcelas(mixed $parcelas): array|string
    {
        if (!is_array($parcelas) || $parcelas === []) {
            return 'Lista de parcelas inválida.';
        }
        if (count($parcelas) > self::MAX_PARCELAS) {
            return 'Máximo de ' . self::MAX_PARCELAS . ' parcelas.';
        }

        $normalizadas = [];
        foreach (array_values($parcelas) as $idx => $p) {
            $n = $idx + 1;
            if (!is_array($p) || !isset($p['valor']) || !is_numeric($p['valor']) || (float) $p['valor'] <= 0) {
                return "Valor inválido na parcela {$n}.";
            }
            $venc = $this->parseData($p['vencimento'] ?? null);
            if ($venc === null) {
                return "Vencimento inválido na parcela {$n}.";
            }
            $normalizadas[] = [
                'valor'      => round((float) $p['valor'], 2),
                'vencimento' => $venc->format('Y-m-d'),
            ];
        }

        return $normalizadas;
    }

    /**
     * Converte 'Y-m-d' em data, retornando null se inválida.
     */
    protected function parseData(mixed $valor): ?\DateTimeImmutable
    {
        if (!is_string($valor)) {
            return null;
        }
        $dt = \DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

        return ($dt !== false && $dt->format('Y-m-d') === $valor) ? $dt : null;
    }

    /**
     * Calcula o vencimento da parcela $indice (0-based) a partir da data base.
     * Mensal: mantém o dia, limitado ao último dia do mês (31/01 → 28/02 → 31/03).
     */
    protected function vencimentoDaParcela(\DateTimeImmutable $inicio, string $periodicidade, int $indice): \DateTimeImmutable
    {
        if ($periodicidade === 'semanal') {
            return $inicio->modify('+' . (7 * $indice) . ' days');
        }
        if ($periodicidade === 'quinzenal') {
            return $inicio->modify('+' . (14 * $indice) . ' days');
        }

        $mes = $inicio->modify('first day of this month')->modify("+{$indice} months");
        $dia = min((int) $inicio->format('j'), (int) $mes->format('t'));

        return $mes->setDate((int) $mes->format('Y'), (int) $mes->format('n'), $dia);
    }

    /**
     * Gera um UUID v4 para identificar o grupo de parcelas.
     */
    protected function novoGrupoId(): string
    {
        $bytes    = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * Cria múltiplas despesas vinculadas como parcelas de um grupo, em uma única transação.
     * A primeira parcela mantém o status enviado; as demais nascem Pendentes.
     *
     * @param array $data Dados base da despesa
     * @param array $parcelas Lista de parcelas [{vencimento, valor}]
     * @return array
     */
    protected function createDespesaParcelada(array $data, array $parcelas): array
    {
        $grupoId       = $this->novoGrupoId();
        $totalParcelas = count($parcelas);
        $ids           = [];

        $db = \Config\Database::connect();
        $db->resetTransStatus();
        $db->transBegin();

        try {
            foreach ($parcelas as $idx => $p) {
                $id = $this->despesaRepo->create([
                    'descricao'         => $data['descricao'],
                    'categoria'         => $data['categoria'],
                    'fornecedor'        => $data['fornecedor'] ?? null,
                    'forma_pagamento'   => $data['forma_pagamento'] ?? null,
                    'status'            => $idx === 0 ? ($data['status'] ?? 'Pendente') : 'Pendente',
                    'valor'             => $p['valor'],
                    'data_vencimento'   => $p['vencimento'],
                    'des_grupo_id'      => $grupoId,
                    'des_parcela_num'   => $idx + 1,
                    'des_parcela_total' => $totalParcelas,
                ]);

                if (!$id) {
                    break;
                }
                $ids[] = $id;
            }
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->error('Erro ao criar as parcelas: ' . $e->getMessage());
        }

        if (count($ids) !== $totalParcelas || $db->transStatus() === false) {
            $db->transRollback();
            return $this->error('Erro ao criar as parcelas. Nenhuma parcela foi registrada.');
        }

        $db->transCommit();

        return $this->success(
            "Despesa parcelada em {$totalParcelas}x criada com sucesso",
            ['ids' => $ids, 'des_grupo_id' => $grupoId]
        );
    }

    /**
     * Cria múltiplas instâncias de uma despesa baseadas em uma regra de recorrência.
     *
     * @param array $data Dados base da despesa
     * @return array
     */
    protected function createDespesaRecorrente(array $data): array
    {
        $recorrencia = $data['recorrencia'];
        $dataFimStr = !empty($data['recorrencia_fim']) ? $data['recorrencia_fim'] : date('Y-m-d', strtotime('+1 year'));
        $currentDateStr = $data['data_vencimento'] ?? date('Y-m-d');
        
        $intervalMap = [
            'semanal'   => '+1 week',
            'quinzenal' => '+2 weeks',
            'mensal'    => '+1 month',
            'anual'     => '+1 year'
        ];
        $interval = $intervalMap[$recorrencia] ?? '+1 month';
        
        $grupoId = uniqid('FIX_', true);
        $ids = [];
        $count = 0;
        $maxIterations = 60; // Limite de 5 anos para mensal, ou ~1 ano para semanal.

        try {
            $db = \Config\Database::connect();
            $db->transStart();

            while ($currentDateStr <= $dataFimStr && $count < $maxIterations) {
                $iterationData = [
                    'descricao'         => $data['descricao'],
                    'categoria'         => $data['categoria'],
                    'fornecedor'        => $data['fornecedor'] ?? null,
                    'forma_pagamento'   => $data['forma_pagamento'] ?? null,
                    'status'            => 'Pendente', // Recorrência futura nasce pendente
                    'valor'             => $data['valor'],
                    'data_vencimento'   => $currentDateStr,
                    'des_grupo_id'      => $grupoId,
                    'des_recorrencia'   => $recorrencia,
                    'des_recorrencia_fim' => $dataFimStr
                ];

                // A primeira parcela pode manter o status enviado (ex: se já pagou a deste mês)
                if ($count === 0 && isset($data['status'])) {
                    $iterationData['status'] = $data['status'];
                }

                $id = $this->despesaRepo->create($iterationData);
                if ($id) {
                    $ids[] = $id;
                }
                
                $currentDateStr = date('Y-m-d', strtotime($interval, strtotime($currentDateStr)));
                $count++;
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->error("Erro ao processar série de despesas fixas.");
            }

            return $this->success("Despesa fixa registrada com sucesso ({$count} ocorrências).", ['ids' => $ids]);

        } catch (\Exception $e) {
            return $this->error("Erro ao criar recorrência: " . $e->getMessage());
        }
    }

    /**
     * Atualiza os dados de uma despesa existente.
     *
     * Com `escopo` (apenas_esta | esta_e_seguintes | todas) os campos cadastrais
     * (descricao, categoria, fornecedor, forma_pagamento) também são aplicados às demais
     * parcelas do grupo. `valor`, `data_vencimento` e `status` valem só para a própria parcela.
     * Parcelas já pagas não têm a forma de pagamento alterada.
     *
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateDespesa(int $id, array $data): array
    {
        $escopo = $data['escopo'] ?? self::ESCOPO_APENAS_ESTA;
        if (!in_array($escopo, self::ESCOPOS, true)) {
            return $this->error('Escopo inválido. Use apenas_esta, esta_e_seguintes ou todas.');
        }

        $allowed = ['descricao', 'categoria', 'fornecedor', 'valor', 'data_vencimento', 'forma_pagamento', 'status'];
        $clean   = array_intersect_key($data, array_flip($allowed));

        if (empty($clean)) {
            return $this->error('Nenhum campo válido para atualizar.');
        }

        if ($escopo === self::ESCOPO_APENAS_ESTA) {
            $ok = $this->despesaRepo->getModel()->update($id, $clean);
            if ($ok) {
                return $this->success('Despesa atualizada com sucesso.', ['id' => $id]);
            }
            return $this->error('Erro ao atualizar despesa.');
        }

        $despesa = $this->despesaRepo->findById($id);
        if (!$despesa) {
            return $this->error('Despesa não encontrada.');
        }

        $comuns  = array_intersect_key($clean, array_flip(['descricao', 'categoria', 'fornecedor', 'forma_pagamento']));
        $demais  = array_filter(
            $this->despesasDoEscopo($despesa, $escopo),
            static fn(array $d): bool => (int) $d['id'] !== $id
        );

        return $this->emTransacao(function () use ($id, $clean, $comuns, $demais, $escopo): array {
            $model       = $this->despesaRepo->getModel();
            $atualizadas = 1;

            if (!$model->update($id, $clean)) {
                return $this->error('Erro ao atualizar despesa.');
            }

            foreach ($demais as $outra) {
                $dados = $comuns;
                if (($outra['status'] ?? null) === 'Pago') {
                    unset($dados['forma_pagamento']);
                }
                if ($dados === []) {
                    continue;
                }
                if (!$model->update((int) $outra['id'], $dados)) {
                    return $this->error('Erro ao atualizar as parcelas do grupo.');
                }
                $atualizadas++;
            }

            return $this->success(
                "Despesa atualizada com sucesso ({$atualizadas} parcela(s)).",
                ['id' => $id, 'escopo' => $escopo, 'atualizadas' => $atualizadas]
            );
        });
    }

    /**
     * Cancela uma despesa ou, conforme o escopo, as parcelas pendentes do grupo.
     * Parcelas já pagas nunca são alteradas.
     *
     * @param int $id
     * @param string $escopo apenas_esta | esta_e_seguintes | todas
     * @return array
     */
    public function cancelarDespesa(int $id, string $escopo = self::ESCOPO_APENAS_ESTA): array
    {
        if (!in_array($escopo, self::ESCOPOS, true)) {
            return $this->error('Escopo inválido. Use apenas_esta, esta_e_seguintes ou todas.');
        }

        $despesa = $this->despesaRepo->findById($id);
        if (!$despesa) {
            return $this->error('Despesa não encontrada.');
        }

        $alvo       = $this->despesasDoEscopo($despesa, $escopo);
        $pendentes  = array_filter($alvo, static fn(array $d): bool => ($d['status'] ?? null) === 'Pendente');
        $ignoradas  = count(array_filter($alvo, static fn(array $d): bool => ($d['status'] ?? null) === 'Pago'));

        if ($pendentes === []) {
            return $this->error('Nenhuma parcela pendente para cancelar.', ['ignoradas_pagas' => $ignoradas]);
        }

        return $this->emTransacao(function () use ($pendentes, $ignoradas): array {
            $model = $this->despesaRepo->getModel();

            foreach ($pendentes as $d) {
                if (!$model->update((int) $d['id'], ['status' => 'Cancelado'])) {
                    return $this->error('Erro ao cancelar as parcelas.');
                }
            }

            $canceladas = count($pendentes);

            return $this->success(
                "{$canceladas} parcela(s) cancelada(s)" . ($ignoradas > 0 ? "; {$ignoradas} já paga(s) não alterada(s)" : '') . '.',
                ['canceladas' => $canceladas, 'ignoradas_pagas' => $ignoradas]
            );
        });
    }

    /**
     * Lista as parcelas de um grupo, ordenadas por número da parcela.
     *
     * @param string $grupoId
     * @return array
     */
    public function getGrupoDespesas(string $grupoId): array
    {
        $parcelas = $this->despesaRepo->getByGrupo($grupoId);
        if ($parcelas === []) {
            return $this->error('Grupo de despesas não encontrado.');
        }

        return $this->success('Parcelas do grupo.', ['des_grupo_id' => $grupoId, 'parcelas' => $parcelas]);
    }

    /**
     * Despesas afetadas pelo escopo a partir de uma despesa de referência.
     * Sem grupo, o escopo sempre se resume à própria despesa. Em recorrências
     * (sem número de parcela) "esta e seguintes" usa a data de vencimento.
     *
     * @param array $despesa
     * @param string $escopo
     * @return array
     */
    protected function despesasDoEscopo(array $despesa, string $escopo): array
    {
        if ($escopo === self::ESCOPO_APENAS_ESTA || empty($despesa['des_grupo_id'])) {
            return [$despesa];
        }

        $grupo = $this->despesaRepo->getByGrupo((string) $despesa['des_grupo_id']);
        if ($escopo === self::ESCOPO_TODAS) {
            return $grupo;
        }

        $num = $despesa['des_parcela_num'] ?? null;

        return array_values(array_filter($grupo, static function (array $d) use ($despesa, $num): bool {
            if ($num !== null && ($d['des_parcela_num'] ?? null) !== null) {
                return (int) $d['des_parcela_num'] >= (int) $num;
            }
            return (string) $d['data_vencimento'] >= (string) $despesa['data_vencimento'];
        }));
    }

    /**
     * Executa $fn dentro de uma transação. Qualquer resultado diferente de sucesso,
     * exceção ou falha de query desfaz tudo.
     *
     * @param callable():array $fn
     * @return array
     */
    protected function emTransacao(callable $fn): array
    {
        $db = \Config\Database::connect();
        $db->resetTransStatus();
        $db->transBegin();

        try {
            $result = $fn();
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->error('Erro ao processar a operação: ' . $e->getMessage());
        }

        if (($result['status'] ?? null) !== 'success') {
            $db->transRollback();
            return $result;
        }
        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->error('Erro ao processar a operação. Nenhuma alteração foi gravada.');
        }

        $db->transCommit();

        return $result;
    }

    /**
     * Processa o upload de comprovante para writable/despesas/{id}/.
     * Retorna o caminho relativo salvo (ou erro).
     *
     * @param int $id
     * @param \CodeIgniter\HTTP\Files\UploadedFile|null $file
     * @return array
     */
    public function uploadComprovante(int $id, $file): array
    {
        if (!$file || !$file->isValid()) {
            return $this->error('Arquivo inválido ou não enviado.');
        }

        $dir = WRITEPATH . 'despesas/' . $id . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Gerar nome único preservando a extensão original
        $ext      = $file->guessExtension();
        $filename = 'comprovante_' . date('Ymd_His') . '.' . $ext;

        if (!$file->move($dir, $filename)) {
            return $this->error('Falha ao salvar o arquivo.');
        }

        // Salvar caminho relativo no campo comprovante da despesa
        $relativePath = 'despesas/' . $id . '/' . $filename;
        $this->despesaRepo->getModel()->update($id, ['comprovante' => $relativePath]);

        return $this->success('Comprovante enviado com sucesso.', [
            'path'     => $relativePath,
            'filename' => $filename,
        ]);
    }

    /**
     * Cria uma nova nota fiscal
     *
     * @param array $data
     * @return array
     */
    public function createNota(array $data): array
    {
        if (empty($data['hash'])) {
            $data['hash'] = md5(uniqid('', true) . microtime(true));
        }

        $id = $this->notaRepo->create($data);
        if ($id) {
            return $this->success('Nota fiscal criada com sucesso', ['id' => $id]);
        }
        return $this->error('Erro ao criar nota fiscal');
    }

    /**
     * Gera o QR Code em base64 para a URL pública do recibo
     *
     * @param string $data
     * @return string
     */
    public function generateQR(string $data): string {
        $options = new \chillerlan\QRCode\QROptions([
            'version'      => 5,
            'outputBase64' => true,
        ]);

        $qrcode = new \chillerlan\QRCode\QRCode($options);
        return $qrcode->render($data);
    }
}
