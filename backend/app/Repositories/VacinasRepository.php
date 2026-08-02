<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\VacinaModel;

class VacinasRepository extends BaseRepository
{
    public function __construct(?VacinaModel $model = null)
    {
        $this->model = $model ?? new VacinaModel();
    }

    public function findByPet(int $petId): array
    {
        return $this->model->where('paciente_id', $petId)->orderBy('data_aplicacao', 'DESC')->findAll();
    }

    public function getUpcomingExpirations(int $days = 7): array
    {
        $today = date('Y-m-d');
        $limitDate = date('Y-m-d', strtotime("+$days days"));

        return $this->model
            ->select('vacinas.*, pacientes.paciente_nome, clientes.nome as tutor_nome, clientes.telefones as tutor_tel')
            ->join('pacientes', 'pacientes.paciente_id = vacinas.paciente_id')
            ->join('clientes', 'clientes.id = pacientes.cliente_id')
            ->where('data_proxima_dose >=', $today)
            ->where('data_proxima_dose <=', $limitDate)
            ->orderBy('data_proxima_dose', 'ASC')
            ->findAll();
    }
}
