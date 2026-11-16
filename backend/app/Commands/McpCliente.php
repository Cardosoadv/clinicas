<?php

namespace App\Commands;

use App\Controllers\OAuth;
use App\Libraries\Mcp\OAuthSupport;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class McpCliente extends BaseCommand
{
    protected $group       = 'MCP';
    protected $name        = 'mcp:cliente';
    protected $description = 'Cadastra um cliente OAuth do MCP (para conectores que pedem Client ID/Secret prontos, como o Gemini).';
    protected $usage       = 'mcp:cliente <nome> <redirect_uri> [none]';
    protected $arguments   = [
        'nome'         => 'Nome do cliente (ex.: Gemini).',
        'redirect_uri' => 'URL de retorno informada pelo conector.',
        'none'         => 'Opcional: cliente público (sem client_secret, PKCE obrigatório).',
    ];

    public function run(array $params)
    {
        $nome = $params[0] ?? null;
        $uri  = $params[1] ?? null;

        if (!$nome || !$uri || !OAuthSupport::redirectUriPermitida($uri)) {
            CLI::error('Uso: php spark mcp:cliente <nome> <redirect_uri https> [none]');

            return EXIT_USER_INPUT;
        }

        [$id, $secret] = OAuth::criarCliente($nome, [$uri], ($params[2] ?? '') === 'none' ? 'none' : 'client_secret_post');

        CLI::write('Client ID:     ' . $id, 'green');
        CLI::write('Client Secret: ' . ($secret ?? '(cliente público)'), 'green');
        CLI::write('Guarde o secret agora: só o hash fica no banco.', 'yellow');

        return EXIT_SUCCESS;
    }
}
