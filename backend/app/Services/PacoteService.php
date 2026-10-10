<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AgendamentosRepository;
use App\Repositories\PacotesRepository;
use App\Repositories\PacoteItensRepository;
use App\Repositories\FatCobrancaRepository;
use App\Repositories\PacoteUsoRepository;
use Exception;

/**
 * Serviço responsável pela gestão de pacotes de serviços e créditos (pré-pagos).
 */
class PacoteService extends BaseService
{
    protected PacotesRepository $pacotesRepo;
    protected PacoteItensRepository $itensRepo;
    protected FatCobrancaRepository $cobrancaRepo;
    protected PacoteUsoRepository $usoRepo;
    protected ?AgendaService $agendaService;
    protected ?AgendamentosRepository $agendamentosRepo;

    /** Periodicidades aceitas no pré-agendamento das sessões de um pacote. */
    public const PERIODICIDADES_PREAGENDAMENTO = ['semanal', 'mensal'];

    /**
     * Injeta as dependências do serviço de pacotes.
     *
     * @param PacotesRepository|null $pacotesRepo
     * @param PacoteItensRepository|null $itensRepo
     * @param FatCobrancaRepository|null $cobrancaRepo
     * @param PacoteUsoRepository|null $usoRepo
     * @param AgendaService|null $agendaService
     * @param AgendamentosRepository|null $agendamentosRepo
     */
    public function __construct(
        ?PacotesRepository $pacotesRepo = null,
        ?PacoteItensRepository $itensRepo = null,
        ?FatCobrancaRepository $cobrancaRepo = null,
        ?PacoteUsoRepository $usoRepo = null,
        ?AgendaService $agendaService = null,
        ?AgendamentosRepository $agendamentosRepo = null
    ) {
        $this->pacotesRepo = $pacotesRepo ?? new PacotesRepository();
        $this->repository   = $this->pacotesRepo;
        $this->itensRepo    = $itensRepo    ?? new PacoteItensRepository();
        $this->cobrancaRepo = $cobrancaRepo ?? new FatCobrancaRepository();
        $this->usoRepo      = $usoRepo      ?? new PacoteUsoRepository();
        // AgendaService instancia PacoteService no próprio construtor; por isso
        // as dependências da Agenda são criadas sob demanda (ver agenda()).
        $this->agendaService    = $agendaService;
        $this->agendamentosRepo = $agendamentosRepo;
    }

    /**
     * Retorna o serviço da Agenda, criando-o na primeira vez em que é usado.
     *
     * @return AgendaService
     */
    protected function agenda(): AgendaService
    {
        return $this->agendaService ??= new AgendaService(null, null, null, $this);
    }

    /**
     * Retorna o repositório de agendamentos, criando-o na primeira vez em que é usado.
     *
     * @return AgendamentosRepository
     */
    protected function agendamentos(): AgendamentosRepository
    {
        return $this->agendamentosRepo ??= new AgendamentosRepository();
    }

    /**
     * Retorna o repositório de pacotes
     *
     * @return PacotesRepository
     */
    public function getPacotesRepository(): PacotesRepository
    {
        return $this->pacotesRepo;
    }

    /**
     * Retorna o repositório de itens do pacote
     *
     * @return PacoteItensRepository
     */
    public function getItensRepository(): PacoteItensRepository
    {
        return $this->itensRepo;
    }

    /**
     * Cria um novo pacote e gera a cobrança correspondente.
     *
     * @param array $data
     * @return array
     */
    public function createPacote(array $data): array
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. Criar o Pacote
            $pacoteData = [
                'paciente_id'   => $data['paciente_id'],
                'nome'          => $data['nome'],
                'tipo'          => $data['tipo'] ?? 'Serviços',
                'saldo_valor'   => ($data['tipo'] ?? 'Serviços') === 'Crédito' ? ($data['valor_total'] ?? 0) : 0,
                'data_validade' => !empty($data['data_validade']) ? $data['data_validade'] : null,
                'status'        => 'Pendente',
                'observacoes'   => $data['observacoes'] ?? ''
            ];

            $pacoteId = $this->pacotesRepo->create($pacoteData);

            // 2. Criar os Itens (se for tipo Serviços)
            if ($pacoteData['tipo'] === 'Serviços' && !empty($data['itens'])) {
                foreach ($data['itens'] as $item) {
                    $this->itensRepo->create([
                        'pacote_id'        => $pacoteId,
                        'servico_id'       => $item['servico_id'] ?? null,
                        'item_nome'        => $item['item_nome'] ?? null,
                        'quantidade_total' => $item['quantidade'] ?? 1,
                        'quantidade_usada' => 0,
                        'valor_unitario'   => $item['valor_unitario'] ?? 0
                    ]);
                }
            }

            // 3. Gerar a Cobrança no Faturamento
            $cobrancaRepoData = [
                'paciente_id'     => $data['paciente_id'],
                'servico'         => "Compra de Pacote: " . $data['nome'],
                'valor'           => $data['valor_total'],
                'desconto'        => $data['desconto'] ?? 0.00,
                'data_servico'    => date('Y-m-d'),
                'forma_pagamento' => $data['forma_pagamento'] ?? 'Cartão',
                'vencimento'      => $data['vencimento'] ?? date('Y-m-d'),
                'status'          => 'Pendente',
                'pacote_id'       => $pacoteId // Vincula a cobrança ao pacote para ativação posterior
            ];

            $this->cobrancaRepo->create($cobrancaRepoData);

            // 4. Pré-agendar as sessões na Agenda, se solicitado
            $preagendados = 0;
            if ($pacoteData['tipo'] === 'Serviços' && !empty($data['preagendar'])) {
                $sessoes = [];
                foreach ((array) ($data['itens'] ?? []) as $item) {
                    $sessoes[] = [
                        'servico_id' => $item['servico_id'] ?? null,
                        'quantidade' => (int) ($item['quantidade'] ?? 1),
                    ];
                }
                $preagendados = $this->gerarPreAgendamentos((int) $pacoteId, (int) $data['paciente_id'], $sessoes, (array) ($data['preagendamento'] ?? []));
                if ($preagendados === 0) {
                    throw new Exception('Nenhum item do pacote tem serviço do catálogo para pré-agendar.');
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->error('Erro ao processar criação do pacote.');
            }

            $msg = 'Pacote criado com sucesso. Aguardando pagamento da cobrança para ativação.';
            if ($preagendados > 0) {
                $msg = "Pacote criado com sucesso e $preagendados sessão(ões) pré-agendada(s) na Agenda. Aguardando pagamento da cobrança para ativação.";
            }

            return $this->success($msg, ['id' => $pacoteId, 'preagendados' => $preagendados]);

        } catch (Exception $e) {
            $db->transRollback();
            return $this->error('Erro: ' . $e->getMessage());
        }
    }

    /**
     * Pré-agenda na Agenda as sessões ainda não usadas nem agendadas de um
     * pacote de serviços já existente (ex.: pela tela de edição do pacote).
     *
     * Para cada item com serviço do catálogo, agenda
     * `quantidade_total - quantidade_usada - agendamentos pendentes do pacote`.
     *
     * @param int $pacoteId
     * @param array $opcoes data_inicial, periodicidade, hora, duracao, veterinario_id
     * @return array
     */
    public function preAgendar(int $pacoteId, array $opcoes): array
    {
        $pacote = $this->pacotesRepo->findById($pacoteId);
        if (!$pacote) {
            return $this->error('Pacote não encontrado.');
        }
        if ($pacote['tipo'] !== 'Serviços') {
            return $this->error('Só é possível pré-agendar sessões de pacotes de serviços.');
        }
        if (in_array($pacote['status'], ['Cancelado', 'Esgotado'], true)) {
            return $this->error("Não é possível pré-agendar sessões de um pacote {$pacote['status']}.");
        }

        $pendentes = $this->agendamentos()->countPendentesPorPacote($pacoteId);
        $sessoes = [];

        foreach ($this->itensRepo->getPorPacote($pacoteId) as $item) {
            $servicoId = (int) ($item['servico_id'] ?? 0);
            $restante = (int) $item['quantidade_total'] - (int) $item['quantidade_usada'];

            // Desconta as sessões já agendadas (o mesmo serviço pode estar em mais de um item)
            $jaAgendadas = min($restante, $pendentes[$servicoId] ?? 0);
            if ($servicoId > 0) {
                $pendentes[$servicoId] = ($pendentes[$servicoId] ?? 0) - $jaAgendadas;
            }

            $sessoes[] = ['servico_id' => $servicoId ?: null, 'quantidade' => $restante - $jaAgendadas];
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $total = $this->gerarPreAgendamentos($pacoteId, (int) $pacote['paciente_id'], $sessoes, $opcoes);

            if ($total === 0) {
                throw new Exception('Todas as sessões deste pacote já foram usadas ou já estão agendadas.');
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->error('Erro ao pré-agendar as sessões do pacote.');
            }

            return $this->success("$total sessão(ões) pré-agendada(s) na Agenda.", ['preagendados' => $total]);
        } catch (Exception $e) {
            $db->transRollback();
            return $this->error('Erro: ' . $e->getMessage());
        }
    }

    /**
     * Gera na Agenda os agendamentos das sessões de um pacote, vinculados a ele
     * (`pacote_id`) e ao serviço de cada item.
     *
     * Cada item com serviço do catálogo vira uma série com uma ocorrência por
     * sessão, espaçada pela periodicidade a partir da data inicial. Quando há
     * vários itens, eles ficam em horários consecutivos no mesmo dia (o 2º item
     * começa quando termina o 1º, e assim por diante), para não se sobreporem.
     * Itens de nome livre (sem serviço do catálogo) não são agendados.
     *
     * Deve ser chamado dentro de uma transação: em caso de erro, lança exceção
     * para que quem chamou desfaça tudo.
     *
     * @param int $pacoteId
     * @param int $pacienteId
     * @param array $sessoes Lista de ['servico_id' => int|null, 'quantidade' => int]
     * @param array $opcoes data_inicial, periodicidade, hora, duracao, veterinario_id
     * @return int Quantidade de agendamentos criados
     * @throws Exception
     */
    protected function gerarPreAgendamentos(int $pacoteId, int $pacienteId, array $sessoes, array $opcoes): int
    {
        $dataInicial   = (string) ($opcoes['data_inicial'] ?? '');
        $periodicidade = (string) ($opcoes['periodicidade'] ?? '');
        $hora          = substr((string) ($opcoes['hora'] ?? ''), 0, 5);
        $duracao       = (int) ($opcoes['duracao'] ?? 30);
        $veterinario   = !empty($opcoes['veterinario_id']) ? (int) $opcoes['veterinario_id'] : null;

        $inicio = \DateTimeImmutable::createFromFormat('!Y-m-d H:i', "$dataInicial $hora");
        if ($inicio === false || $inicio->format('Y-m-d H:i') !== "$dataInicial $hora") {
            throw new Exception('Informe a data inicial e o horário das sessões para pré-agendar.');
        }
        if (!in_array($periodicidade, self::PERIODICIDADES_PREAGENDAMENTO, true)) {
            throw new Exception('Periodicidade inválida para o pré-agendamento (use semanal ou mensal).');
        }
        if ($duracao < 1) {
            throw new Exception('Duração das sessões inválida.');
        }

        $total = 0;
        $horario = $inicio;

        foreach ($sessoes as $sessao) {
            $servicoId  = (int) ($sessao['servico_id'] ?? 0);
            $quantidade = (int) ($sessao['quantidade'] ?? 0);
            if ($servicoId < 1 || $quantidade < 1) {
                continue;
            }

            if ($horario->format('Y-m-d') !== $dataInicial) {
                throw new Exception('Os horários das sessões ultrapassam o fim do dia. Escolha um horário inicial mais cedo.');
            }

            $result = $this->agenda()->createSeriePorQuantidade([
                'paciente_id'     => $pacienteId,
                'pacote_id'       => $pacoteId,
                'age_data'        => $dataInicial,
                'age_hora'        => $horario->format('H:i:s'),
                'age_duracao'     => (string) $duracao,
                'age_status'      => 'pendente',
                'age_veterinario' => $veterinario,
                'age_recorrencia' => $periodicidade,
                'age_servico'     => [$servicoId],
                'age_obs'         => "Sessão pré-agendada do pacote #$pacoteId",
            ], $quantidade);

            if ($result['status'] !== 'success') {
                throw new Exception($result['message'] ?? 'Erro ao pré-agendar as sessões do pacote.');
            }

            $total += $quantidade;
            $horario = $horario->modify("+{$duracao} minutes");
        }

        return $total;
    }

    /**
     * Atualiza os dados de um pacote existente.
     * Os itens só podem ser alterados enquanto o pacote estiver "Pendente",
     * pois a partir da ativação eles passam a ter uso (quantidade_usada) registrado.
     *
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updatePacote(int $id, array $data): array
    {
        $pacote = $this->pacotesRepo->findById($id);
        if (!$pacote) {
            return $this->error('Pacote não encontrado.');
        }

        $allowedStatus = ['Pendente', 'Ativo', 'Esgotado', 'Cancelado'];

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $updateData = [];

            if (array_key_exists('nome', $data) && $data['nome'] !== null && $data['nome'] !== '') {
                $updateData['nome'] = $data['nome'];
            }
            if (array_key_exists('data_validade', $data)) {
                $updateData['data_validade'] = !empty($data['data_validade']) ? $data['data_validade'] : null;
            }
            if (array_key_exists('observacoes', $data)) {
                $updateData['observacoes'] = $data['observacoes'];
            }
            if (array_key_exists('status', $data) && in_array($data['status'], $allowedStatus, true)) {
                $updateData['status'] = $data['status'];
            }
            if ($pacote['tipo'] === 'Crédito' && array_key_exists('saldo_valor', $data)) {
                $updateData['saldo_valor'] = (float) $data['saldo_valor'];
            }

            if (!empty($updateData)) {
                $this->pacotesRepo->update($id, $updateData);
            }

            if ($pacote['tipo'] === 'Serviços' && array_key_exists('itens', $data)) {
                if ($pacote['status'] !== 'Pendente') {
                    throw new Exception('Não é possível alterar os itens de um pacote que já está ativo ou possui uso registrado.');
                }
                $this->itensRepo->sync($id, (array) $data['itens']);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->error('Erro ao atualizar o pacote.');
            }

            return $this->success('Pacote atualizado com sucesso.');
        } catch (Exception $e) {
            $db->transRollback();
            return $this->error('Erro: ' . $e->getMessage());
        }
    }

    /**
     * Exclui um pacote, desde que ele ainda não possua uso registrado.
     * Pacotes já utilizados devem ser cancelados (status "Cancelado") em vez
     * de excluídos, para preservar o histórico financeiro.
     *
     * @param int $id
     * @return array
     */
    public function deletePacote(int $id): array
    {
        $pacote = $this->pacotesRepo->findById($id);
        if (!$pacote) {
            return $this->error('Pacote não encontrado.');
        }

        if (!empty($this->usoRepo->getPorPacote($id))) {
            return $this->error('Não é possível excluir um pacote que já possui uso registrado. Cancele o pacote em vez de excluí-lo.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $this->itensRepo->deleteByPacote($id);
        $this->pacotesRepo->delete($id);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->error('Erro ao excluir o pacote.');
        }

        return $this->success('Pacote excluído com sucesso.');
    }

    /**
     * Ativa um pacote (chamado quando a cobrança vinculada é paga).
     *
     * @param int $pacoteId
     * @return bool
     */
    public function activatePacote(int $pacoteId): bool
    {
        return $this->pacotesRepo->getModel()->update($pacoteId, ['status' => 'Ativo']);
    }

    /**
     * Retorna os pacotes disponíveis para uso de um pet.
     * Otimizado para evitar N+1 queries ao buscar itens.
     *
     * @param int $petId
     * @return array
     */
    public function getDisponiveisPorPet(int $petId): array
    {
        $pacotes = $this->pacotesRepo->getAtivosPorPet($petId);
        
        if (empty($pacotes)) {
            return [];
        }

        // Identifica IDs de pacotes que possuem itens (Tipo Serviços)
        $pacoteIds = [];
        foreach ($pacotes as $p) {
            if ($p['tipo'] === 'Serviços') {
                $pacoteIds[] = (int) $p['id'];
            }
        }

        if (!empty($pacoteIds)) {
            $todosItens = $this->itensRepo->getPorPacotes($pacoteIds);

            // Agrupa itens por pacote_id
            $itensAgrupados = [];
            foreach ($todosItens as $item) {
                $itensAgrupados[$item['pacote_id']][] = $item;
            }

            // Associa os itens aos respectivos pacotes
            foreach ($pacotes as &$p) {
                if ($p['tipo'] === 'Serviços') {
                    $p['itens'] = $itensAgrupados[$p['id']] ?? [];
                }
            }
        }

        return $pacotes;
    }

    /**
     * Consome um item ou saldo do pacote.
     *
     * @param int $pacoteId
     * @param int|null $servicoId
     * @param float $valor
     * @return array
     */
    public function consumir(int $pacoteId, ?int $servicoId = null, float $valor = 0): array
    {
        $pacote = $this->pacotesRepo->findById($pacoteId);
        if (!$pacote || $pacote['status'] !== 'Ativo') {
            return $this->error('Pacote não encontrado ou inativo.');
        }

        if ($pacote['tipo'] === 'Crédito') {
            if ($pacote['saldo_valor'] < $valor) {
                return $this->error('Saldo insuficiente no pacote.');
            }
            $novoSaldo = (float)($pacote['saldo_valor'] - $valor);
            $this->pacotesRepo->getModel()->update($pacoteId, [
                'saldo_valor' => $novoSaldo,
                'status' => ($novoSaldo <= 0) ? 'Esgotado' : 'Ativo'
            ]);

            // Grava histórico
            $this->usoRepo->create([
                'pacote_id'        => $pacoteId,
                'servico_id'       => $servicoId,
                'quantidade'       => 0,
                'valor_descontado' => $valor,
                'data_uso'         => date('Y-m-d H:i:s'),
                'observacao'       => 'Consumo de crédito'
            ]);

            return $this->success('Consumo de crédito realizado.');
        } else {
            // Busca item correspondente ao serviço ou o primeiro disponível se generic
            $itens = $this->itensRepo->getPorPacote($pacoteId);
            $targetItem = null;

            foreach ($itens as $item) {
                if ((int)$item['servico_id'] === $servicoId && ($item['quantidade_total'] > $item['quantidade_usada'])) {
                    $targetItem = $item;
                    break;
                }
            }

            if (!$targetItem) {
                return $this->error('Serviço não disponível neste pacote ou esgotado.');
            }

            $novaQtdUsada = (int)($targetItem['quantidade_usada'] + 1);
            $this->itensRepo->getModel()->update($targetItem['id'], ['quantidade_usada' => $novaQtdUsada]);

            // Grava histórico
            $this->usoRepo->create([
                'pacote_id'        => $pacoteId,
                'servico_id'       => $servicoId,
                'quantidade'       => 1,
                'valor_descontado' => $targetItem['valor_unitario'],
                'data_uso'         => date('Y-m-d H:i:s'),
                'observacao'       => 'Consumo de serviço'
            ]);

            // Verifica se esgotou o pacote inteiro
            $this->checkEsgotado($pacoteId);

            return $this->success('Consumo de serviço realizado.');
        }
    }

    /**
     * Verifica se o pacote foi esgotado
     *
     * @param int $pacoteId
     * @return void
     */
    protected function checkEsgotado(int $pacoteId): void
    {
        $itens = $this->itensRepo->getPorPacote($pacoteId);
        $totalRestante = 0;
        foreach ($itens as $item) {
            $totalRestante += ($item['quantidade_total'] - $item['quantidade_usada']);
        }

        if ($totalRestante <= 0) {
            $this->pacotesRepo->getModel()->update($pacoteId, ['status' => 'Esgotado']);
        }
    }
}
