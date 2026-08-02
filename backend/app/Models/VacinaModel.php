<?php

namespace App\Models;

use CodeIgniter\Model;

class VacinaModel extends Model
{
    protected $table            = 'vacinas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'paciente_id',
        'vacina_nome',
        'data_aplicacao',
        'data_proxima_dose',
        'lote',
        'fabricante',
        'observacoes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'paciente_id'       => 'required|is_natural_no_zero',
        'vacina_nome'       => 'required|min_length[3]|max_length[255]',
        'data_aplicacao'    => 'required|valid_date',
        'data_proxima_dose' => 'permit_empty|valid_date',
    ];
}
