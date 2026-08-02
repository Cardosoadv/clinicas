<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEstoqueTables extends Migration
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
                'constraint' => 255,
            ],
            'descricao' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'unidade_medida' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'quantidade_atual' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],
            'estoque_minimo' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],
            'valor_venda' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
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
        $this->forge->createTable('estoque_produtos');

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'produto_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'tipo' => [
                'type'       => 'ENUM',
                'constraint' => ['Entrada', 'Saída', 'Ajuste'],
                'default'    => 'Entrada',
            ],
            'quantidade' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'valor_unitario' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],
            'valor_total' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],
            'despesa_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'observacao' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addKey('produto_id', false, false, 'estoque_movimentacoes_produto_id_foreign');
        $this->forge->addKey('despesa_id', false, false, 'estoque_movimentacoes_despesa_id_foreign');
        $this->forge->addForeignKey('despesa_id', 'fat_despesas', 'id', 'SET NULL', 'CASCADE', 'estoque_movimentacoes_despesa_id_foreign');
        $this->forge->addForeignKey('produto_id', 'estoque_produtos', 'id', 'CASCADE', 'CASCADE', 'estoque_movimentacoes_produto_id_foreign');
        $this->forge->createTable('estoque_movimentacoes');
    }

    public function down()
    {
        $this->forge->dropTable('estoque_produtos', true);
        $this->forge->dropTable('estoque_movimentacoes', true);
    }
}
