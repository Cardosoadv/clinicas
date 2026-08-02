<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * FatNotaModel
 *
 * @property int $id
 * @property int $cobranca_id
 * @property int $paciente_id
 * @property string $tipo
 * @property string|null $cpf_responsavel
 * @property string $data_emissao
 * @property string|null $descricao
 * @property float $valor
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 */
class FatNotaModel extends Model
{
    protected $table            = 'fat_notas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'cobranca_id',
        'paciente_id',
        'tipo',
        'cpf_responsavel',
        'data_emissao',
        'descricao',
        'valor',
        'hash'
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
