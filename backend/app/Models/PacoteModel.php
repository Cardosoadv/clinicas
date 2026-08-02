<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * PacoteModel
 *
 * @property int $id
 * @property int $paciente_id
 * @property string $nome
 * @property string $tipo
 * @property float $saldo_valor
 * @property string|null $data_validade
 * @property string $status
 * @property string|null $observacoes
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 */
class PacoteModel extends Model
{
    protected $table            = 'pacotes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'paciente_id',
        'nome',
        'tipo',
        'saldo_valor',
        'data_validade',
        'status',
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
