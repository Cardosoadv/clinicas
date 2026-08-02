<?php

declare(strict_types=1);

namespace App\Services;

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

    /**
     * Injeta as dependências do serviço de pacotes.
     *
     * @param PacotesRepository|null $pacotesRepo
     * @param PacoteItensRepository|null $itensRepo
     * @param FatCobrancaRepository|null $cobrancaRepo
     * @param PacoteUsoRepository|null $usoRepo
     */
    public function __construct(
        ?PacotesRepository $pacotesRepo = null,
        ?PacoteItensRepository $itensRepo = null,
        ?FatCobrancaRepository $cobrancaRepo = null,
        ?PacoteUsoRepository $usoRepo = null
    ) {
        $this->pacotesRepo = $pacotesRepo ?? new PacotesRepository();
        $this->repository   = $this->pacotesRepo;
        $this->itensRepo    = $itensRepo    ?? new PacoteItensRepository();
        $this->cobrancaRepo = $cobrancaRepo ?? new FatCobrancaRepository();
        $this->usoRepo      = $usoRepo      ?? new PacoteUsoRepository();
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

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->error('Erro ao processar criação do pacote.');
            }

            return $this->success('Pacote criado com sucesso. Aguardando pagamento da cobrança para ativação.', ['id' => $pacoteId]);

        } catch (Exception $e) {
            $db->transRollback();
            return $this->error('Erro: ' . $e->getMessage());
        }
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
