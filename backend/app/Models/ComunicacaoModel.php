<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * ComunicacaoModel
 *
 * @property int $id
 * @property int $cliente_id
 * @property string $canal
 * @property string $tipo
 * @property string|null $paciente_nome
 * @property string $mensagem
 * @property string $created_at
 */
class ComunicacaoModel extends Model
{
    protected $table            = 'comunicacoes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'cliente_id',
        'canal',
        'tipo',
        'paciente_nome',
        'mensagem',
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
    protected $updatedField  = '';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
