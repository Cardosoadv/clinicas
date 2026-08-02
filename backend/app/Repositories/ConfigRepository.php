<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ConfigModel;

/**
 * Repositório para configurações do sistema.
 */
class ConfigRepository extends BaseRepository
{
    /**
     * @param ConfigModel|null $model
     */
    public function __construct(?ConfigModel $model = null)
    {
        $this->model = $model ?? new ConfigModel();
    }

    /**
     * Busca um valor de configuração pela chave.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMetaValue(string $key, $default = null)
    {
        /** @var ConfigModel $model */
        $model = $this->model;
        return $model->getMetaValue($key, $default);
    }

    /**
     * Define um valor de configuração.
     *
     * @param string $key
     * @param mixed $value
     * @return bool|int|string
     */
    public function setMetaValue(string $key, $value)
    {
        /** @var ConfigModel $model */
        $model = $this->model;
        return $model->setMetaValue($key, $value);
    }

    /**
     * Busca templates de WhatsApp pelo meta_key.
     *
     * @return array
     */
    public function findTemplates(): array
    {
        return $this->model->like('meta_key', 'whatsapp_template')->findAll();
    }

    
    /**
     * Find configurations by key pattern.
     *
     * @param string $pattern
     * @return array
     */
    public function findLike(string $pattern): array
    {
        return $this->model->like('meta_key', $pattern)->findAll();
    }

    /**
     * Find configurations by multiple keys.
     *
     * @param array $keys
     * @return array
     */
    public function findByKeys(array $keys): array
    {
        return $this->model->whereIn('meta_key', $keys)->findAll();
    }
}

