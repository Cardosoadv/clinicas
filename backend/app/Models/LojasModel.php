<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * LojasModel
 *
 * @property int $id
 * @property string $nome
 * @property string|null $cnpj
 * @property string|null $email
 * @property string|null $telefone
 * @property string|null $endereco
 * @property string|null $cidade
 * @property string|null $estado
 * @property string|null $cep
 * @property string|null $logo
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 */
class LojasModel extends Model
{
    protected $table            = 'lojas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nome',
        'cnpj',
        'email',
        'telefone',
        'endereco',
        'cidade',
        'estado',
        'cep',
        'logo',
        'status',
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
    protected $validationRules      = [
        'nome'     => 'required|min_length[3]|max_length[255]',
        'cnpj'     => 'permit_empty|min_length[14]|max_length[20]',
        'email'    => 'permit_empty|valid_email|max_length[255]',
        'telefone' => 'permit_empty|min_length[8]|max_length[20]',
        'estado'   => 'permit_empty|exact_length[2]',
        'status'   => 'required|in_list[Ativo,Inativo]',
    ];
    protected $validationMessages   = [
        'nome' => [
            'required' => 'O nome da loja é obrigatório.',
            'min_length' => 'O nome da loja deve ter pelo menos 3 caracteres.',
        ],
        'email' => [
            'valid_email' => 'Por favor, informe um endereço de e-mail válido.',
        ],
        'status' => [
            'in_list' => 'O status deve ser Ativo ou Inativo.',
        ],
    ];
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
