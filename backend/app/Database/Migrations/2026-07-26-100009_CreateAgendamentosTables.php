<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAgendamentosTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'age_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'age_grupo_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 36,
                'null'       => true,
            ],
            'paciente_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'age_data' => [
                'type' => 'DATE',
            ],
            'age_hora' => [
                'type' => 'TIME',
            ],
            'age_duracao' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'age_obs' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'age_lembrete' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'age_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pendente', 'confirmado', 'cancelado', 'concluido'],
                'default'    => 'pendente',
            ],
            'age_veterinario' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'age_faturado' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'age_recorrencia' => [
                'type'       => 'ENUM',
                'constraint' => ['nenhuma', 'semanal', 'quinzenal', 'mensal'],
                'null'       => true,
                'default'    => 'nenhuma',
            ],
            'age_recorrencia_fim' => [
                'type' => 'DATE',
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
        $this->forge->addKey('age_id', true);
        $this->forge->addKey('paciente_id', false, false, 'agendamentos_pet_id_foreign');
        $this->forge->addKey(['age_data', 'age_hora'], false, false, 'idx_age_data_hora');
        $this->forge->addKey('age_status', false, false, 'idx_age_status');
        $this->forge->addKey('age_id', false, false, 'idx_age_paciente_id');
        $this->forge->addKey('age_grupo_id', false, false, 'idx_age_grupo_id');
        $this->forge->addForeignKey('paciente_id', 'pacientes', 'paciente_id', 'CASCADE', 'CASCADE', 'agendamentos_pet_id_foreign');
        $this->forge->createTable('agendamentos');

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'age_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'ser_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('age_id', false, false, 'agendamento_servicos_age_id_foreign');
        $this->forge->addKey('ser_id', false, false, 'agendamento_servicos_ser_id_foreign');
        $this->forge->addForeignKey('age_id', 'agendamentos', 'age_id', 'CASCADE', 'CASCADE', 'agendamento_servicos_age_id_foreign');
        $this->forge->addForeignKey('ser_id', 'servicos', 'ser_id', 'CASCADE', 'CASCADE', 'agendamento_servicos_ser_id_foreign');
        $this->forge->createTable('agendamento_servicos');
    }

    public function down()
    {
        $this->forge->dropTable('agendamentos', true);
        $this->forge->dropTable('agendamento_servicos', true);
    }
}
