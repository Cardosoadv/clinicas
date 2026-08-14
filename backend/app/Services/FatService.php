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
        $recentDespesas = $this->despesaRepo->getRecent(5);

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
            ],
            'transacoes' => array_slice($transacoes, 0, 5),
            'notas'      => $this->notaRepo->getRecent(5),
            'stats'      => [
                'por_servico'    => $this->getReceitaPorServico($month, $year),
                'formas_pgto'    => $this->getFormasPagamento($month, $year),
                'top_pets'       => $this->getTopPets($month, $year),
                'chart_data'     => $this->getChartReceitaDespesa(),
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
        // Se for uma despesa parcelada (presença de array de parcelas)
        if (isset($data['is_parcelado']) && $data['is_parcelado'] == 'true' && !empty($data['parcelas'])) {
            $parcelas = $data['parcelas'];
            if (is_string($parcelas)) {
                $parcelas = json_decode($parcelas, true);
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
     * Cria múltiplas despesas vinculadas como parcelas de um grupo.
     *
     * @param array $data Dados base da despesa
     * @param array $parcelas Lista de parcelas [{vencimento, valor}]
     * @return array
     */
    protected function createDespesaParcelada(array $data, array $parcelas): array
    {
        $grupoId = uniqid('GRP_', true);
        $totalParcelas = count($parcelas);
        $ids = [];

        foreach ($parcelas as $idx => $p) {
            $parcelaData = [
                'descricao'         => $data['descricao'],
                'categoria'         => $data['categoria'],
                'fornecedor'        => $data['fornecedor'] ?? null,
                'forma_pagamento'   => $data['forma_pagamento'] ?? null,
                'status'            => $data['status'] ?? 'Pendente',
                'valor'             => $p['valor'],
                'data_vencimento'   => $p['vencimento'],
                'des_grupo_id'      => $grupoId,
                'des_parcela_num'   => $idx + 1,
                'des_parcela_total' => $totalParcelas,
            ];

            $id = $this->despesaRepo->create($parcelaData);
            if ($id) {
                $ids[] = $id;
            }
        }

        if (count($ids) === $totalParcelas) {
            return $this->success("Despesa parcelada em {$totalParcelas}x criada com sucesso", ['ids' => $ids]);
        }

        return $this->error('Erro ao criar algumas ou todas as parcelas');
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
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateDespesa(int $id, array $data): array
    {
        $allowed = ['descricao', 'categoria', 'fornecedor', 'valor', 'data_vencimento', 'forma_pagamento', 'status'];
        $clean   = array_intersect_key($data, array_flip($allowed));

        if (empty($clean)) {
            return $this->error('Nenhum campo válido para atualizar.');
        }

        $ok = $this->despesaRepo->getModel()->update($id, $clean);
        if ($ok) {
            return $this->success('Despesa atualizada com sucesso.', ['id' => $id]);
        }
        return $this->error('Erro ao atualizar despesa.');
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
