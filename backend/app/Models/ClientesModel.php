<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * ClientesModel
 *
 * @property int $id
 * @property string $nome
 * @property string|null $razao_social
 * @property string|null $apelido
 * @property string|null $observacoes
 * @property string|null $rg
 * @property string|null $cpf
 * @property string|null $nascimento
 * @property string|null $telefones
 * @property string|null $emails
 * @property string|null $rua
 * @property string|null $numero
 * @property string|null $complemento
 * @property string|null $bairro
 * @property string|null $cep
 * @property string|null $cidade
 * @property string|null $estado
 * @property string|null $empresa
 * @property string|null $cnpj
 * @property string|null $vendedor
 * @property string|null $origem_cliente
 * @property string|null $ultima_compra
 * @property int $exportacao
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 */
class ClientesModel extends Model
{
    protected $table            = 'clientes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nome',
        'razao_social',
        'apelido',
        'observacoes',
        'rg',
        'cpf',
        'nascimento',
        'telefones',
        'emails',
        'rua',
        'numero',
        'complemento',
        'bairro',
        'cep',
        'cidade',
        'estado',
        'empresa',
        'cnpj',
        'vendedor',
        'origem_cliente',
        'ultima_compra',
        'exportacao',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

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
}
