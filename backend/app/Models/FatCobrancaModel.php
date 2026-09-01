<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * FatCobrancaModel
 *
 * @property int $id
 * @property int $paciente_id
 * @property string $servico
 * @property float $valor
 * @property float $desconto
 * @property string $data_servico
 * @property string|null $forma_pagamento
 * @property int|null $parcelas
 * @property string|null $vencimento
 * @property string $status
 * @property string|null $observacoes
 * @property int|null $pacote_id
 * @property int|null $servico_id
 * @property int|null $agendamento_id
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 */
class FatCobrancaModel extends Model
{
    protected $table            = 'fat_cobrancas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'paciente_id',
        'servico',
        'valor',
        'desconto',
        'data_servico',
        'forma_pagamento',
        'parcelas',
        'vencimento',
        'status',
        'observacoes',
        'pacote_id',
        'servico_id',
        'agendamento_id',
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
