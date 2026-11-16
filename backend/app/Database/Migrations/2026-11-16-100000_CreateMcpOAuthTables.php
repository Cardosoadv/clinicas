<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabelas do Authorization Server OAuth 2.1 do endpoint /mcp:
 * clientes (Claude, Gemini...) registrados dinamicamente ou via `php spark mcp:cliente`
 * e códigos de autorização de uso único (somente o hash é guardado).
 */
class CreateMcpOAuthTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'client_id'                  => ['type' => 'VARCHAR', 'constraint' => 64],
            'client_secret_hash'         => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'client_name'                => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'redirect_uris'              => ['type' => 'TEXT'],
            'token_endpoint_auth_method' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'client_secret_post'],
            'created_at'                 => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('client_id');
        $this->forge->createTable('mcp_oauth_clients', true);

        $this->forge->addField([
            'id'                    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'code_hash'             => ['type' => 'VARCHAR', 'constraint' => 64],
            'client_id'             => ['type' => 'VARCHAR', 'constraint' => 64],
            'usuario_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'redirect_uri'          => ['type' => 'VARCHAR', 'constraint' => 500],
            'code_challenge'        => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'code_challenge_method' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'S256'],
            'scope'                 => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'mcp'],
            'expires_at'            => ['type' => 'DATETIME'],
            'used_at'               => ['type' => 'DATETIME', 'null' => true],
            'created_at'            => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code_hash');
        $this->forge->addKey('client_id');
        $this->forge->addKey('expires_at');
        $this->forge->createTable('mcp_oauth_codes', true);
    }

    public function down()
    {
        $this->forge->dropTable('mcp_oauth_codes', true);
        $this->forge->dropTable('mcp_oauth_clients', true);
    }
}
