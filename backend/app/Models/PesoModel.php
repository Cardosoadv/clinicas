<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * PesoModel
 *
 * @property int $id
 * @property int $paciente_id
 * @property float $peso
 * @property string $data_pesagem
 * @property string|null $observacoes
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 */
class PesoModel extends Model
{
    protected $table            = 'paciente_pesos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'paciente_id',
        'peso',
        'data_pesagem',
        'observacoes',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'          => 'integer',
        'paciente_id' => 'integer',
        'peso'        => 'float',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
