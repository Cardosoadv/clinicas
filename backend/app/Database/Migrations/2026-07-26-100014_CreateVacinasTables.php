<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVacinasTables extends Migration
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
            'vacina_nome' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'data_aplicacao' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'data_proxima_dose' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'lote' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'fabricante' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
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
        $this->forge->addKey('paciente_id', false, false, 'vacinas_pet_id_foreign');
        $this->forge->addForeignKey('paciente_id', 'pacientes', 'paciente_id', 'CASCADE', 'CASCADE', 'vacinas_pet_id_foreign');
        $this->forge->createTable('vacinas');
    }

    public function down()
    {
        $this->forge->dropTable('vacinas', true);
    }
}
