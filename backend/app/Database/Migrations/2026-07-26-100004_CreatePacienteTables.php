<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatepacientesTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'paciente_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'cliente_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'paciente_avatar' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'default'    => '🐾',
            ],
            'paciente_nome' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'paciente_nascimento' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'paciente_sexo' => [
                'type'       => 'ENUM',
                'constraint' => ['Fêmea', 'Macho', 'Outro'],
            ],
            'paciente_raca' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'paciente_especie' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'paciente_pelagem' => [
                'type'       => 'ENUM',
                'constraint' => ['Curto', 'Médio', 'Longo', 'Sem pelo'],
                'null'       => true,
            ],
            'paciente_porte' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'paciente_sangue' => [
                'type'       => 'ENUM',
                'constraint' => ['DEA 1.1+', 'DEA 1.1-', 'A', 'B', 'AB'],
                'null'       => true,
            ],
            'paciente_endereco' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'paciente_conheceu' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'paciente_doenca_sist' => [
                'type'       => 'ENUM',
                'constraint' => ['Sim', 'Não'],
                'default'    => 'Não',
            ],
            'paciente_condicoes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'paciente_caracteristicas' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'paciente_alergia_med' => [
                'type'       => 'ENUM',
                'constraint' => ['Sim', 'Não'],
                'default'    => 'Não',
            ],
            'paciente_medicamentos' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'paciente_saude_obs' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'paciente_queixa' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'paciente_habitos' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'paciente_status' => [
                'type'       => 'ENUM',
                'constraint' => ['Ativo', 'Inativo', 'Suspenso'],
                'default'    => 'Ativo',
            ],
            'exportacao' => [
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
        $this->forge->addKey('paciente_id', true);
        $this->forge->addKey('cliente_id', false, false, 'pets_cliente_id_foreign');
        $this->forge->addForeignKey('cliente_id', 'clientes', 'id', 'SET NULL', 'CASCADE', 'pets_cliente_id_foreign');
        $this->forge->createTable('pacientes');

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
            'peso' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,3',
            ],
            'data_pesagem' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey('paciente_id', false, false, 'pet_pesos_pet_id_foreign');
        $this->forge->addForeignKey('paciente_id', 'pacientes', 'paciente_id', 'CASCADE', 'CASCADE', 'pet_pesos_pet_id_foreign');
        $this->forge->createTable('paciente_pesos');
    }

    public function down()
    {
        $this->forge->dropTable('pacientes', true);
        $this->forge->dropTable('paciente_pesos', true);
    }
}
