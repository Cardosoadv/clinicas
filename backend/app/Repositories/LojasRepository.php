<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\LojasModel;

class LojasRepository extends BaseRepository
{
    /**
     * @param LojasModel|null $model
     */
    public function __construct(?LojasModel $model = null)
    {
        $this->model = $model ?? new LojasModel();
    }
}
