<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLojasTables extends Migration
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
            'nome' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'cnpj' => [
                'type'       => 'VARCHAR',
                'constraint' => 14,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'telefone' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'endereco' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'cidade' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'estado' => [
                'type'       => 'VARCHAR',
                'constraint' => 2,
            ],
            'cep' => [
                'type'       => 'VARCHAR',
                'constraint' => 8,
            ],
            'logo' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'status' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('lojas');
    }

    public function down()
    {
        $this->forge->dropTable('lojas', true);
    }
}
