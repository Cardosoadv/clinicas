<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * PrescricaoItemModel
 *
 * @property int $id
 * @property int $prescricao_id
 * @property string $medicamento
 * @property string|null $dosagem
 * @property string|null $frequencia
 * @property string|null $duracao
 * @property string|null $via_administracao
 * @property string $created_at
 * @property string $updated_at
 */
class PrescricaoItemModel extends Model
{
    protected $table            = 'prescricao_itens';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'prescricao_id',
        'medicamento',
        'dosagem',
        'frequencia',
        'duracao',
        'via_administracao',
        'particao'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'prescricao_id' => 'required|is_natural_no_zero',
        'medicamento'   => 'required|min_length[2]|max_length[255]',
    ];
}
