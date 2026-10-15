<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIndexDesGrupoIdToFatDespesas extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE fat_despesas ADD INDEX idx_fat_despesas_grupo (des_grupo_id)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE fat_despesas DROP INDEX idx_fat_despesas_grupo');
    }
}
