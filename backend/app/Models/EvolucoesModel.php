<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * EvolucoesModel
 *
 * @property int $id
 * @property int $paciente_id
 * @property int $veterinario_id
 * @property string $data_registro
 * @property string|null $titulo
 * @property string|null $descricao
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 */
class EvolucoesModel extends Model
{
    protected $table            = 'evolucoes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'paciente_id',
        'veterinario_id',
        'data_registro',
        'titulo',
        'descricao'
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
