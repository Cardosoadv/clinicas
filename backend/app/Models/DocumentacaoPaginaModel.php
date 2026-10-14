<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Páginas da documentação interna criadas pela interface. Quem criou e quem
 * alterou por último ficam em `criado_por` e `atualizado_por`.
 */
class DocumentacaoPaginaModel extends Model
{
    public const STATUS = ['rascunho', 'publicado'];

    protected $table            = 'documentacao_paginas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'slug', 'titulo', 'resumo', 'ordem', 'permissao', 'telas', 'palavras_chave',
        'markdown', 'status', 'publicado_em', 'criado_por', 'atualizado_por',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'id'     => 'permit_empty|is_natural_no_zero',
        'slug'   => 'required|max_length[160]|is_unique[documentacao_paginas.slug,id,{id}]',
        'titulo' => 'required|max_length[120]',
        'status' => 'permit_empty|in_list[rascunho,publicado]',
    ];
    protected $validationMessages = [
        'slug' => ['is_unique' => 'Já existe uma página com este endereço.'],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
