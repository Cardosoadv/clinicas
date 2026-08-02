<?php

declare(strict_types=1);

namespace App\Controllers\V1;

use App\Controllers\BaseController;
use App\Services\PrescricoesService;
use App\Services\ProntuariosService;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

/**
 * Controller responsável pela gestão dos prontuários dos pacientes.
 * Inclui anamnese, odontograma, evoluções, vacinas, pesos, imagens e prescrições.
 */
class Prontuarios extends BaseController
{
    protected ProntuariosService $service;

    public function __construct(?ProntuariosService $service = null)
    {
        $this->service = $service ?? new ProntuariosService();
    }

    /**
     * API: Lista pacientes para seleção de prontuário (com busca opcional).
     */
    public function listPacientes(): ResponseInterface
    {
        $term = $this->request->getGet('search');
        $data = $this->service->getPetsList($term);
        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }

    /**
     * API: Retorna o prontuário completo de um paciente.
     */
    public function show(int $petId): ResponseInterface
    {
        try {
            $data = $this->service->getFullRecord($petId);
            return $this->apiResponse(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            return $this->apiNotFound($e->getMessage());
        }
    }

    /**
     * API: Atualiza a ficha de anamnese do paciente. Aceita multipart/form-data (avatar opcional).
     */
    public function saveAnamnese(int $petId): ResponseInterface
    {
        $data = $this->request->getPost([
            'paciente_nome', 'paciente_nascimento', 'paciente_sexo', 'paciente_especie',
            'paciente_avatar', 'paciente_endereco', 'paciente_queixa', 'paciente_saude_obs',
            'paciente_status',
        ]);

        $rules = [
            'paciente_nome'       => 'required|min_length[2]|max_length[255]',
            'paciente_nascimento' => 'required|valid_date[Y-m-d]',
            'paciente_sexo'       => 'required|in_list[Macho,Fêmea]',
            'paciente_especie'    => 'required|in_list[Canina,Felina,Exóticos]',
            'paciente_status'     => 'permit_empty|in_list[Ativo,Inativo,Suspenso]',
            'avatar' => [
                'label' => 'Foto do Paciente',
                'rules' => [
                    'permit_empty',
                    'is_image[avatar]',
                    'mime_in[avatar,image/png,image/jpg,image/jpeg,image/webp]',
                    'ext_in[avatar,png,jpg,jpeg,webp]',
                    'max_size[avatar,2048]',
                ],
            ],
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $file = $this->request->getFile('avatar');

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $uploadPath = WRITEPATH . "avatar/{$petId}";

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $file->move($uploadPath, $newName);
            $data['paciente_avatar'] = "avatar/{$petId}/{$newName}";
        }

        $result = $this->service->updateAnamnese($petId, $data);
        return $this->apiResponse($result);
    }

    /**
     * API: Salva o estado atual do mapa do odontograma.
     */
    public function saveOdontograma(int $petId): ResponseInterface
    {
        $data = $this->getRequestData();

        $rules = [
            'mapa'        => 'required',
            'observacoes' => 'permit_empty|max_length[1000]',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        if (isset($data['mapa']) && is_string($data['mapa'])) {
            $data['mapa'] = json_decode($data['mapa'], true);
        }

        $result = $this->service->saveOdontograma($petId, $data);
        return $this->apiResponse($result, 201);
    }

    /**
     * API: Adiciona uma nova nota de evolução clínica.
     */
    public function addEvolucao(int $petId): ResponseInterface
    {
        $data = $this->getRequestData();

        $rules = [
            'titulo'         => 'required|min_length[3]|max_length[255]',
            'descricao'      => 'required|min_length[5]',
            'veterinario_id' => 'permit_empty|is_natural_no_zero',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        if (empty($data['veterinario_id']) && function_exists('auth')) {
            $data['veterinario_id'] = auth()->id();
        }

        $result = $this->service->addEvolucao($petId, $data);
        return $this->apiResponse($result, 201);
    }

    /**
     * API: Edita uma evolução clínica existente.
     */
    public function editEvolucao(int $evolucaoId): ResponseInterface
    {
        $data = $this->getRequestData();

        $rules = [
            'titulo'         => 'required|min_length[3]|max_length[255]',
            'descricao'      => 'required|min_length[5]',
            'veterinario_id' => 'permit_empty|is_natural_no_zero',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        if (empty($data['veterinario_id']) && function_exists('auth')) {
            $data['veterinario_id'] = auth()->id();
        }

        $result = $this->service->editEvolucao($evolucaoId, $data);
        return $this->apiResponse($result);
    }

    /**
     * API: Remove uma evolução clínica.
     */
    public function deleteEvolucao(int $evolucaoId): ResponseInterface
    {
        $result = $this->service->deleteEvolucao($evolucaoId);
        return $this->apiResponse($result);
    }

    /**
     * API: Adiciona uma nova vacina ao histórico do paciente.
     */
    public function addVacina(int $petId): ResponseInterface
    {
        $data = $this->getRequestData();

        $rules = [
            'vacina_nome'       => 'required|min_length[3]|max_length[255]',
            'data_aplicacao'    => 'required|valid_date',
            'data_proxima_dose' => 'permit_empty|valid_date',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->addVacina($petId, $data);
        return $this->apiResponse($result, 201);
    }

    /**
     * API: Remove uma vacina.
     */
    public function deleteVacina(int $id): ResponseInterface
    {
        $result = $this->service->deleteVacina($id);
        return $this->apiResponse($result);
    }

    /**
     * API: Adiciona um novo registro de peso ao paciente.
     */
    public function addPeso(int $petId): ResponseInterface
    {
        $data = $this->getRequestData();

        $rules = [
            'peso'         => 'required|decimal',
            'data_pesagem' => 'required|valid_date',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $result = $this->service->addPeso($petId, $data);
        return $this->apiResponse($result, 201);
    }

    /**
     * API: Remove um registro de peso.
     */
    public function deletePeso(int $id): ResponseInterface
    {
        $result = $this->service->deletePeso($id);
        return $this->apiResponse($result);
    }

    /**
     * API: Realiza o upload e registro de uma nova imagem/exame.
     * Armazena em pasta segura fora do root público.
     */
    public function addImagem(int $petId): ResponseInterface
    {
        $rules = [
            'arquivo' => [
                'label' => 'Arquivo de Imagem/Radiografia',
                'rules' => [
                    'uploaded[arquivo]',
                    'is_image[arquivo]',
                    'mime_in[arquivo,image/png,image/jpg,image/jpeg,image/webp]',
                    'ext_in[arquivo,png,jpg,jpeg,webp]',
                    'max_size[arquivo,10240]',
                ],
            ],
            'titulo' => 'required|min_length[3]|max_length[255]',
        ];

        $data = $this->request->getPost(['titulo', 'data_exame']);

        if (!$this->validateData($data, $rules)) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $file = $this->request->getFile('arquivo');

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $uploadPath = WRITEPATH . 'imagens/' . $petId;

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $file->move($uploadPath, $newName);
            $data['arquivo_url'] = $newName;
        }

        $result = $this->service->addImagem($petId, $data);
        return $this->apiResponse($result, 201);
    }

    /**
     * Serve uma imagem armazenada de forma segura.
     */
    public function viewImagem(int $petId, string $filename): ResponseInterface
    {
        $filename = basename($filename);
        $path = WRITEPATH . 'imagens/' . $petId . '/' . $filename;

        if (!file_exists($path)) {
            $legacyPath = FCPATH . 'uploads/radiografias/' . $filename;
            if (file_exists($legacyPath)) {
                $path = $legacyPath;
            } else {
                return $this->apiNotFound('Imagem não encontrada.');
            }
        }

        $file = new \CodeIgniter\Files\File($path);

        return $this->response
            ->setHeader('Content-Type', $file->getMimeType())
            ->setBody(file_get_contents($path));
    }

    /**
     * API: Cria uma nova prescrição estruturada.
     */
    public function addPrescricao(int $petId): ResponseInterface
    {
        $data = $this->getRequestData();

        $rules = [
            'data_prescricao' => 'required|valid_date[Y-m-d]',
            'itens'           => 'required',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->apiValidationError($this->validator->getErrors());
        }

        $data['paciente_id'] = $petId;

        if (is_string($data['itens'])) {
            $data['itens'] = json_decode($data['itens'], true);
        }

        if (function_exists('auth')) {
            $data['veterinario_id'] = auth()->id();
        }

        $result = (new PrescricoesService())->createPrescricao($data);
        return $this->apiResponse($result, 201);
    }

    /**
     * API: Retorna os detalhes de uma prescrição.
     */
    public function getPrescricao(int $id): ResponseInterface
    {
        $data = (new PrescricoesService())->getDetails($id);

        if (!$data) {
            return $this->apiNotFound('Prescrição não encontrada.');
        }

        return $this->apiResponse(['status' => 'success', 'data' => $data]);
    }
}
