<?php

declare(strict_types=1);
 
namespace App\Services;

use App\Repositories\EquipeRepository;
use CodeIgniter\Shield\Entities\User;
 
/**
 * @property EquipeRepository $repository
 */
class EquipeService extends BaseService
{
    /**
     * @param EquipeRepository|null $repository
     */
    public function __construct(?EquipeRepository $repository = null)
    {
        $this->repository = $repository ?? new EquipeRepository();
    }

    /**
     * Cria um membro da equipe e opcionalmente um usuário no Shield
     *
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $userId = null;

        // Se houver user_id, vincula o usuário existente
        if (!empty($data['user_id'])) {
            $userId = (int) $data['user_id'];
        }
        // Se não, mas houver e-mail e senha, cria o usuário no Shield
        elseif (!empty($data['email'])) {
            $users = auth()->getProvider();

            $user = new User([
                'username' => null,
                'email'    => $data['email'],
                'password' => $data['password'] ?? 'Petys@2026',
            ]);

            if (!$users->save($user)) {
                $db->transRollback();
                return $this->error('Erro ao criar usuário: ' . implode(', ', $users->errors()));
            }

            $userId = $users->getInsertID();
            $user = $users->find($userId);
            $user->addGroup('equipe');
        }

        $equipeData = [
            'user_id'           => $userId,
            'equ_pronome'       => $data['equ_pronome'] ?? null,
            'equ_nome'          => $data['equ_nome'],
            'equ_crmv'          => $data['equ_crmv'] ?? null,
            'equ_especialidade' => $data['equ_especialidade'] ?? null,
            'equ_is_veterinario'   => isset($data['equ_is_veterinario']) ? ($data['equ_is_veterinario'] ? 1 : 0) : 0,
            'equ_status'        => $data['equ_status'] ?? 'Ativo',
        ];

        $equipeId = $this->repository->create($equipeData);

        $db->transComplete();

        if ($db->transStatus() === false || !$equipeId) {
            return $this->error('Erro ao processar o cadastro da equipe');
        }

        return $this->success('Membro da equipe cadastrado com sucesso', ['id' => $equipeId]);
    }

    /**
     * Retorna o nome e o pronome de tratamento do usuário
     *
     * @param int $userId
     * @return array
     */
    public function getUserNameAndHonorific(int $userId): array
    {
        return $this->repository->getUserNameAndHonorific($userId);
    }
}
