<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePrescricoesTables extends Migration
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
            'veterinario_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'data_prescricao' => [
                'type' => 'DATE',
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
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('paciente_id', false, false, 'prescricoes_pet_id_foreign');
        $this->forge->addKey('veterinario_id', false, false, 'prescricoes_veterinario_id_foreign');
        $this->forge->addForeignKey('paciente_id', 'pacientes', 'paciente_id', 'CASCADE', 'CASCADE', 'prescricoes_pet_id_foreign');
        $this->forge->addForeignKey('veterinario_id', 'equipe', 'equ_id', 'SET NULL', 'CASCADE', 'prescricoes_veterinario_id_foreign');
        $this->forge->createTable('prescricoes');

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'prescricao_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'medicamento' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'dosagem' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'frequencia' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'duracao' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'via_administracao' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
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
            'particao' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => '1',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('prescricao_id', false, false, 'prescricao_itens_prescricao_id_foreign');
        $this->forge->addForeignKey('prescricao_id', 'prescricoes', 'id', 'CASCADE', 'CASCADE', 'prescricao_itens_prescricao_id_foreign');
        $this->forge->createTable('prescricao_itens');
    }

    public function down()
    {
        $this->forge->dropTable('prescricoes', true);
        $this->forge->dropTable('prescricao_itens', true);
    }
}
