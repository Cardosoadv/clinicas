<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * ConfigModel
 *
 * @property int $id
 * @property string $meta_key
 * @property string|null $meta_value
 * @property string|null $description
 * @property string $created_at
 * @property string $updated_at
 */
class ConfigModel extends Model
{
    protected $table            = 'configs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'meta_key',
        'meta_value',
        'description',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Retrieve a configuration value by its key.
     */
    public function getMetaValue(string $key, $default = null)
    {
        $config = $this->where('meta_key', $key)->first();
        return $config ? $config['meta_value'] : $default;
    }

    /**
     * Set a configuration value.
     */
    public function setMetaValue(string $key, $value)
    {
        $config = $this->where('meta_key', $key)->first();
        if ($config) {
            return $this->update($config['id'], ['meta_value' => $value]);
        }
        return $this->insert(['meta_key' => $key, 'meta_value' => $value]);
    }
}
