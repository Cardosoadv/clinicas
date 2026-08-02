<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * PrescricaoModel
 *
 * @property int $id
 * @property int $paciente_id
 * @property int|null $veterinario_id
 * @property string $data_prescricao
 * @property string|null $observacoes
 * @property string $created_at
 * @property string $updated_at
 */
class PrescricaoModel extends Model
{
    protected $table            = 'prescricoes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'paciente_id',
        'veterinario_id',
        'data_prescricao',
        'observacoes'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'paciente_id'     => 'required|is_natural_no_zero',
        'data_prescricao' => 'required|valid_date',
    ];
}
