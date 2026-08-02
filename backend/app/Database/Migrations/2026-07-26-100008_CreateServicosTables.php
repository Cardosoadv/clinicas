<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServicosTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ser_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ser_nome' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'ser_icone' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'ser_valor' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],
            'ser_tempo_estimado' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'Tempo estimado em minutos',
            ],
            'pop_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'ser_descricao' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ser_status' => [
                'type'       => 'ENUM',
                'constraint' => ['Ativo', 'Inativo'],
                'default'    => 'Ativo',
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
        $this->forge->addKey('ser_id', true);
        $this->forge->createTable('servicos');

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ser_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'produto_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'quantidade' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 1.00,
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
        $this->forge->addKey('ser_id', false, false, 'servico_produtos_ser_id_foreign');
        $this->forge->addKey('produto_id', false, false, 'servico_produtos_produto_id_foreign');
        $this->forge->addForeignKey('produto_id', 'estoque_produtos', 'id', 'CASCADE', 'CASCADE', 'servico_produtos_produto_id_foreign');
        $this->forge->addForeignKey('ser_id', 'servicos', 'ser_id', 'CASCADE', 'CASCADE', 'servico_produtos_ser_id_foreign');
        $this->forge->createTable('servico_produtos');
    }

    public function down()
    {
        $this->forge->dropTable('servicos', true);
        $this->forge->dropTable('servico_produtos', true);
    }
}
