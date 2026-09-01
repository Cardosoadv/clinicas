<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Services\ReceitasImportService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller responsável pela importação de receitas via CSV (Sispet).
 */
class ReceitasImport extends BaseController
{
    protected ReceitasImportService $service;

    public function __construct(?ReceitasImportService $service = null)
    {
        $this->service = $service ?? new ReceitasImportService();
    }

    /**
     * API: Processa o upload do arquivo CSV de receitas.
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
