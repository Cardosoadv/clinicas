<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Materiais (medicamentos, seringas, luvas etc.) utilizados em um agendamento
 * e lançados no momento do faturamento.
 */
class CreateAgendamentoMateriaisTable extends Migration
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
            'agendamento_id' => [
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
            'valor_unitario' => [
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
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('agendamento_id', false, false, 'idx_age_materiais_agendamento_id');
        $this->forge->addKey('produto_id', false, false, 'idx_age_materiais_produto_id');
        $this->forge->addForeignKey('agendamento_id', 'agendamentos', 'age_id', 'CASCADE', 'CASCADE', 'fk_age_materiais_agendamento');
        $this->forge->addForeignKey('produto_id', 'estoque_produtos', 'id', 'CASCADE', 'CASCADE', 'fk_age_materiais_produto');
        $this->forge->createTable('agendamento_materiais');
    }

    public function down()
    {
        $this->forge->dropTable('agendamento_materiais', true);
    }
}
