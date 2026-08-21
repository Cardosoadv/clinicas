<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\LojasRepository;
use Exception;

class LojasService extends BaseService
{
    /**
     * @param LojasRepository|null $repository
     */
    public function __construct(?LojasRepository $repository = null)
    {
        $this->repository = $repository ?? new LojasRepository(null);
    }

    /**
     * Loja usada como identidade visual do sistema (nome, endereço, logo).
     */
    public function getPrincipal(): ?array
    {
        return $this->repository->findPrincipal();
    }

    /**
     * Cria uma loja e processa o upload da logo.
     * 
     * @param array $data
     * @param \CodeIgniter\HTTP\Files\UploadedFile|null $file
     * @return array
     */
    public function createWithLogo(array $data, $file = null): array
    {
        try {
            $db = \Config\Database::connect();
            $db->transStart();

            // 1. Cria o registro básico para obter o ID
            $id = $this->repository->create($data);

            if ($id && $file && $file->isValid() && !$file->hasMoved()) {
                // 2. Processa o upload da logo
                $newName = $file->getRandomName();
                $uploadPath = WRITEPATH . 'imagens/loja/' . $id;

                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                $file->move($uploadPath, $newName);

                // 3. Atualiza o registro com o nome do arquivo da logo
                $this->repository->update((int)$id, ['logo' => $newName]);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->error('Erro ao criar loja com logo');
            }

            return $this->success('Loja criada com sucesso', ['id' => $id]);
        } catch (Exception $e) {
            return $this->error('Erro interno: ' . $e->getMessage());
        }
    }

    /**
     * Atualiza uma loja e processa o upload de uma nova logo se fornecida.
     * 
     * @param int $id
     * @param array $data
     * @param \CodeIgniter\HTTP\Files\UploadedFile|null $file
     * @return array
     */
    public function updateWithLogo(int $id, array $data, $file = null): array
    {
        try {
            $db = \Config\Database::connect();
            $db->transStart();

            if ($file && $file->isValid() && !$file->hasMoved()) {
                // 1. Processa o upload da nova logo
                $newName = $file->getRandomName();
                $uploadPath = WRITEPATH . 'imagens/loja/' . $id;

                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                $file->move($uploadPath, $newName);
                $data['logo'] = $newName;
            }

            // 2. Atualiza os dados da loja
            $result = $this->repository->update($id, $data);

            $db->transComplete();

            if ($db->transStatus() === false || !$result) {
                return $this->error('Erro ao atualizar loja');
            }

            return $this->success('Loja atualizada com sucesso');
        } catch (Exception $e) {
            return $this->error('Erro interno: ' . $e->getMessage());
        }
    }
}
