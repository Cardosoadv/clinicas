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

    /**
     * Busca todos os grupos de clientes duplicados por nome e telefone.
     */
    public function getDuplicados(): array
    {
        $db = \Config\Database::connect();
        
        $query = $db->query("
            SELECT nome, telefones, COUNT(*) as qtd
            FROM clientes
            WHERE deleted_at IS NULL
            GROUP BY nome, telefones
            HAVING qtd > 1
        ");
        
        $grupos = $query->getResultArray();
        $duplicados = [];
        
        foreach ($grupos as $grupo) {
            $clientes = $db->table('clientes')
                           ->where('nome', $grupo['nome'])
                           ->where('telefones', $grupo['telefones'])
                           ->where('deleted_at IS NULL')
                           ->orderBy('id', 'ASC')
                           ->get()
                           ->getResultArray();
                           
            $duplicados[] = [
                'nome'       => $grupo['nome'],
                'telefones'  => $grupo['telefones'],
                'quantidade' => $grupo['qtd'],
                'clientes'   => $clientes
            ];
        }
        
        return $duplicados;
    }

    /**
     * Realiza a mesclagem de clientes duplicados mantendo o menor ID.
     * Os campos vazios do menor ID recebem dados dos maiores IDs.
     * Referências nas tabelas vinculadas são atualizadas.
     */
    public function mergeDuplicados(array $ids): array
    {
        if (count($ids) < 2) {
            return $this->error('É necessário ao menos dois IDs para realizar o merge.');
        }

        $db = \Config\Database::connect();
        
        $clientes = $db->table('clientes')
                       ->whereIn('id', $ids)
                       ->where('deleted_at IS NULL')
                       ->orderBy('id', 'ASC')
                       ->get()
                       ->getResultArray();
                       
        if (count($clientes) < 2) {
            return $this->error('Os clientes informados não foram encontrados ou já foram mesclados/excluídos.');
        }
        
        $master = $clientes[0];
        $masterId = $master['id'];
        
        $updates = [];
        $idsToDelete = [];
        
        for ($i = 1; $i < count($clientes); $i++) {
            $dup = $clientes[$i];
            $idsToDelete[] = $dup['id'];
            
            foreach ($dup as $field => $value) {
                if (in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                    continue;
                }
                // Se no master estiver vazio/nulo, copiamos do duplicado (se tiver algo)
                if (empty($master[$field]) && !empty($value)) {
                    $master[$field] = $value;
                    $updates[$field] = $value;
                }
            }
        }
        
        $db->transStart();
        
        if (!empty($updates)) {
            $updates['updated_at'] = date('Y-m-d H:i:s');
            $db->table('clientes')->where('id', $masterId)->update($updates);
        }
        
        // Atualiza as chaves estrangeiras para apontar para o master
        $db->table('pacientes')->whereIn('cliente_id', $idsToDelete)->update(['cliente_id' => $masterId]);
        $db->table('cliente_notas')->whereIn('cliente_id', $idsToDelete)->update(['cliente_id' => $masterId]);
        $db->table('comunicacoes')->whereIn('cliente_id', $idsToDelete)->update(['cliente_id' => $masterId]);
        
        // Soft delete nos clientes duplicados
        $db->table('clientes')->whereIn('id', $idsToDelete)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        
        $db->transComplete();
        
        if ($db->transStatus() === false) {
            return $this->error('Ocorreu um erro ao realizar a mesclagem.');
        }
        
        return $this->success('Clientes mesclados com sucesso.');
    }
}
