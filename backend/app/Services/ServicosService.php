<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ServicosRepository;

/**
 * Serviço responsável pela gestão de serviços oferecidos pela clínica.
 *
 * @property ServicosRepository $repository
 */
class ServicosService extends BaseService
{
    protected \App\Repositories\ServicoProdutosRepository $servicoProdutosRepo;

    /**
     * Injeta o repositório de serviços.
     *
     * @param ServicosRepository|null $repository
     * @param \App\Repositories\ServicoProdutosRepository|null $servicoProdutosRepo
     */
    public function __construct(
        ?ServicosRepository $repository = null,
        ?\App\Repositories\ServicoProdutosRepository $servicoProdutosRepo = null
    ) {
        $this->repository = $repository ?? new ServicosRepository();
        $this->servicoProdutosRepo = $servicoProdutosRepo ?? new \App\Repositories\ServicoProdutosRepository();
    }

    /**
     * Retorna todos os serviços ativos.
     *
     * @return array
     */
    public function getAllActive(): array
    {
        /** @var ServicosRepository $repo */
        $repo = $this->repository;
        return $repo->findAllActive();
    }

    /**
     * Retorna a lista de serviços com suporte a busca e filtros.
     *
     * @param string|null $search
     * @param string|null $status
     * @return array
     */
    public function getAllFiltered(?string $search = null, ?string $status = null): array
    {
        /** @var ServicosRepository $repo */
        $repo = $this->repository;
        return $repo->getFiltered($search, $status);
    }

    /**
     * Retorna os detalhes de um serviço, incluindo os produtos vinculados.
     *
     * @param int $id
     * @return array|null
     */
    public function getServiceWithProducts(int $id): ?array
    {
        $servico = $this->repository->findById($id);
        if ($servico) {
            $servico['produtos'] = $this->servicoProdutosRepo->findByServico($id);
        }
        return $servico;
    }

    /**
     * Salva as associações de produtos de um serviço.
     *
     * @param int $id
     * @param array $produtos
     * @return array
     */
    public function syncProducts(int $id, array $produtos): array
    {
        try {
            $this->servicoProdutosRepo->sync($id, $produtos);
            return $this->success('Produtos vinculados com sucesso.');
        } catch (\Exception $e) {
            return $this->error('Erro ao vincular produtos: ' . $e->getMessage());
        }
    }
}
