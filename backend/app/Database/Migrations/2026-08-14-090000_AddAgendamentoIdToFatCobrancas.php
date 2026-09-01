<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAgendamentoIdToFatCobrancas extends Migration
{
    public function up()
    {
        $this->forge->addColumn('fat_cobrancas', [
            'agendamento_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'pacote_id',
            ],
        ]);

        $this->db->query('ALTER TABLE fat_cobrancas ADD CONSTRAINT fk_cobranca_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamentos (age_id) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE fat_cobrancas DROP FOREIGN KEY fk_cobranca_agendamento');
        $this->forge->dropColumn('fat_cobrancas', 'agendamento_id');
    }
}
