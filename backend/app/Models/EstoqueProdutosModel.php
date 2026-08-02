<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * EstoqueProdutosModel
 *
 * @property int $id
 * @property string $nome
 * @property string|null $descricao
 * @property string|null $unidade_medida
 * @property float $quantidade_atual
 * @property float $estoque_minimo
 * @property float|null $valor_venda
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 */
class EstoqueProdutosModel extends Model
{
    protected $table            = 'estoque_produtos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields    = [
        'nome',
        'descricao',
        'unidade_medida',
        'quantidade_atual',
        'estoque_minimo',
        'valor_venda',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'nome'             => 'required|min_length[3]',
        'quantidade_atual' => 'numeric',
        'estoque_minimo'   => 'numeric',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
}
