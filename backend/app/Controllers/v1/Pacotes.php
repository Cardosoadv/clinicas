<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Services\PacoteService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller responsável pelo módulo de pacotes de serviços e créditos.
 */
class Pacotes extends BaseController
{
    protected PacoteService $service;

    public function __construct(?PacoteService $service = null)
    {
        $this->service = $service ?? new PacoteService();
    }

    /**
     * API: Retorna a lista completa de pacotes com dados do paciente.
     */
    public function getAll(): ResponseInterface
    {
        $data = $this->service->getPacotesRepository()->getAllWithPet();
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Cria um novo pacote.
     */
    public function create(): ResponseInterface
    {
        $data = $this->getRequestData();

        // Decodifica JSON dos itens se vier como string (ex.: multipart/form-data)
        if (isset($data['itens_json'])) {
            $data['itens'] = json_decode((string) $data['itens_json'], true);
        }

        $result = $this->service->createPacote($data);
        return $this->apiResponse($result, 201);
    }

    /**
     * API: Exibe os detalhes de um pacote.
     */
    public function show(int $id): ResponseInterface
    {
        $pacote = $this->service->getPacotesRepository()->getWithDetails($id);

        if (!$pacote) {
            return $this->apiNotFound('Pacote não encontrado.');
        }

        return $this->apiResponse(['status' => 'success', 'data' => $pacote]);
    }

    /**
     * API: Retorna os pacotes disponíveis para uso de um paciente.
     */
    public function getDisponiveis(int $petId): ResponseInterface
    {
        $pacotes = $this->service->getDisponiveisPorPet($petId);
        return $this->apiResponse(['status' => 'success', 'data' => $pacotes]);
    }
}
