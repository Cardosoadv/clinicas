<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EquipeModel;

class EquipeRepository extends BaseRepository
{
    /**
     * @param EquipeModel|null $model
     */
    public function __construct(?EquipeModel $model = null)
    {
        $this->model = $model ?? new EquipeModel();
    }

    /**
     * Busca um membro da equipe pelo ID do usuário Shield
     *
     * @param int $userId
     * @return array|null
     */
    public function findByUserId(int $userId): ?array
    {
        return $this->model->where('user_id', $userId)->first();
    }

    /**
     * Retorna o nome e o pronome de tratamento do usuário
     *
     * @param int $userId
     * @return array
     */
    public function getUserNameAndHonorific(int $userId): array
    {
        $user = $this->findByUserId($userId);
        $honorific = $user['equ_pronome'] ?? '';
        $name = $user['equ_nome'] ?? 'Colaborador';
        return [
            'honorific' => $honorific,
            'name'      => $name
        ];
    }
}
