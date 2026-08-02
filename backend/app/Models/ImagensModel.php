<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * ImagensModel
 *
 * @property int $id
 * @property int $paciente_id
 * @property string $titulo
 * @property string $arquivo_url
 * @property string|null $data_exame
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 */
class ImagensModel extends Model
{
    protected $table            = 'imagens';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'paciente_id',
        'titulo',
        'arquivo_url',
        'data_exame'
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
