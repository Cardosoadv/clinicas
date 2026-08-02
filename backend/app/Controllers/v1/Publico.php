<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Models\FatCobrancaModel;
use App\Models\FatNotaModel;
use App\Models\PetsModel;
use App\Services\FatService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller responsável por endpoints públicos acessados via link/QR code
 * (não requerem sessão autenticada — protegidos por hash na própria URL).
 */
class Publico extends BaseController
{
    /**
     * API: Retorna os dados de um recibo público, validado por hash.
     */
    public function recibo(int $id, string $hash): ResponseInterface
    {
        $notaModel = new FatNotaModel();
        $nota = $notaModel->find($id);

        if (!$nota || $nota['hash'] !== $hash) {
            return $this->apiNotFound('Recibo não encontrado ou acesso inválido.');
        }

        $cobrancaModel = new FatCobrancaModel();
        $cobranca = $cobrancaModel->find($nota['cobranca_id']);

        $petsModel = new PetsModel();
        $pet = null;
        $petId = $nota['paciente_id'] ?? $cobranca['paciente_id'] ?? null;

        if ($petId) {
            $pet = $petsModel->find($petId);
        }

        $tutor = null;
        if ($pet && !empty($pet['cliente_id'])) {
            $db = \Config\Database::connect();
            $tutor = $db->table('clientes')->where('id', $pet['cliente_id'])->get()->getRowArray();
        }

        $fatService = new FatService();
        $qrCodeData = base_url("api/v1/publico/recibos/{$id}/{$hash}");

        $data = [
            'nota'            => $nota,
            'cobranca'        => $cobranca,
            'paciente'        => $pet,
            'tutor_nome'      => $tutor['nome'] ?? 'Não informado',
            'tutor_telefone'  => $tutor['telefones'] ?? 'Não informado',
            'qr_code_base64'  => $fatService->generateQR($qrCodeData),
        ];

        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }
}
