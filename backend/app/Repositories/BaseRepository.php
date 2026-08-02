<?php

declare(strict_types=1);

namespace App\Repositories;

use CodeIgniter\Model;

/**
 * Classe base para repositórios
 * 
 * @property Model $model
 * @method findAll(int $limit = 0, int $offset = 0): array
 * @method findById(int $id): ?array
 * @method create(array $data): int
 * @method update(int $id, array $data): bool
 * @method delete(int $id): bool
 */
abstract class BaseRepository
{
    protected Model $model;

    /**
     * Busca todos os registros
     * 
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findAll(int $limit = 0, int $offset = 0): array
    {
        return $this->model->findAll($limit, $offset);
    }

    /**
     * Busca um registro por ID
     * 
     * @param int $id
     * @return ?array
     */
    public function findById(int $id): ?array
    {
        return $this->model->find($id);
    }

    /**
     * Cria um registro
     * 
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        return (int) $this->model->insert($data);
    }

    /**
     * Atualiza um registro
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        return $this->model->update($id, $data);
    }

    /**
     * Deleta um registro
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->model->delete($id);
    }

    /**
     * Pagina os resultados
     * 
     * @param int $perPage
     * @return array
     */
    public function paginate(int $perPage = 20): array
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Retorna o pager
     * 
     * @return object
     */
    public function getPager()
    {
        return $this->model->pager;
    }

    /**
     * Retorna o modelo subjacente
     * 
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }
}
