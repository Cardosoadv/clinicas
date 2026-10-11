<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * AgendamentoMaterialModel
 *
 * Representa um material (produto do estoque) utilizado em um agendamento.
 *
 * @property int $id
 * @property int $agendamento_id
 * @property int $produto_id
 * @property float $quantidade
 * @property float $valor_unitario
 * @property string $created_at
 * @property string $updated_at
 */
class AgendamentoMaterialModel extends Model
{
    protected $table            = 'agendamento_materiais';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'agendamento_id',
        'produto_id',
        'quantidade',
        'valor_unitario',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
