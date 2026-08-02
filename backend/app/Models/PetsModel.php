<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * PetsModel
 *
 * @property int $paciente_id
 * @property int $cliente_id
 * @property string|null $paciente_avatar
 * @property string $paciente_nome
 * @property string|null $paciente_nascimento
 * @property string|null $paciente_sexo
 * @property string|null $paciente_raca
 * @property string|null $paciente_especie
 * @property string|null $paciente_pelagem
 * @property string|null $paciente_porte
 * @property string|null $paciente_caracteristicas
 * @property string|null $paciente_sangue
 * @property string|null $paciente_endereco
 * @property string|null $paciente_conheceu
 * @property string|null $paciente_doenca_sist
 * @property string|null $paciente_condicoes
 * @property string|null $paciente_alergia_med
 * @property string|null $paciente_medicamentos
 * @property string|null $paciente_saude_obs
 * @property string|null $paciente_queixa
 * @property string|null $paciente_habitos
 * @property string $paciente_status
 * @property int $exportacao
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 */
class PetsModel extends Model
{
    protected $table            = 'pacientes';
    protected $primaryKey       = 'paciente_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'cliente_id',
        'paciente_avatar',
        'paciente_nome',
        'paciente_nascimento',
        'paciente_sexo',
        'paciente_raca',
        'paciente_especie',
        'paciente_pelagem',
        'paciente_porte',
        'paciente_caracteristicas',
        'paciente_sangue',
        'paciente_endereco',
        'paciente_conheceu',
        'paciente_doenca_sist',
        'paciente_condicoes',
        'paciente_alergia_med',
        'paciente_medicamentos',
        'paciente_saude_obs',
        'paciente_queixa',
        'paciente_habitos',
        'paciente_status',
        'exportacao',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

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
