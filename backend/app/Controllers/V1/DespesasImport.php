<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Services\DespesasImportService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller responsável pela importação de despesas via CSV (Sispet).
 */
class DespesasImport extends BaseController
{
    protected DespesasImportService $service;

    public function __construct(?DespesasImportService $service = null)
    {
        $this->service = $service ?? new DespesasImportService();
    }

    /**
     * API: Processa o upload do arquivo CSV de despesas.
     */
    public function upload(): ResponseInterface
    {
        $file = $this->request->getFile('csv_file');

        if (!$file || !$file->isValid()) {
            return $this->apiError('Arquivo inválido ou não enviado.');
        }

        $result = $this->service->importFromCsv($file->getTempName());
        return $this->apiResponse($result);
    }
}
