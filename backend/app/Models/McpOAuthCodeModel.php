<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Códigos de autorização OAuth de uso único (guarda só o hash). */
class McpOAuthCodeModel extends Model
{
    protected $table         = 'mcp_oauth_codes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['code_hash', 'client_id', 'usuario_id', 'redirect_uri', 'code_challenge', 'code_challenge_method', 'scope', 'expires_at', 'used_at', 'created_at'];
    protected $useTimestamps = false;
}
