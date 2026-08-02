<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePacotesTables extends Migration
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
            'paciente_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'nome' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'tipo' => [
                'type'       => 'ENUM',
                'constraint' => ['Serviços', 'Crédito'],
                'default'    => 'Serviços',
            ],
            'saldo_valor' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
                'comment'    => 'Usado apenas para pacotes do tipo Crédito',
            ],
            'data_validade' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['Pendente', 'Ativo', 'Esgotado', 'Cancelado'],
                'default'    => 'Pendente',
            ],
            'observacoes' => [
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
        $this->forge->addKey('paciente_id', false, false, 'pacotes_pet_id_foreign');
        $this->forge->addForeignKey('paciente_id', 'pacientes', 'paciente_id', 'CASCADE', 'CASCADE', 'pacotes_pet_id_foreign');
        $this->forge->createTable('pacotes');

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'pacote_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'servico_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'item_nome' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Caso não queira vincular a um serviço específico',
            ],
            'quantidade_total' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'quantidade_usada' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
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
        $this->forge->addKey('pacote_id', false, false, 'pacote_itens_pacote_id_foreign');
        $this->forge->addForeignKey('pacote_id', 'pacotes', 'id', 'CASCADE', 'CASCADE', 'pacote_itens_pacote_id_foreign');
        $this->forge->createTable('pacote_itens');

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'pacote_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'servico_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'quantidade' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'valor_descontado' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],
            'data_uso' => [
                'type' => 'DATETIME',
                'null' => true,
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
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('pacote_id', false, false, 'pacote_uso_pacote_id_foreign');
        $this->forge->addForeignKey('pacote_id', 'pacotes', 'id', 'CASCADE', 'CASCADE', 'pacote_uso_pacote_id_foreign');
        $this->forge->createTable('pacote_uso');
    }

    public function down()
    {
        $this->forge->dropTable('pacotes', true);
        $this->forge->dropTable('pacote_itens', true);
        $this->forge->dropTable('pacote_uso', true);
    }
}
