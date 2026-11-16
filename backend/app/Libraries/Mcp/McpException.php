<?php

declare(strict_types=1);

namespace App\Libraries\Mcp;

use RuntimeException;

/** Erro de protocolo JSON-RPC (vira o objeto "error" da resposta). */
class McpException extends RuntimeException
{
}
