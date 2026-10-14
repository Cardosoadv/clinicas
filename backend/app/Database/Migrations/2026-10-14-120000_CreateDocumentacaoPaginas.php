<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Páginas da documentação interna criadas pela interface.
 *
 * Mesmo formato das páginas em arquivo (docs/documentacao-interna.md): os campos
 * do cabeçalho viram colunas, `telas` e `palavras_chave` são listas em JSON e o
 * corpo fica em `markdown`. Página com o mesmo slug de um arquivo o substitui
 * depois de publicada. Status é VARCHAR validado no model (rascunho|publicado),
 * para a migration rodar igual em MySQL e SQLite (testes).
 *
 * Exclusão é definitiva: volta a valer o arquivo de mesmo slug, se houver, e o
 * slug fica livre para ser usado de novo.
 */
class CreateDocumentacaoPaginas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'slug'           => ['type' => 'VARCHAR', 'constraint' => 160],
            'titulo'         => ['type' => 'VARCHAR', 'constraint' => 120],
            'resumo'         => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'ordem'          => ['type' => 'INT', 'default' => 100],
            'permissao'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'telas'          => ['type' => 'TEXT', 'null' => true],
            'palavras_chave' => ['type' => 'TEXT', 'null' => true],
            'markdown'       => ['type' => 'MEDIUMTEXT', 'null' => true],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'rascunho'],
            'publicado_em'   => ['type' => 'DATETIME', 'null' => true],
            'criado_por'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'atualizado_por' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('status');
        $this->forge->createTable('documentacao_paginas', true);

        // O catálogo em cache foi montado sem a tabela: descarta.
        \App\Services\Documentacao\CatalogoDocumentacao::limparCache();
    }

    public function down()
    {
        $this->forge->dropTable('documentacao_paginas', true);
    }
}
