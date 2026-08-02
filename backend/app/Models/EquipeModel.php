<?php

declare(strict_types=1);
 
 namespace App\Models;
 
 use CodeIgniter\Model;
 
 /**
  * EquipeModel
  *
  * @property int $equ_id
  * @property int|null $user_id
  * @property string|null $equ_pronome
  * @property string $equ_nome
  * @property string|null $equ_crmv
  * @property string|null $equ_especialidade
  * @property int $equ_is_veterinario
  * @property string $equ_status
  * @property string $created_at
  * @property string $updated_at
  * @property string|null $deleted_at
  */
 class EquipeModel extends Model
 {
     protected $table            = 'equipe';
     protected $primaryKey       = 'equ_id';
     protected $useAutoIncrement = true;
     protected $returnType       = 'array';
     protected $useSoftDeletes   = false;
     protected $protectFields    = true;
     protected $allowedFields    = [
         'user_id',
         'equ_pronome',
         'equ_nome',
         'equ_crmv',
         'equ_especialidade',
         'equ_is_veterinario',
         'equ_status',
     ];
 
     // Dates
     protected $useTimestamps = true;
     protected $dateFormat    = 'datetime';
     protected $createdField  = 'created_at';
     protected $updatedField  = 'updated_at';
     protected $deletedField  = 'deleted_at';
 
     // Validation
     protected $validationRules      = [
         'equ_nome' => 'required|min_length[3]|max_length[255]',
         'equ_crmv'  => 'permit_empty|max_length[50]',
     ];
     protected $validationMessages   = [];
     protected $skipValidation       = false;
     protected $cleanValidationRules = true;
 }
