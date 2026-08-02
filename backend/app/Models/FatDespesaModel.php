<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * FatDespesaModel
 *
 * @property int $id
 * @property string $descricao
 * @property string $categoria
 * @property string|null $fornecedor
 * @property float $valor
 * @property string $data_vencimento
 * @property string|null $forma_pagamento
 * @property string $status
 * @property string|null $comprovante
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 */
class FatDespesaModel extends Model
{
    protected $table            = 'fat_despesas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'descricao',
        'categoria',
        'fornecedor',
        'valor',
        'data_vencimento',
        'forma_pagamento',
        'status',
        'comprovante',
        'des_grupo_id',
        'des_parcela_num',
        'des_parcela_total',
        'des_recorrencia',
        'des_recorrencia_fim'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
