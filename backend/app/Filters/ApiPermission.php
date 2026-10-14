<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Exige uma permissão do Shield (`apipermission:documentacao.editar`).
 *
 * Mesma checagem do filtro `permission` do Shield, mas responde em JSON (403)
 * em vez de redirecionar, como o ApiAuth. Deve rodar depois do `apiauth`.
 * Com mais de uma permissão, basta ter uma delas.
 */
class ApiPermission implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = auth('session')->getAuthenticator()->getUser();

        if ($user === null) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Não autenticado.']);
        }

        foreach ((array) $arguments as $permissao) {
            if ($user->can($permissao)) {
                return null;
            }
        }

        return service('response')
            ->setStatusCode(403)
            ->setJSON(['status' => 'error', 'message' => 'Você não tem permissão para acessar este recurso.']);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
