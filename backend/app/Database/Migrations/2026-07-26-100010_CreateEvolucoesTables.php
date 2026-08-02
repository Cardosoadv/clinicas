<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEvolucoesTables extends Migration
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
            'data_registro' => [
                'type' => 'DATETIME',
            ],
            'titulo' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'descricao' => [
                'type' => 'TEXT',
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
        $this->forge->addKey('paciente_id', false, false, 'evolucoes_pet_id_foreign');
        $this->forge->addKey('veterinario_id', false, false, 'evolucoes_dentista_id_foreign');
        $this->forge->addForeignKey('veterinario_id', 'equipe', 'equ_id', 'SET NULL', 'CASCADE', 'evolucoes_dentista_id_foreign');
        $this->forge->addForeignKey('paciente_id', 'pacientes', 'paciente_id', 'CASCADE', 'CASCADE', 'evolucoes_pet_id_foreign');
        $this->forge->createTable('evolucoes');
    }

    public function down()
    {
        $this->forge->dropTable('evolucoes', true);
    }
}
