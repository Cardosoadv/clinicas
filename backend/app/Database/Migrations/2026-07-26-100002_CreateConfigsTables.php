<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateConfigsTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'meta_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'meta_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('meta_key');
        $this->forge->createTable('configs');
    }

    public function down()
    {
        $this->forge->dropTable('configs', true);
    }
}
