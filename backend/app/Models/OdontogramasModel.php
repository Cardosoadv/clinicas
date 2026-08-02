<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * OdontogramasModel
 *
 * @property int $id
 * @property int $paciente_id
 * @property string $data_registro
 * @property string|null $mapa_dentes
 * @property string|null $observacoes
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 */
class OdontogramasModel extends Model
{
    protected $table            = 'odontogramas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'paciente_id',
        'data_registro',
        'mapa_dentes',
        'observacoes'
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
