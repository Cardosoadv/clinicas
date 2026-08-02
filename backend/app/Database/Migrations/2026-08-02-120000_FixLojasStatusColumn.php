<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * A coluna `lojas.status` foi criada como INT por engano; a aplicação (validação,
 * controller, resto do domínio) sempre tratou status como texto ('Ativo'/'Inativo'),
 * igual a `servicos.ser_status` e `equipe.equ_status`. Corrige o tipo e normaliza
 * os valores 1/0 já gravados para o enum de texto correspondente.
 */
class FixLojasStatusColumn extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('lojas', [
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'Ativo',
            ],
        ]);

        $this->db->query("UPDATE lojas SET status = 'Ativo' WHERE status = '1'");
        $this->db->query("UPDATE lojas SET status = 'Inativo' WHERE status = '0'");
    }

    public function down()
    {
        $this->forge->modifyColumn('lojas', [
            'status' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
        ]);
    }
}
