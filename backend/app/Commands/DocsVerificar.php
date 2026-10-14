<?php

namespace App\Commands;

use App\Services\Documentacao\VerificadorDocumentacao;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DocsVerificar extends BaseCommand
{
    protected $group       = 'Documentação';
    protected $name        = 'docs:verificar';
    protected $description = 'Confere as páginas da documentação interna (cabeçalhos, links, âncoras, imagens e telas). Sai com erro se houver problema.';
    protected $usage       = 'docs:verificar [--raiz <pasta>]';
    protected $options     = ['--raiz' => 'Pasta das páginas (padrão: app/Docs).'];

    public function run(array $params)
    {
        $raiz = $params['raiz'] ?? CLI::getOption('raiz');
        $problemas = (new VerificadorDocumentacao(is_string($raiz) && $raiz !== '' ? $raiz : null))->verificar();

        if ($problemas === []) {
            CLI::write('Documentação sem problemas.', 'green');

            return EXIT_SUCCESS;
        }

        $total = 0;
        foreach ($problemas as $arquivo => $lista) {
            CLI::write($arquivo, 'yellow');
            foreach ($lista as $problema) {
                CLI::write("  - {$problema}");
                $total++;
            }
        }
        CLI::error("{$total} problema(s) em " . count($problemas) . ' arquivo(s).');

        return EXIT_ERROR;
    }
}
