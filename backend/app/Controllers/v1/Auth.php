<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Entities\User;

/**
 * Controller de autenticação para o frontend React.
 *
 * O LoginController padrão do Shield responde com redirects HTML (302),
 * o que não serve para um cliente JSON. Este controller replica a mesma
 * lógica de autenticação por sessão, mas sempre responde em JSON.
 */
class Auth extends BaseController
{
    /**
     * API: Autentica o usuário por email/senha e inicia a sessão (cookie).
     */
    public function login(): ResponseInterface
    {
        $data = $this->getRequestData();

        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        /** @var Session $authenticator */
        $authenticator = auth('session')->getAuthenticator();

        $remember = (bool) ($data['remember'] ?? false);

        $result = $authenticator->remember($remember)->attempt([
            'email'    => $data['email'],
            'password' => $data['password'],
        ]);

        if (!$result->isOK()) {
            return $this->apiError($result->reason() ?? 'E-mail ou senha inválidos.', 401);
        }

        return $this->apiResponse([
            'status'  => 'success',
            'message' => 'Login efetuado com sucesso.',
            'user'    => $this->serializeUser(auth()->user()),
        ]);
    }

    /**
     * API: Encerra a sessão do usuário autenticado.
     */
    public function logout(): ResponseInterface
    {
        auth()->logout();
        return $this->apiResponse(['status' => 'success', 'message' => 'Sessão encerrada.']);
    }

    /**
     * API: Retorna o usuário autenticado atual (ou null), usado pelo
     * frontend para restaurar o estado de sessão ao carregar a aplicação.
     */
    public function me(): ResponseInterface
    {
        if (!auth()->loggedIn()) {
            return $this->apiResponse(['status' => 'success', 'data' => null]);
        }

        return $this->apiResponse(['status' => 'success', 'data' => $this->serializeUser(auth()->user())]);
    }

    private function serializeUser(User $user): array
    {
        return [
            'id'       => $user->id,
            'username' => $user->username,
            'email'    => (string) $user->email,
        ];
    }
}
