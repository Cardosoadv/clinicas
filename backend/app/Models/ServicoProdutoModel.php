<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * ServicoProdutoModel
 *
 * Representa o vínculo entre um serviço e os produtos consumidos (BOM).
 *
 * @property int $id
 * @property int $ser_id
 * @property int $produto_id
 * @property float $quantidade
 * @property string $created_at
 * @property string $updated_at
 */
class ServicoProdutoModel extends Model
{
    protected $table            = 'servico_produtos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ser_id',
        'produto_id',
        'quantidade'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
