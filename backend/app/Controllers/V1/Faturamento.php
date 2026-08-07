<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Services\FatService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller responsável pelo módulo financeiro, faturamento e fluxo de caixa.
 */
class Faturamento extends BaseController
{
    protected FatService $service;

    public function __construct(?FatService $service = null)
    {
        $this->service = $service ?? new FatService();
    }

    /**
     * API: Retorna os KPIs e indicadores do dashboard financeiro.
     */
    public function dashboard(): ResponseInterface
    {
        $data = $this->service->getDashboardData();
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Retorna as listas completas de cobranças e despesas.
     */
    public function lancamentos(): ResponseInterface
    {
        $data = $this->service->getLancamentosData();
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Retorna todas as notas fiscais/recibos emitidos.
     */
    public function notas(): ResponseInterface
    {
        $data = $this->service->getNotas();
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Registra uma nova cobrança (receita) no sistema.
     */
    public function createCobranca(): ResponseInterface
    {
        $data = $this->getRequestData();

        if (!$this->validateData($data, [
            'paciente_id'  => 'required|is_natural_no_zero',
            'servico'      => 'required|max_length[255]',
            'valor'        => 'required|numeric',
            'data_servico' => 'required|valid_date',
            'vencimento'   => 'required|valid_date',
            'status'       => 'permit_empty|in_list[Pendente,Pago,Atrasado,Cancelado]',
        ])) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->createCobranca($data);
        return $this->apiResponse($result, 201);
    }

    /**
     * API: Atualiza os dados de uma cobrança existente.
     */
    public function updateCobranca(int $id): ResponseInterface
    {
        $data = $this->getRequestData();

        if (!$this->validateData($data, [
            'valor'  => 'permit_empty|numeric',
            'status' => 'permit_empty|in_list[Pendente,Pago,Atrasado,Cancelado]',
        ])) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->updateCobranca($id, $data);
        return $this->apiResponse($result);
    }

    /**
     * API: Registra uma nova despesa no sistema.
     */
    public function createDespesa(): ResponseInterface
    {
        $data = $this->getRequestData();

        if (!$this->validateData($data, [
            'descricao'       => 'required|max_length[255]',
            'categoria'       => 'required|max_length[100]',
            'valor'           => 'required|numeric',
            'data_vencimento' => 'required|valid_date',
            'status'          => 'permit_empty|in_list[Pendente,Pago,Cancelado]',
        ])) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->createDespesa($data);
        return $this->apiResponse($result, 201);
    }

    /**
     * API: Atualiza os dados de uma despesa existente.
     */
    public function updateDespesa(int $id): ResponseInterface
    {
        $data = $this->getRequestData();

        if (!$this->validateData($data, [
            'valor'  => 'permit_empty|numeric',
            'status' => 'permit_empty|in_list[Pendente,Pago,Cancelado]',
        ])) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->updateDespesa($id, $data);
        return $this->apiResponse($result);
    }

    /**
     * API: Faz o upload de comprovante para a despesa.
     * Armazena em writable/despesas/{id}/
     */
    public function uploadComprovantesDespesa(int $id): ResponseInterface
    {
        $file = $this->request->getFile('comprovante');
        $result = $this->service->uploadComprovante($id, $file);
        return $this->apiResponse($result, 201);
    }

    /**
     * API: Serve o arquivo comprovante de despesa (acesso seguro fora do public/).
     */
    public function viewComprovante(int $id, string $filename): ResponseInterface
    {
        $filename = basename($filename);
        $path = WRITEPATH . 'despesas/' . $id . '/' . $filename;
        if (!is_file($path)) {
            return $this->apiNotFound('Comprovante não encontrado.');
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody(file_get_contents($path));
    }

    /**
     * API: Gera uma nova nota fiscal ou registro de faturamento.
     */
    public function createNota(): ResponseInterface
    {
        $data = $this->getRequestData();

        if (!$this->validateData($data, [
            'paciente_id'  => 'required|is_natural_no_zero',
            'tipo'         => 'required|in_list[Recibo,NFS-e]',
            'data_emissao' => 'required|valid_date',
            'descricao'    => 'required',
            'valor'        => 'required|numeric',
        ])) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->createNota($data);
        return $this->apiResponse($result, 201);
    }
}
