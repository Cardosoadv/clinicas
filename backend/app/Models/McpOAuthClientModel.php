<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Clientes OAuth do conector MCP (Claude, Gemini...). */
class McpOAuthClientModel extends Model
{
    protected $table         = 'mcp_oauth_clients';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['client_id', 'client_secret_hash', 'client_name', 'redirect_uris', 'token_endpoint_auth_method', 'created_at'];
    protected $useTimestamps = false;
}
