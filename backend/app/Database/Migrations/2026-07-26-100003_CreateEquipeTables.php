<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEquipeTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'equ_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'equ_pronome' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'equ_nome' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'equ_crmv' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'equ_especialidade' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'equ_is_veterinario' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
                'default'    => 0,
            ],
            'equ_status' => [
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
        $this->forge->addKey('equ_id', true);
        $this->forge->addKey('user_id', false, false, 'equipe_user_id_foreign');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE', 'equipe_user_id_foreign');
        $this->forge->createTable('equipe');
    }

    public function down()
    {
        $this->forge->dropTable('equipe', true);
    }
}
