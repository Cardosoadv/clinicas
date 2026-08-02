<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * EstoqueMovimentacoesModel
 *
 * @property int $id
 * @property int $produto_id
 * @property string $tipo
 * @property float $quantidade
 * @property float|null $valor_unitario
 * @property float|null $valor_total
 * @property int|null $despesa_id
 * @property string|null $observacao
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 */
class EstoqueMovimentacoesModel extends Model
{
    protected $table            = 'estoque_movimentacoes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields    = [
        'produto_id',
        'tipo',
        'quantidade',
        'valor_unitario',
        'valor_total',
        'despesa_id',
        'observacao',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'produto_id' => 'required|is_natural_no_zero',
        'tipo'       => 'required|in_list[Entrada,Saída,Ajuste]',
        'quantidade' => 'required|numeric',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
}
