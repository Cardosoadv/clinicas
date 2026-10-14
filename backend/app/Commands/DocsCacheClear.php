<?php

namespace App\Commands;

use App\Services\Documentacao\CatalogoDocumentacao;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DocsCacheClear extends BaseCommand
{
    protected $group       = 'Documentação';
    protected $name        = 'docs:cache:clear';
    protected $description = 'Limpa o cache da documentação interna (catálogo e páginas renderizadas).';
    protected $usage       = 'docs:cache:clear';

    public function run(array $params)
    {
        CatalogoDocumentacao::limparCache();
        CLI::write('Cache da documentação limpo.', 'green');

        return EXIT_SUCCESS;
    }
}
