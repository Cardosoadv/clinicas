<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AgendamentosRepository;
use App\Repositories\ComunicacaoRepository;
use App\Repositories\FatCobrancaRepository;
use App\Repositories\PetsRepository;
use App\Repositories\ConfigRepository;
use App\Services\LojasService;
use Exception;

/**
 * Serviço responsável por gerar links de comunicação (ex: WhatsApp).
 */
class ComunicacaoService extends BaseService
{
    /** @var AgendamentosRepository */
    protected AgendamentosRepository $ageRepo;

    /** @var FatCobrancaRepository */
    protected FatCobrancaRepository $fatRepo;

    /** @var PetsRepository */
    protected PetsRepository $petsRepo;

    /** @var ConfigRepository */
    protected ConfigRepository $configRepo;

    /** @var ComunicacaoRepository */
    protected ComunicacaoRepository $comunicacaoRepo;

    /** @var LojasService */
    protected LojasService $lojasService;

    /**
     * Injeta as dependências.
     *
     * @param AgendamentosRepository|null $ageRepo
     * @param FatCobrancaRepository|null $fatRepo
     * @param PetsRepository|null $petsRepo
     * @param ConfigRepository|null $configRepo
     * @param ComunicacaoRepository|null $comunicacaoRepo
     * @param LojasService|null $lojasService
     */
    public function __construct(
        ?AgendamentosRepository $ageRepo = null,
        ?FatCobrancaRepository $fatRepo = null,
        ?PetsRepository $petsRepo = null,
        ?ConfigRepository $configRepo = null,
        ?ComunicacaoRepository $comunicacaoRepo = null,
        ?LojasService $lojasService = null
    ) {
        $this->ageRepo = $ageRepo ?? new AgendamentosRepository();
        $this->fatRepo = $fatRepo ?? new FatCobrancaRepository();
        $this->petsRepo = $petsRepo ?? new PetsRepository();
        $this->configRepo = $configRepo ?? new ConfigRepository();
        $this->comunicacaoRepo = $comunicacaoRepo ?? new ComunicacaoRepository();
        $this->lojasService = $lojasService ?? new LojasService();
    }

    /**
     * Nome da clínica exibido nas mensagens: vem da loja principal cadastrada
     * (Configurações > Lojas); cai para o .env/valor genérico se nenhuma loja
     * estiver cadastrada ainda.
     */
    private function nomeClinica(): string
    {
        $loja = $this->lojasService->getPrincipal();
        return (string) ($loja['nome'] ?? (getenv('NomeClinica') ?: 'Petys'));
    }

    /**
     * Registra no histórico uma comunicação gerada automaticamente.
     */
    private function logComunicacao(?int $clienteId, string $tipo, ?string $pacienteNome, string $mensagem): void
    {
        if (!$clienteId) {
            return;
        }

        $this->comunicacaoRepo->create([
            'cliente_id'    => $clienteId,
            'canal'         => 'whatsapp',
            'tipo'          => $tipo,
            'paciente_nome' => $pacienteNome,
            'mensagem'      => $mensagem,
        ]);
    }

    /**
     * Gera o link do WhatsApp para um agendamento.
     *
     * @param int $id
     * @return string
     * @throws Exception
     */
    public function getWhatsAppLinkAgendamento(int $id): string
    {
        $appt = $this->ageRepo->findWithTutor($id);

        if (!$appt) {
            throw new Exception("Agendamento não encontrado.");
        }

        $template = $this->configRepo->getMetaValue('whatsapp_template_agendamento', 'Olá {tutor}, passando para lembrar do agendamento de {pet} para {servico} em {data} às {hora}. Podemos confirmar? 🐾');

        $vars = [
            '{tutor}'   => explode(' ', $appt['tutor_nome'])[0], // Primeiro nome
            '{pet}'     => $appt['paciente_nome'],
            '{servico}' => $appt['age_servico'],
            '{data}'    => date('d/m/Y', strtotime($appt['age_data'])),
            '{hora}'    => substr($appt['age_hora'], 0, 5),
            '{clinica}' => $this->nomeClinica(),
        ];

        $message = strtr((string) $template, $vars);
        $phone = $this->parsePhone($appt['telefones']);

        $this->logComunicacao($appt['cliente_id'] ?? null, 'agendamento', $appt['paciente_nome'], $message);

        return "https://wa.me/{$phone}/?text=" . urlencode($message);
    }

    /**
     * Gera o link do WhatsApp para uma cobrança.
     *
     * @param int $id
     * @return string
     * @throws Exception
     */
    public function getWhatsAppLinkCobranca(int $id): string
    {
        $cobranca = $this->fatRepo->findWithTutor($id);

        if (!$cobranca) {
            throw new Exception("Cobrança não encontrada.");
        }

        $template = $this->configRepo->getMetaValue('whatsapp_template_cobranca', 'Olá {tutor}, informamos que consta uma pendência de {pet} no valor de R$ {valor} (vencimento em {data}). Favor regularizar. Obrigado! 💰');

        $vars = [
            '{tutor}'   => explode(' ', $cobranca['tutor_nome'])[0],
            '{pet}'     => $cobranca['paciente_nome'],
            '{valor}'   => number_format((float)$cobranca['valor'], 2, ',', '.'),
            '{data}'    => date('d/m/Y', strtotime($cobranca['vencimento'])),
            '{clinica}' => $this->nomeClinica(),
        ];

        $message = strtr((string) $template, $vars);
        $phone = $this->parsePhone($cobranca['telefones']);

        $this->logComunicacao($cobranca['cliente_id'] ?? null, 'cobranca', $cobranca['paciente_nome'], $message);

        return "https://wa.me/{$phone}/?text=" . urlencode($message);
    }

    /**
     * Gera o link do WhatsApp para um pós-consulta (acompanhamento).
     *
     * @param int $id
     * @return string
     * @throws Exception
     */
    public function getWhatsAppLinkPosConsulta(int $id): string
    {
        $appt = $this->ageRepo->findWithTutor($id);

        if (!$appt) {
            throw new Exception("Agendamento não encontrado.");
        }

        $template = $this->configRepo->getMetaValue('whatsapp_template_pos_consulta', 'Olá {tutor}, como está o {pet} após o procedimento de {servico}? Esperamos que esteja se recuperando bem! Qualquer dúvida, estamos à disposição. 🐾');

        $vars = [
            '{tutor}'   => explode(' ', $appt['tutor_nome'])[0],
            '{pet}'     => $appt['paciente_nome'],
            '{servico}' => $appt['age_servico'],
            '{clinica}' => $this->nomeClinica(),
        ];

        $message = strtr((string) $template, $vars);
        $phone = $this->parsePhone($appt['telefones']);

        $this->logComunicacao($appt['cliente_id'] ?? null, 'pos_consulta', $appt['paciente_nome'], $message);

        return "https://wa.me/{$phone}/?text=" . urlencode($message);
    }

    /**
     * Gera o link do WhatsApp para lembrete de vacina.
     */
    public function getWhatsAppLinkVacina(int $id): string
    {
        $db = \Config\Database::connect();
        $vacina = $db->table('vacinas')
            ->select('vacinas.*, pacientes.paciente_nome, pacientes.cliente_id, clientes.nome as tutor_nome, clientes.telefones')
            ->join('pacientes', 'pacientes.paciente_id = vacinas.paciente_id')
            ->join('clientes', 'clientes.id = pacientes.cliente_id')
            ->where('vacinas.id', $id)
            ->get()
            ->getRowArray();

        if (!$vacina) {
            throw new Exception("Vacina não encontrada.");
        }

        $template = $this->configRepo->getMetaValue('whatsapp_template_vacina', 'Olá {tutor}, passando para lembrar que o reforço da vacina {vacina} de {pet} está agendado para {data}. Vamos garantir a proteção do seu amiguinho? 💉🐾');

        $vars = [
            '{tutor}'   => explode(' ', $vacina['tutor_nome'])[0],
            '{pet}'     => $vacina['paciente_nome'],
            '{vacina}'  => $vacina['vacina_nome'],
            '{data}'    => date('d/m/Y', strtotime($vacina['data_proxima_dose'])),
            '{clinica}' => $this->nomeClinica(),
        ];

        $message = strtr($template, $vars);
        $phone = $this->parsePhone($vacina['telefones']);

        $this->logComunicacao($vacina['cliente_id'] ?? null, 'vacina', $vacina['paciente_nome'], $message);

        return "https://wa.me/{$phone}/?text=" . urlencode($message);
    }

    /**
     * Gera o link do WhatsApp para desejar feliz aniversário ao pet.
     *
     * @param int $id
     * @return string
     * @throws Exception
     */
    public function getWhatsAppLinkAniversario(int $id): string
    {
        $pet = $this->petsRepo->findById($id);

        if (!$pet) {
            throw new Exception("Pet não encontrado.");
        }

        $template = $this->configRepo->getMetaValue('whatsapp_template_aniversario', 'Olá {tutor}, hoje é um dia especial! Queremos desejar um feliz aniversário para o(a) {pet}! Muita saúde e petiscos! 🎂🎉 🐾');

        $vars = [
            '{tutor}'   => explode(' ', $pet['pet_resp_nome'])[0],
            '{pet}'     => $pet['paciente_nome'],
            '{clinica}' => $this->nomeClinica(),
        ];

        $message = strtr((string) $template, $vars);
        $phone = $this->parsePhone($pet['pet_resp_tel']);

        $this->logComunicacao($pet['cliente_id'] ?? null, 'aniversario', $pet['paciente_nome'], $message);

        return "https://wa.me/{$phone}/?text=" . urlencode($message);
    }

    /**
     * Extrai o primeiro número de telefone válido para o WhatsApp.
     *
     * @param string|null $telefones
     * @return string
     */
    private function parsePhone(?string $telefones): string
    {
        if (empty($telefones)) {
            return '';
        }

        // Se for uma string separada por vírgula ou similar
        $parts = preg_split('/[,;|]/', $telefones);
        if ($parts === false) {
            return '';
        }

        foreach ($parts as $p) {
            $cleaned = preg_replace('/\D/', '', (string) $p);
            
            // Adicionar 55 se não tiver DDI (Brasil)
            if (strlen((string) $cleaned) === 11 || strlen((string) $cleaned) === 10) {
                $cleaned = '55' . $cleaned;
            }
            
            if (strlen((string) $cleaned) >= 12) {
                return (string) $cleaned;
            }
        }

        return (string) preg_replace('/\D/', '', (string) $parts[0]);
    }
}
