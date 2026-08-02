<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filtro de autenticação para as rotas da API (api/v1/*).
 *
 * O SessionAuth padrão do Shield responde a requisições não autenticadas
 * com um redirect HTML para a tela de login, o que não serve para um
 * cliente JSON (frontend React). Este filtro faz a mesma checagem de
 * sessão do Shield, mas responde sempre com JSON + status HTTP corretos.
 */
class ApiAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authenticator = auth('session')->getAuthenticator();

        if (! $authenticator->loggedIn()) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Não autenticado.']);
        }

        $user = $authenticator->getUser();

        if ($user !== null && $user->isBanned()) {
            $authenticator->logout();

            return service('response')
                ->setStatusCode(403)
                ->setJSON(['status' => 'error', 'message' => $user->getBanMessage() ?? 'Usuário banido.']);
        }

        if ($user !== null && ! $user->isActivated()) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON(['status' => 'error', 'message' => 'Conta não ativada.']);
        }

        if (setting('Auth.recordActiveDate')) {
            $authenticator->recordActiveDate();
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
