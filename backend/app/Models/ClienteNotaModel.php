<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * ClienteNotaModel
 *
 * @property int $id
 * @property int $cliente_id
 * @property string $texto
 * @property string|null $autor
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 */
class ClienteNotaModel extends Model
{
    protected $table            = 'cliente_notas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'cliente_id',
        'texto',
        'autor',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'         => 'integer',
        'cliente_id' => 'integer',
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
