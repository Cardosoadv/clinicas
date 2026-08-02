<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Services\ComunicacaoService;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

/**
 * Controller responsável por gerar links de comunicação (ex: WhatsApp) com clientes.
 * Retorna sempre o link em JSON — quem decide abrir/redirecionar é o frontend React.
 */
class Comunicacao extends BaseController
{
    protected ComunicacaoService $comService;

    public function __construct(?ComunicacaoService $comService = null)
    {
        $this->comService = $comService ?? new ComunicacaoService();
    }

    /**
     * API: Gera o link do WhatsApp para lembrete de agendamento.
     */
    public function getWhatsappAgendamento(int $id): ResponseInterface
    {
        return $this->whatsappLink(fn () => $this->comService->getWhatsAppLinkAgendamento($id));
    }

    /**
     * API: Gera o link do WhatsApp para cobrança pendente.
     */
    public function getWhatsappCobranca(int $id): ResponseInterface
    {
        return $this->whatsappLink(fn () => $this->comService->getWhatsAppLinkCobranca($id));
    }

    /**
     * API: Gera o link do WhatsApp para acompanhamento pós-consulta.
     */
    public function getWhatsappPosConsulta(int $id): ResponseInterface
    {
        return $this->whatsappLink(fn () => $this->comService->getWhatsAppLinkPosConsulta($id));
    }

    /**
     * API: Gera o link do WhatsApp para lembrete de vacina.
     */
    public function getWhatsappVacina(int $id): ResponseInterface
    {
        return $this->whatsappLink(fn () => $this->comService->getWhatsAppLinkVacina($id));
    }

    /**
     * API: Gera o link do WhatsApp para felicitação de aniversário do paciente.
     */
    public function getWhatsappAniversario(int $id): ResponseInterface
    {
        return $this->whatsappLink(fn () => $this->comService->getWhatsAppLinkAniversario($id));
    }

    /**
     * Executa o callback do serviço e padroniza a resposta {status, link}/{status, message}.
     */
    private function whatsappLink(callable $callback): ResponseInterface
    {
        try {
            $link = $callback();
            return $this->apiResponse(['status' => 'success', 'link' => $link]);
        } catch (Exception $e) {
            return $this->apiNotFound($e->getMessage());
        }
    }
}
