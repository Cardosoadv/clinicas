<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller responsável pelo perfil do usuário autenticado.
 */
class Perfil extends BaseController
{
    /**
     * API: Altera a senha do usuário logado.
     */
    public function alterarSenha(): ResponseInterface
    {
        $data = $this->getRequestData();

        $rules = [
            'current_password'     => 'required',
            'new_password'         => 'required|min_length[8]',
            'new_password_confirm' => 'required|matches[new_password]',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $user = auth()->user();

        $result = auth()->check([
            'email'    => $user->email,
            'password' => $data['current_password'],
        ]);

        if (!$result->isOK()) {
            return $this->apiError('A senha atual informada está incorreta.');
        }

        $user->password = $data['new_password'];
        $users = auth()->getProvider();

        if ($users->save($user)) {
            return $this->apiResponse(['status' => 'success', 'message' => 'Sua senha foi alterada com sucesso!']);
        }

        return $this->apiError('Ocorreu um erro ao tentar salvar a nova senha.', 500);
    }
}
