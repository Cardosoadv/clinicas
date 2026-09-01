<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Services\PetsService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller responsável pela gestão do cadastro de pacientes (pets).
 */
class Pets extends BaseController
{
    protected PetsService $service;

    public function __construct(?PetsService $service = null)
    {
        $this->service = $service ?? new PetsService();
    }

    /**
     * API: Lista pacientes filtrados por status (Ativo/Inativo/Suspenso/vazio para
     * todos), espécie e um termo de busca livre — todos opcionais via querystring.
     */
    public function getAll(): ResponseInterface
    {
        $status  = (string) ($this->request->getGet('status') ?? '');
        $especie = $this->request->getGet('especie') ?: null;
        $term    = $this->request->getGet('search') ?: null;

        $data = $this->service->listFiltered($status, $especie, $term);
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Retorna os dados do painel lateral (estatísticas, adições recentes,
     * aniversariantes do mês e espécies cadastradas) da tela de Pacientes.
     */
    public function getPainel(): ResponseInterface
    {
        $data = $this->service->getPainelData();
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Busca pacientes por um termo (nome, CPF do tutor, etc).
     */
    public function search(): ResponseInterface
    {
        $term = (string) $this->request->getGet('term');
        $data = $this->service->search($term);
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Retorna os dados detalhados de um paciente pelo ID.
     */
    public function getById(int $id): ResponseInterface
    {
        $pet = $this->service->findById($id);
        if (!$pet) {
            return $this->apiNotFound('Paciente não encontrado.');
        }
        return $this->apiResponse(['status' => 'success', 'data' => $pet]);
    }

    /**
     * API: Cria um novo paciente vinculado a um tutor já existente.
     */
    public function create(): ResponseInterface
    {
        $data = $this->getRequestData();

        // Ensure empty dates are treated as null
        if (isset($data['paciente_nascimento']) && trim((string)$data['paciente_nascimento']) === '') {
            $data['paciente_nascimento'] = null;
        }

        if (!$this->validateData($data, $this->getValidationRules())) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->create($data);
        return $this->apiResponse($result, 201);
    }

    /**
     * API: Cria um novo tutor (se necessário), paciente e agendamento em um único fluxo.
     */
    public function mixCreate(): ResponseInterface
    {
        $data = $this->getRequestData();
        $result = $this->service->mixCreate($data);
        return $this->apiResponse($result, 201);
    }

    /**
     * API: Atualiza os dados de um paciente existente.
     */
    public function update(int $id): ResponseInterface
    {
        $data = $this->getRequestData();

        // Ensure empty dates are treated as null
        if (isset($data['paciente_nascimento']) && trim((string)$data['paciente_nascimento']) === '') {
            $data['paciente_nascimento'] = null;
        }

        if (!$this->validateData($data, $this->getValidationRules())) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->update($id, $data);
        return $this->apiResponse($result);
    }

    /**
     * API: Exclui o registro de um paciente.
     */
    public function delete(int $id): ResponseInterface
    {
        $result = $this->service->delete($id);
        return $this->apiResponse($result);
    }

    /**
     * Serve uma imagem de avatar do paciente de forma segura.
     */
    public function viewAvatar(int $petId, string $filename): ResponseInterface
    {
        $filename = basename($filename);
        $path = WRITEPATH . "avatar/{$petId}/{$filename}";

        if (!file_exists($path)) {
            return $this->apiNotFound('Avatar não encontrado.');
        }

        $file = new \CodeIgniter\Files\File($path);

        return $this->response
            ->setHeader('Content-Type', $file->getMimeType())
            ->setBody(file_get_contents($path));
    }

    /**
     * Todo paciente precisa estar vinculado a um cliente (tutor) já cadastrado.
     */
    private function getValidationRules(): array
    {
        return [
            'paciente_nome'   => 'required|min_length[2]|max_length[255]',
            'cliente_id'      => 'required|is_natural_no_zero|is_not_unique[clientes.id]',
            'paciente_sexo'   => 'permit_empty|in_list[Fêmea,Macho,Outro]',
            'paciente_status' => 'permit_empty|in_list[Ativo,Inativo,Suspenso]',
        ];
    }
}
