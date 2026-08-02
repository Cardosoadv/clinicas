<?php

declare(strict_types=1);
 
 namespace App\Models;
 
 use CodeIgniter\Model;
 
 /**
  * ServicosModel
  *
  * @property int $ser_id
  * @property string $ser_nome
  * @property float $ser_valor
  * @property int|null $ser_tempo_estimado
  * @property int|null $pop_id
  * @property string|null $ser_descricao
  * @property string $ser_status
  * @property string $created_at
  * @property string $updated_at
  * @property string|null $deleted_at
  */
 class ServicosModel extends Model
 {
     protected $table            = 'servicos';
     protected $primaryKey       = 'ser_id';
     protected $useAutoIncrement = true;
     protected $returnType       = 'array';
     protected $useSoftDeletes   = true;
     protected $protectFields    = true;
     protected $allowedFields    = [
         'ser_nome',
         'ser_icone',
         'ser_valor',
         'ser_tempo_estimado',
         'pop_id',
         'ser_descricao',
         'ser_status',
     ];
 
     protected bool $allowEmptyInserts = false;
     protected bool $updateOnlyChanged = true;
 
     protected array $casts = [
         'ser_id'             => 'integer',
         'ser_valor'          => 'float',
         'ser_tempo_estimado' => '?integer',
         'pop_id'             => '?integer',
     ];
 
     // Dates
     protected $useTimestamps = true;
     protected $dateFormat    = 'datetime';
     protected $createdField  = 'created_at';
     protected $updatedField  = 'updated_at';
     protected $deletedField  = 'deleted_at';
 }
