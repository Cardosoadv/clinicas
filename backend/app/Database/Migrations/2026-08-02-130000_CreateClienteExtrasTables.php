<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClienteExtrasTables extends Migration
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
            'cliente_id' => [
                'type'     => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'texto' => [
                'type' => 'TEXT',
            ],
            'autor' => [
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
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('cliente_id', false, false, 'cliente_notas_cliente_id_foreign');
        $this->forge->addForeignKey('cliente_id', 'clientes', 'id', 'CASCADE', 'CASCADE', 'cliente_notas_cliente_id_foreign');
        $this->forge->createTable('cliente_notas');

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'cliente_id' => [
                'type'     => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'canal' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'whatsapp',
            ],
            'tipo' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'paciente_nome' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'mensagem' => [
                'type' => 'TEXT',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('cliente_id', false, false, 'comunicacoes_cliente_id_foreign');
        $this->forge->addForeignKey('cliente_id', 'clientes', 'id', 'CASCADE', 'CASCADE', 'comunicacoes_cliente_id_foreign');
        $this->forge->createTable('comunicacoes');
    }

    public function down()
    {
        $this->forge->dropTable('comunicacoes', true);
        $this->forge->dropTable('cliente_notas', true);
    }
}
