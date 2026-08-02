<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * PacoteItemModel
 *
 * @property int $id
 * @property int $pacote_id
 * @property int|null $servico_id
 * @property string $item_nome
 * @property int $quantidade_total
 * @property int $quantidade_usada
 * @property float|null $valor_unitario
 * @property string $created_at
 * @property string $updated_at
 */
class PacoteItemModel extends Model
{
    protected $table            = 'pacote_itens';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'pacote_id',
        'servico_id',
        'item_nome',
        'quantidade_total',
        'quantidade_usada',
        'valor_unitario'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
