<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ClienteNotaRepository;
use App\Repositories\ClientesRepository;
use App\Repositories\ComunicacaoRepository;
use App\Repositories\PetsRepository;

class ClientesService extends BaseService
{
    protected ClientesRepository $clientesRepo;
    protected PetsRepository $petsRepo;
    protected ClienteNotaRepository $notaRepo;
    protected ComunicacaoRepository $comunicacaoRepo;

    public function __construct(
        ?ClientesRepository $repository = null,
        ?PetsRepository $petsRepo = null,
        ?ClienteNotaRepository $notaRepo = null,
        ?ComunicacaoRepository $comunicacaoRepo = null
    ) {
        $this->clientesRepo    = $repository ?? new ClientesRepository();
        $this->repository      = $this->clientesRepo;
        $this->petsRepo        = $petsRepo ?? new PetsRepository();
        $this->notaRepo        = $notaRepo ?? new ClienteNotaRepository();
        $this->comunicacaoRepo = $comunicacaoRepo ?? new ComunicacaoRepository();
    }

    /**
     * Lista os pacientes vinculados a um cliente.
     */
    public function getPacientes(int $clienteId): array
    {
        return $this->petsRepo->findByCliente($clienteId);
    }

    /**
     * Lista as notas internas registradas sobre um cliente.
     */
    public function getNotas(int $clienteId): array
    {
        return $this->notaRepo->findByCliente($clienteId);
    }

    /**
     * Registra uma nova nota sobre um cliente.
     */
    public function addNota(int $clienteId, string $texto, ?string $autor): array
    {
        $id = $this->notaRepo->create([
            'cliente_id' => $clienteId,
            'texto'      => $texto,
            'autor'      => $autor,
        ]);

        if (!$id) {
            return $this->error('Erro ao salvar a nota.');
        }

        return $this->success('Nota adicionada com sucesso.', ['id' => $id]);
    }

    /**
     * Remove uma nota de um cliente.
     */
    public function deleteNota(int $clienteId, int $notaId): array
    {
        $nota = $this->notaRepo->findById($notaId);
        if (!$nota || (int) $nota['cliente_id'] !== $clienteId) {
            return $this->error('Nota não encontrada.');
        }

        if (!$this->notaRepo->delete($notaId)) {
            return $this->error('Erro ao excluir a nota.');
        }

        return $this->success('Nota removida com sucesso.');
    }

    /**
     * Lista o histórico de comunicações (ex: WhatsApp) enviadas a um cliente.
     */
    public function getComunicacoes(int $clienteId): array
    {
        return $this->comunicacaoRepo->findByCliente($clienteId);
    }

    /**
     * Registra uma comunicação enviada manualmente a um cliente e devolve o
     * link do WhatsApp para o envio.
     */
    public function enviarComunicacao(int $clienteId, string $mensagem): array
    {
        $cliente = $this->clientesRepo->findById($clienteId);
        if (!$cliente) {
            return $this->error('Cliente não encontrado.');
        }

        $this->comunicacaoRepo->create([
            'cliente_id' => $clienteId,
            'canal'      => 'whatsapp',
            'tipo'       => 'manual',
            'mensagem'   => $mensagem,
        ]);

        $phone = preg_replace('/\D/', '', (string) $cliente['telefones']);
        if ($phone !== null && $phone !== '' && (strlen($phone) === 10 || strlen($phone) === 11)) {
            $phone = '55' . $phone;
        }

        return $this->success('Comunicação registrada com sucesso.', [
            'link' => 'https://wa.me/' . $phone . '/?text=' . urlencode($mensagem),
        ]);
    }

    /**
     * Lista clientes aplicando os filtros da tela (status, tipo de pessoa, busca).
     */
    public function search(string $status, ?string $tipo, ?string $term): array
    {
        return $this->clientesRepo->getFiltered($status, $tipo, $term);
    }

    /**
     * Dados do painel lateral da tela de clientes: estatísticas, adições
     * recentes e aniversariantes do mês.
     */
    public function getPainelData(): array
    {
        return [
            'stats'           => $this->clientesRepo->getStats(),
            'recentes'        => $this->clientesRepo->getRecent(5),
            'aniversariantes' => $this->clientesRepo->getBirthdaysByMonth((int) date('m')),
        ];
    }
}
