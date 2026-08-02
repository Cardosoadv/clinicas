<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * PacoteUsoModel
 *
 * @property int $id
 * @property int $pacote_id
 * @property int|null $servico_id
 * @property int $quantidade
 * @property float|null $valor_descontado
 * @property string $data_uso
 * @property string|null $observacao
 * @property string $created_at
 * @property string $updated_at
 */
class PacoteUsoModel extends Model
{
    protected $table            = 'pacote_uso';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'pacote_id',
        'servico_id',
        'quantidade',
        'valor_descontado',
        'data_uso',
        'observacao'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
