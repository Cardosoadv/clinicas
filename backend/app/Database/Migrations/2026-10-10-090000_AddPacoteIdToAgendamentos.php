<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPacoteIdToAgendamentos extends Migration
{
    public function up()
    {
        $this->forge->addColumn('agendamentos', [
            'pacote_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'age_grupo_id',
            ],
        ]);

        $this->db->query('ALTER TABLE agendamentos ADD INDEX idx_age_pacote_id (pacote_id)');
        $this->db->query('ALTER TABLE agendamentos ADD CONSTRAINT fk_agendamento_pacote FOREIGN KEY (pacote_id) REFERENCES pacotes (id) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE agendamentos DROP FOREIGN KEY fk_agendamento_pacote');
        $this->db->query('ALTER TABLE agendamentos DROP INDEX idx_age_pacote_id');
        $this->forge->dropColumn('agendamentos', 'pacote_id');
    }
}
