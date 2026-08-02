<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinanceiroTables extends Migration
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
            'pacote_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'servico' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'servico_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'valor' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],
            'desconto' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],
            'data_servico' => [
                'type' => 'DATE',
            ],
            'forma_pagamento' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'parcelas' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => '1',
            ],
            'vencimento' => [
                'type' => 'DATE',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['Pendente', 'Pago', 'Atrasado', 'Cancelado'],
                'default'    => 'Pendente',
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
        $this->forge->addKey('paciente_id', false, false, 'fat_cobrancas_pet_id_foreign');
        $this->forge->addKey('data_servico', false, false, 'idx_fat_cobrancas_data');
        $this->forge->addKey('status', false, false, 'idx_fat_cobrancas_status');
        $this->forge->addKey('pacote_id', false, false, 'fk_cobranca_pacote');
        $this->forge->addForeignKey('paciente_id', 'pacientes', 'paciente_id', 'CASCADE', 'CASCADE', 'fat_cobrancas_pet_id_foreign');
        $this->forge->addForeignKey('pacote_id', 'pacotes', 'id', 'CASCADE', 'SET NULL', 'fk_cobranca_pacote');
        $this->forge->createTable('fat_cobrancas');

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'descricao' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'categoria' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'fornecedor' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'valor' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],
            'data_vencimento' => [
                'type' => 'DATE',
            ],
            'forma_pagamento' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['Pendente', 'Pago', 'Cancelado'],
                'default'    => 'Pendente',
            ],
            'des_grupo_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'des_parcela_num' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'des_parcela_total' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'des_recorrencia' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => 'nenhuma',
            ],
            'des_recorrencia_fim' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'comprovante' => [
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
        $this->forge->addKey('id', true);
        $this->forge->addKey('data_vencimento', false, false, 'idx_fat_despesas_data');
        $this->forge->createTable('fat_despesas');

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'cobranca_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'paciente_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'tipo' => [
                'type'       => 'ENUM',
                'constraint' => ['Recibo', 'NFS-e'],
                'default'    => 'Recibo',
            ],
            'cpf_responsavel' => [
                'type'       => 'VARCHAR',
                'constraint' => 14,
                'null'       => true,
            ],
            'data_emissao' => [
                'type' => 'DATE',
            ],
            'descricao' => [
                'type' => 'TEXT',
            ],
            'valor' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
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
        $this->forge->addUniqueKey('hash', 'idx_fat_notas_hash');
        $this->forge->addKey('cobranca_id', false, false, 'fat_notas_cobranca_id_foreign');
        $this->forge->addKey('paciente_id', false, false, 'fat_notas_pet_id_foreign');
        $this->forge->addForeignKey('cobranca_id', 'fat_cobrancas', 'id', 'SET NULL', 'CASCADE', 'fat_notas_cobranca_id_foreign');
        $this->forge->addForeignKey('paciente_id', 'pacientes', 'paciente_id', 'CASCADE', 'CASCADE', 'fat_notas_pet_id_foreign');
        $this->forge->createTable('fat_notas');
    }

    public function down()
    {
        $this->forge->dropTable('fat_cobrancas', true);
        $this->forge->dropTable('fat_despesas', true);
        $this->forge->dropTable('fat_notas', true);
    }
}
