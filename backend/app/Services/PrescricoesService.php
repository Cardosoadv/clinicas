<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PrescricoesRepository;
use Exception;

class PrescricoesService extends BaseService
{
    protected PrescricoesRepository $prescricoesRepo;

    public function __construct(?PrescricoesRepository $prescricoesRepo = null)
    {
        $this->prescricoesRepo = $prescricoesRepo ?? new PrescricoesRepository();
        $this->repository = $this->prescricoesRepo;
    }

    /**
     * Cria uma nova prescrição com múltiplos itens.
     *
     * @param array $data
     * @return array
     */
    public function createPrescricao(array $data): array
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $prescricaoId = $this->prescricoesRepo->create([
                'paciente_id'     => $data['paciente_id'],
                'veterinario_id'  => $data['veterinario_id'] ?? null,
                'data_prescricao' => $data['data_prescricao'] ?? date('Y-m-d'),
                'observacoes'     => $data['observacoes'] ?? null,
            ]);

            if (!$prescricaoId) {
                throw new Exception("Falha ao criar cabeçalho da prescrição.");
            }

            $itens = $data['itens'] ?? [];
            foreach ($itens as $item) {
                $this->prescricoesRepo->createItem([
                    'prescricao_id'     => $prescricaoId,
                    'medicamento'       => $item['medicamento'],
                    'dosagem'           => $item['dosagem'] ?? null,
                    'frequencia'        => $item['frequencia'] ?? null,
                    'duracao'           => $item['duracao'] ?? null,
                    'via_administracao' => $item['via_administracao'] ?? null,
                    'particao'          => $item['particao'] ?? null,
                ]);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->error("Erro ao salvar prescrição.");
            }

            return $this->success("Prescrição registrada com sucesso.", ['id' => $prescricaoId]);

        } catch (Exception $e) {
            $db->transRollback();
            return $this->error("Erro: " . $e->getMessage());
        }
    }

    /**
     * Retorna o histórico de prescrições de um pet.
     *
     * @param int $petId
     * @return array
     */
    public function getByPet(int $petId): array
    {
        return $this->prescricoesRepo->findByPet($petId);
    }

    /**
     * Retorna os detalhes de uma prescrição específica.
     *
     * @param int $id
     * @return array|null
     */
    public function getDetails(int $id): ?array
    {
        return $this->prescricoesRepo->getWithItems($id);
    }
}
