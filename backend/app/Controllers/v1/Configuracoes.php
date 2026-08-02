<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Repositories\ConfigRepository;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller responsável pelas configurações do sistema.
 */
class Configuracoes extends BaseController
{
    protected ConfigRepository $configRepo;

    public function __construct(?ConfigRepository $configRepo = null)
    {
        $this->configRepo = $configRepo ?? new ConfigRepository();
    }

    /**
     * API: Retorna todos os templates de mensagens (WhatsApp, etc).
     */
    public function getTemplates(): ResponseInterface
    {
        $templates = $this->configRepo->findLike('whatsapp_template');
        return $this->apiResponse(['status' => 'success', 'data' => $templates]);
    }

    /**
     * API: Atualiza um template de mensagem.
     */
    public function updateTemplate(): ResponseInterface
    {
        $data = $this->getRequestData();
        $key   = $data['meta_key'] ?? null;
        $value = $data['meta_value'] ?? null;

        $whitelist = [
            'whatsapp_template_agendamento',
            'whatsapp_template_cobranca',
            'whatsapp_template_pos_consulta',
            'whatsapp_template_aniversario',
            'whatsapp_template_vacina',
        ];

        if (empty($key) || !in_array($key, $whitelist, true)) {
            return $this->apiError('Chave de configuração não permitida ou inválida.');
        }

        if (!isset($value)) {
            return $this->apiError('O valor do template é obrigatório.');
        }

        if ($this->configRepo->setMetaValue($key, $value)) {
            return $this->apiResponse(['status' => 'success', 'message' => 'Template atualizado!']);
        }

        return $this->apiError('Erro ao salvar no banco.', 500);
    }
}
