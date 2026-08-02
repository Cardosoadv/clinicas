<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ClientesModel;

class ClientesRepository extends BaseRepository
{
    /**
     * @param ClientesModel|null $model
     */
    public function __construct(?ClientesModel $model = null)
    {
        $this->model = $model ?? new ClientesModel();
    }

    /**
     * Find a client by CPF, since it should be unique.
     *
     * @param string $cpf
     * @return array|null
     */
    public function findByCpf(string $cpf): ?array
    {
        return $this->model->where('cpf', $cpf)->first();
    }

    /**
     * Find a client by exact name and potentially phone.
     *
     * @param string $nome
     * @param string $tel
     * @return array|null
     */
    public function findByNameAndTel(string $nome, string $tel): ?array
    {
        return $this->model->where('nome', $nome)
                           ->where('telefones', $tel)
                           ->first();
    }

    /**
     * Find a client by exact name.
     *
     * @param string $nome
     * @return array|null
     */
    public function findByName(string $nome): ?array
    {
        return $this->model->where('nome', $nome)->first();
    }

    /**
     * Find a client by its export ID.
     *
     * @param string $exportId
     * @return array|null
     */
    public function findByExportacao(string $exportId): ?array
    {
        return $this->model->where('exportacao', $exportId)->first();
    }

    /**
     * Busca clientes com filtros de status (soft delete), tipo de pessoa e termo livre.
     *
     * @param string $status 'ativos' (padrão), 'inativos' ou 'todos'
     * @param string|null $tipo 'fisica' ou 'juridica'
     * @param string|null $term
     * @return array
     */
    public function getFiltered(string $status = 'ativos', ?string $tipo = null, ?string $term = null): array
    {
        $builder = $this->model;

        if ($status === 'inativos') {
            $builder = $builder->onlyDeleted();
        } elseif ($status === 'todos') {
            $builder = $builder->withDeleted();
        }
        // 'ativos' é o comportamento padrão do model (soft-deleted já ficam de fora)

        if ($tipo === 'fisica') {
            $builder = $builder->groupStart()->where('cnpj', null)->orWhere('cnpj', '')->groupEnd();
        } elseif ($tipo === 'juridica') {
            $builder = $builder->where('cnpj IS NOT NULL', null, false)->where('cnpj !=', '');
        }

        if (!empty($term)) {
            $builder = $builder->groupStart()
                ->like('nome', $term)
                ->orLike('razao_social', $term)
                ->orLike('cpf', $term)
                ->orLike('cnpj', $term)
                ->orLike('emails', $term)
            ->groupEnd();
        }

        return $builder->orderBy('nome', 'ASC')->findAll();
    }

    /**
     * Retorna os totalizadores usados nos cartões de estatística da tela de clientes.
     *
     * @return array{total: int, ativos: int, pessoa_fisica: int, pessoa_juridica: int}
     */
    public function getStats(): array
    {
        $total  = $this->model->withDeleted()->countAllResults();
        $ativos = $this->model->countAllResults();

        $pessoaFisica = $this->model->withDeleted()
            ->groupStart()->where('cnpj', null)->orWhere('cnpj', '')->groupEnd()
            ->countAllResults();

        $pessoaJuridica = $this->model->withDeleted()
            ->where('cnpj IS NOT NULL', null, false)->where('cnpj !=', '')
            ->countAllResults();

        return [
            'total'            => $total,
            'ativos'           => $ativos,
            'pessoa_fisica'    => $pessoaFisica,
            'pessoa_juridica'  => $pessoaJuridica,
        ];
    }

    /**
     * Busca os clientes cadastrados mais recentemente.
     *
     * @param int $limit
     * @return array
     */
    public function getRecent(int $limit = 5): array
    {
        return $this->model->orderBy('created_at', 'DESC')->findAll($limit);
    }

    /**
     * Busca os aniversariantes do mês.
     *
     * @param int $month
     * @return array
     */
    public function getBirthdaysByMonth(int $month): array
    {
        return $this->model
            ->where('MONTH(nascimento)', $month)
            ->orderBy('DAY(nascimento)', 'ASC')
            ->findAll();
    }
}
