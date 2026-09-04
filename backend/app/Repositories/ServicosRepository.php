<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ServicosModel;

class ServicosRepository extends BaseRepository
{
    /**
     * @param ServicosModel|null $model
     */
    public function __construct(?ServicosModel $model = null)
    {
        $this->model = $model ?? new ServicosModel();
    }

    /**
     * Busca todos os serviços ativos
     *
     * @return array
     */
    public function findAllActive(): array
    {
        return $this->model->where('ser_status', 'Ativo')->findAll();
    }

    /**
     * Retorna serviços aplicando filtros opcionais de busca e status.
     *
     * @param string|null $search
     * @param string|null $status
     * @return array
     */
    public function getFiltered(?string $search = null, ?string $status = null): array
    {
        $builder = $this->model;

        if ($status !== null && $status !== '' && $status !== 'todos') {
            $builder->where('ser_status', $status);
        }

        if ($search !== null && trim($search) !== '') {
            $term = trim($search);
            $builder->groupStart()
                ->like('ser_nome', $term)
                ->orLike('ser_descricao', $term)
            ->groupEnd();
        }

        return $builder->orderBy('ser_nome', 'ASC')->findAll();
    }
}
