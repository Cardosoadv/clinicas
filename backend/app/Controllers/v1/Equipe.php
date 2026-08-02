<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Services\EquipeService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller responsável pela gestão dos membros da equipe e funcionários da clínica.
 */
class Equipe extends BaseController
{
    protected EquipeService $service;

    public function __construct(?EquipeService $service = null)
    {
        $this->service = $service ?? new EquipeService();
    }

    /**
     * API: Retorna a lista completa de membros da equipe.
     */
    public function getAll(): ResponseInterface
    {
        $data = $this->service->getAll();
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Retorna os usuários do sistema (Shield) que ainda não estão
     * vinculados a nenhum membro da equipe — usado para popular o formulário.
     */
    public function getUnlinkedUsers(): ResponseInterface
    {
        $equipe = $this->service->getAll();
        $equipeUserIds = array_filter(array_column($equipe, 'user_id'));

        $allUsers = auth()->getProvider()->findAll();
        $unlinked = array_values(array_filter($allUsers, static fn ($user) => !in_array($user->id, $equipeUserIds, true)));

        $data = array_map(static fn ($user) => [
            'id'       => $user->id,
            'username' => $user->username,
            'email'    => (string) $user->email,
        ], $unlinked);

        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Busca os detalhes de um membro da equipe pelo ID.
     */
    public function getById(int $id): ResponseInterface
    {
        $membro = $this->service->findById($id);
        if (!$membro) {
            return $this->apiNotFound('Membro da equipe não encontrado.');
        }
        return $this->apiResponse(['status' => 'success', 'data' => $membro]);
    }

    /**
     * API: Cria um novo registro de membro na equipe.
     */
    public function create(): ResponseInterface
    {
        $data = $this->getRequestData();
        $result = $this->service->create($data);
        return $this->apiResponse($result, 201);
    }

    /**
     * API: Atualiza um membro da equipe existente.
     */
    public function update(int $id): ResponseInterface
    {
        $data = $this->getRequestData();
        $result = $this->service->update($id, $data);
        return $this->apiResponse($result);
    }

    /**
     * API: Exclui um membro da equipe.
     */
    public function delete(int $id): ResponseInterface
    {
        $result = $this->service->delete($id);
        return $this->apiResponse($result);
    }
}
