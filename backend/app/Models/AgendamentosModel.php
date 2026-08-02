<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * AgendamentosModel
 *
 * @property int $age_id
 * @property int $paciente_id
 * @property string $age_data
 * @property string $age_hora
 * @property string $age_duracao
 * @property string|null $age_obs
 * @property string $age_lembrete
 * @property string $age_status
 * @property int|null $age_veterinario
 * @property int $age_faturado
 */
class AgendamentosModel extends Model
{
    protected $table            = 'agendamentos';
    protected $primaryKey       = 'age_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'paciente_id',
        'age_data',
        'age_hora',
        'age_duracao',
        'age_obs',
        'age_lembrete',
        'age_status',
        'age_veterinario',
        'age_faturado',
        'age_grupo_id',
        'age_recorrencia',
        'age_recorrencia_fim',
    ];
 
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
