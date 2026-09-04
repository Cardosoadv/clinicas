<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PetsRepository;
use App\Repositories\OdontogramasRepository;
use App\Repositories\EvolucoesRepository;
use App\Repositories\ImagensRepository;
use App\Repositories\AgendamentosRepository;
use App\Repositories\FatCobrancaRepository;
use App\Repositories\VacinasRepository;
use App\Repositories\PesosRepository;
use App\Repositories\PrescricoesRepository;
use Exception;

/**
 * @property PetsRepository $repository
 */
class ProntuariosService extends BaseService
{
    protected PetsRepository $petsRepo;
    protected OdontogramasRepository $odontogramasRepo;
    protected EvolucoesRepository $evolucoesRepo;
    protected ImagensRepository $imagensRepo;
    protected AgendamentosRepository $agendamentosRepo;
    protected FatCobrancaRepository $fatCobrancaRepo;
    protected VacinasRepository $vacinasRepo;
    protected PesosRepository $pesosRepo;
    protected PrescricoesRepository $prescricoesRepo;

    /**
     * @param PetsRepository|null $petsRepo
     * @param OdontogramasRepository|null $odontogramasRepo
     * @param EvolucoesRepository|null $evolucoesRepo
     * @param ImagensRepository|null $imagensRepo
     * @param AgendamentosRepository|null $agendamentosRepo
     * @param FatCobrancaRepository|null $fatCobrancaRepo
     * @param VacinasRepository|null $vacinasRepo
     * @param PesosRepository|null $pesosRepo
     * @param PrescricoesRepository|null $prescricoesRepo
     */
    public function __construct(
        ?PetsRepository $petsRepo = null,
        ?OdontogramasRepository $odontogramasRepo = null,
        ?EvolucoesRepository $evolucoesRepo = null,
        ?ImagensRepository $imagensRepo = null,
        ?AgendamentosRepository $agendamentosRepo = null,
        ?FatCobrancaRepository $fatCobrancaRepo = null,
        ?VacinasRepository $vacinasRepo = null,
        ?PesosRepository $pesosRepo = null,
        ?PrescricoesRepository $prescricoesRepo = null
    ) {
        $this->petsRepo         = $petsRepo ?? new PetsRepository();
        $this->odontogramasRepo = $odontogramasRepo ?? new OdontogramasRepository();
        $this->evolucoesRepo    = $evolucoesRepo ?? new EvolucoesRepository();
        $this->imagensRepo      = $imagensRepo ?? new ImagensRepository();
        $this->agendamentosRepo = $agendamentosRepo ?? new AgendamentosRepository();
        $this->fatCobrancaRepo = $fatCobrancaRepo ?? new FatCobrancaRepository();
        $this->vacinasRepo = $vacinasRepo ?? new VacinasRepository();
        $this->pesosRepo = $pesosRepo ?? new PesosRepository();
        $this->prescricoesRepo = $prescricoesRepo ?? new PrescricoesRepository();

        // Initialize parent repository to PetsRepository as default
        $this->repository = $this->petsRepo;
    }

    /**
     * Retorna a lista de pets
     *
     * @param string|null $term
     * @return array
     */
    public function getPetsList(?string $term = null): array
    {
        if ($term) {
            return $this->petsRepo->search($term);
        }
        return $this->petsRepo->findAllWithClient();
    }

    /**
     * Get all data for a pet's medical record
     *
     * @param int $petId
     * @return array
     * @throws Exception
     */
    public function getFullRecord(int $petId): array
    {
        $pet = $this->petsRepo->findById($petId);
        if (!$pet) {
            throw new Exception("Pet não encontrado.");
        }

        $odontograma = $this->odontogramasRepo->findLatestByPet($petId);
        $evolucoes   = $this->evolucoesRepo->findByPet($petId);
        $imagens      = $this->imagensRepo->findByPet($petId);
        $vacinas      = $this->vacinasRepo->findByPet($petId);
        $pesos        = $this->pesosRepo->findByPet($petId);
        $prescricoes  = $this->prescricoesRepo->findByPet($petId);
        $historico    = $this->getCombinedHistory($petId);

        return [
            'pet'          => $pet,
            'odontograma'  => $odontograma,
            'evolucoes'    => $evolucoes,
            'imagens'      => $imagens,
            'vacinas'      => $vacinas,
            'pesos'        => $pesos,
            'prescricoes'  => $prescricoes,
            'historico'    => $historico,
            'estatisticas' => $this->calculateStats($imagens, $historico)
        ];
    }

    /**
     * Salva o odontograma de um pet
     *
     * @param int $petId
     * @param array $data
     * @return array
     */
    public function saveOdontograma(int $petId, array $data): array
    {
        try {
            $saveData = [
                'paciente_id'   => $petId,
                'data_registro' => date('Y-m-d H:i:s'),
                'mapa_dentes'   => json_encode($data['mapa'], JSON_THROW_ON_ERROR),
                'observacoes'   => $data['observacoes'] ?? null
            ];

            $id = $this->odontogramasRepo->create($saveData);
            return $this->success('Odontograma salvo com sucesso', ['id' => $id]);
        } catch (Exception $e) {
            return $this->error('Erro ao salvar odontograma: ' . $e->getMessage());
        }
    }

    /**
     * Adiciona uma evolução clínica
     *
     * @param int $petId
     * @param array $data
     * @return array
     */
    public function addEvolucao(int $petId, array $data): array
    {
        try {
            $insertData = [
                'paciente_id'   => $petId,
                'veterinario_id'   => $data['veterinario_id'] ?? null,
                'data_registro' => date('Y-m-d H:i:s'),
                'titulo'        => $data['titulo'] ?? 'Evolução Clínica',
                'descricao'     => $data['descricao']
            ];
            
            $id = $this->evolucoesRepo->create($insertData);
            return $this->success('Evolução registrada com sucesso', ['id' => $id]);
        } catch (Exception $e) {
            return $this->error('Erro ao registrar evolução: ' . $e->getMessage());
        }
    }

    /**
     * Edita uma evolução clínica
     *
     * @param int $evolucaoId
     * @param array $data
     * @return array
     */
    public function editEvolucao(int $evolucaoId, array $data): array
    {
        try {
            $updateData = [
                'titulo'        => $data['titulo'],
                'descricao'     => $data['descricao']
            ];

            // If a veterinario_id is provided, optionally we could update it, but typically 
            // the original creator or current user ID is kept. Let's update it if passed.
            if (!empty($data['veterinario_id'])) {
                $updateData['veterinario_id'] = $data['veterinario_id'];
            }
            
            $this->evolucoesRepo->update($evolucaoId, $updateData);
            return $this->success('Evolução atualizada com sucesso');
        } catch (Exception $e) {
            return $this->error('Erro ao atualizar evolução: ' . $e->getMessage());
        }
    }

    /**
     * Deleta uma evolução clínica
     *
     * @param int $evolucaoId
     * @return array
     */
    public function deleteEvolucao(int $evolucaoId): array
    {
        try {
            $this->evolucoesRepo->delete($evolucaoId);
            return $this->success('Evolução removida com sucesso');
        } catch (Exception $e) {
            return $this->error('Erro ao remover evolução: ' . $e->getMessage());
        }
    }

    /**
     * Adiciona uma vacina ao prontuário
     *
     * @param int $petId
     * @param array $data
     * @return array
     */
    public function addVacina(int $petId, array $data): array
    {
        try {
            $insertData = [
                'paciente_id'       => $petId,
                'vacina_nome'       => $data['vacina_nome'],
                'data_aplicacao'    => $data['data_aplicacao'],
                'data_proxima_dose' => !empty($data['data_proxima_dose']) ? $data['data_proxima_dose'] : null,
                'lote'              => $data['lote'] ?? null,
                'fabricante'        => $data['fabricante'] ?? null,
                'observacoes'       => $data['observacoes'] ?? null,
            ];

            $id = $this->vacinasRepo->create($insertData);
            return $this->success('Vacina registrada com sucesso', ['id' => $id]);
        } catch (Exception $e) {
            return $this->error('Erro ao registrar vacina: ' . $e->getMessage());
        }
    }

    /**
     * Remove uma vacina
     *
     * @param int $id
     * @return array
     */
    public function deleteVacina(int $id): array
    {
        try {
            $this->vacinasRepo->delete($id);
            return $this->success('Vacina removida com sucesso');
        } catch (Exception $e) {
            return $this->error('Erro ao remover vacina: ' . $e->getMessage());
        }
    }

    /**
     * Adiciona um registro de peso
     *
     * @param int $petId
     * @param array $data
     * @return array
     */
    public function addPeso(int $petId, array $data): array
    {
        try {
            $insertData = [
                'paciente_id'  => $petId,
                'peso'         => (float) $data['peso'],
                'data_pesagem' => $data['data_pesagem'] ?? date('Y-m-d'),
                'observacoes'  => $data['observacoes'] ?? null,
            ];

            $id = $this->pesosRepo->create($insertData);
            return $this->success('Peso registrado com sucesso', ['id' => $id]);
        } catch (Exception $e) {
            return $this->error('Erro ao registrar peso: ' . $e->getMessage());
        }
    }

    /**
     * Remove um registro de peso
     *
     * @param int $id
     * @return array
     */
    public function deletePeso(int $id): array
    {
        try {
            $this->pesosRepo->delete($id);
            return $this->success('Registro de peso removido com sucesso');
        } catch (Exception $e) {
            return $this->error('Erro ao remover peso: ' . $e->getMessage());
        }
    }

    /**
     * Adiciona uma imagem ao prontuário
     *
     * @param int $petId
     * @param array $data
     * @return array
     */
    public function addImagem(int $petId, array $data): array
    {
        try {
            $insertData = [
                'paciente_id' => $petId,
                'titulo'      => $data['titulo'],
                'arquivo_url' => $data['arquivo_url'],
                'data_exame'  => $data['data_exame'] ?? date('Y-m-d')
            ];
            
            $id = $this->imagensRepo->create($insertData);
            return $this->success('Imagem registrada com sucesso', ['id' => $id]);
        } catch (Exception $e) {
            return $this->error('Erro ao registrar imagem: ' . $e->getMessage());
        }
    }

    /**
     * Retorna o histórico combinado de agendamentos e procedimentos
     *
     * @param int $petId
     * @return array
     */
    private function getCombinedHistory(int $petId): array
    {
        // Fetch appointments using repository method for service name join
        $appts = $this->agendamentosRepo->findByPet($petId);

        // Fetch billing/procedures with appointment observations
        $bills = $this->fatCobrancaRepo->findByPet($petId);

        return [
            'agendamentos' => $appts,
            'procedimentos' => $bills
        ];
    }

    /**
     * Calcula estatísticas do prontuário a partir dos dados já carregados.
     * Evita múltiplas queries redundantes de COUNT/SUM ao abrir o prontuário.
     *
     * @param array $imagens
     * @param array $historico
     * @return array
     */
    private function calculateStats(array $imagens, array $historico): array
    {
        $consultasCount = count($historico['agendamentos'] ?? []);
        $imagensCount   = count($imagens);

        $totalInvestido = 0.0;
        if (!empty($historico['procedimentos'])) {
            foreach ($historico['procedimentos'] as $proc) {
                $totalInvestido += (float) ($proc['valor'] ?? 0.0);
            }
        }

        return [
            'consultas'       => $consultasCount,
            'imagens'         => $imagensCount,
            'total_investido' => $totalInvestido
        ];
    }
    
    /**
     * Helper to update anamnese in Pets
     *
     * @param int $petId
     * @param array $data
     * @return array
     */
    public function updateAnamnese(int $petId, array $data): array
    {
        try {
            $result = $this->petsRepo->update($petId, $data);
            if ($result) {
                return $this->success('Anamnese atualizada com sucesso');
            }
            return $this->error('Erro ao atualizar anamnese');
        } catch (Exception $e) {
            return $this->error('Erro: ' . $e->getMessage());
        }
    }
}
