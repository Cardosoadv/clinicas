<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Services\PetsImportService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller responsável pela importação de pacientes (pets) via CSV.
 */
class PetsImport extends BaseController
{
    protected PetsImportService $service;

    public function __construct(?PetsImportService $service = null)
    {
        $this->service = $service ?? new PetsImportService();
    }

    /**
     * API: Processa o upload do arquivo CSV de pacientes.
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
