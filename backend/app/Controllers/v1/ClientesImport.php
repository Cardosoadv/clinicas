<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Services\ClientesImportService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller responsável pela importação de clientes via CSV.
 */
class ClientesImport extends BaseController
{
    protected ClientesImportService $service;

    public function __construct(?ClientesImportService $service = null)
    {
        $this->service = $service ?? new ClientesImportService();
    }

    /**
     * API: Processa o upload do arquivo CSV de clientes.
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
