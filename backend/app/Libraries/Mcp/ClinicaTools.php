<?php

declare(strict_types=1);

namespace App\Libraries\Mcp;

use App\Services\AgendaService;
use App\Services\ClientesService;
use App\Services\PetsService;
use App\Services\ServicosService;

/**
 * Ferramentas MCP da clínica: incluir cliente (tutor), incluir paciente (pet)
 * e agendar — mais três consultas auxiliares (buscar cliente, buscar paciente
 * e listar serviços) para que o assistente encontre os IDs necessários.
 *
 * Toda escrita passa pelos mesmos Services usados pela API REST/React, então
 * as regras de negócio (ex.: CPF duplicado) valem igualmente aqui.
 */
class ClinicaTools
{
    private ?ClientesService $clientes;
    private ?PetsService $pets;
    private ?AgendaService $agenda;
    private ?ServicosService $servicos;

    public function __construct(
        ?ClientesService $clientes = null,
        ?PetsService $pets = null,
        ?AgendaService $agenda = null,
        ?ServicosService $servicos = null
    ) {
        $this->clientes = $clientes;
        $this->pets     = $pets;
        $this->agenda   = $agenda;
        $this->servicos = $servicos;
    }

    public static function instrucoes(): string
    {
        return 'Sistema de gestão de clínica veterinária. Fluxo típico: buscar_clientes (evita duplicar) -> '
            . 'incluir_cliente -> incluir_paciente (precisa do cliente_id) -> listar_servicos -> agendar '
            . '(precisa do paciente_id e de ao menos um servico_id). Datas em AAAA-MM-DD e horas em HH:MM.';
    }

    public function register(McpServer $server): void
    {
        $server->registerTool(
            'incluir_cliente',
            'Cadastra um novo cliente (tutor/responsável). Antes de chamar, use buscar_clientes para não duplicar. Retorna o id do cliente.',
            [
                'type'       => 'object',
                'properties' => [
                    'nome'       => ['type' => 'string', 'description' => 'Nome completo (mín. 3 caracteres).'],
                    'cpf'        => ['type' => 'string', 'description' => 'CPF válido, com ou sem pontuação.'],
                    'cnpj'       => ['type' => 'string', 'description' => 'CNPJ válido (pessoa jurídica).'],
                    'telefones'  => ['type' => 'string', 'description' => 'Telefone(s) com DDD, ex.: (11) 99999-0000.'],
                    'emails'     => ['type' => 'string', 'description' => 'E-mail(s) de contato.'],
                    'nascimento' => ['type' => 'string', 'description' => 'Data de nascimento AAAA-MM-DD.'],
                    'rua'        => ['type' => 'string'],
                    'numero'     => ['type' => 'string'],
                    'complemento' => ['type' => 'string'],
                    'bairro'     => ['type' => 'string'],
                    'cep'        => ['type' => 'string'],
                    'cidade'     => ['type' => 'string'],
                    'estado'     => ['type' => 'string', 'description' => 'UF, ex.: SP.'],
                    'observacoes' => ['type' => 'string'],
                ],
                'required'   => ['nome'],
            ],
            fn (array $a): array => $this->incluirCliente($a)
        );

        $server->registerTool(
            'incluir_paciente',
            'Cadastra um novo paciente (animal) vinculado a um cliente já existente. Retorna o id do paciente.',
            [
                'type'       => 'object',
                'properties' => [
                    'cliente_id' => ['type' => 'integer', 'description' => 'ID do cliente (tutor) dono do paciente.'],
                    'nome'       => ['type' => 'string', 'description' => 'Nome do paciente.'],
                    'especie'    => ['type' => 'string', 'description' => 'Ex.: Cão, Gato.'],
                    'raca'       => ['type' => 'string'],
                    'sexo'       => ['type' => 'string', 'enum' => ['Fêmea', 'Macho', 'Outro']],
                    'nascimento' => ['type' => 'string', 'description' => 'Data de nascimento AAAA-MM-DD.'],
                    'porte'      => ['type' => 'string'],
                    'observacoes' => ['type' => 'string', 'description' => 'Observações de saúde / características.'],
                ],
                'required'   => ['cliente_id', 'nome'],
            ],
            fn (array $a): array => $this->incluirPaciente($a)
        );

        $server->registerTool(
            'agendar',
            'Cria um agendamento para um paciente existente. Use listar_servicos para obter os servico_ids.',
            [
                'type'       => 'object',
                'properties' => [
                    'paciente_id' => ['type' => 'integer'],
                    'data'        => ['type' => 'string', 'description' => 'Data AAAA-MM-DD.'],
                    'hora'        => ['type' => 'string', 'description' => 'Hora HH:MM (24h).'],
                    'servico_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'minItems' => 1, 'description' => 'IDs dos serviços.'],
                    'duracao_minutos' => ['type' => 'integer', 'description' => 'Duração em minutos (padrão 30).'],
                    'observacoes' => ['type' => 'string'],
                    'status'      => ['type' => 'string', 'enum' => ['pendente', 'confirmado'], 'description' => 'Padrão: pendente.'],
                ],
                'required'   => ['paciente_id', 'data', 'hora', 'servico_ids'],
            ],
            fn (array $a): array => $this->agendar($a)
        );

        $server->registerTool(
            'buscar_clientes',
            'Busca clientes ativos por nome, CPF, telefone etc. Retorna até 20 resultados.',
            ['type' => 'object', 'properties' => ['termo' => ['type' => 'string']], 'required' => ['termo']],
            fn (array $a): array => $this->buscarClientes($a)
        );

        $server->registerTool(
            'buscar_pacientes',
            'Busca pacientes (animais) por nome ou nome do tutor. Retorna até 20 resultados com paciente_id.',
            ['type' => 'object', 'properties' => ['termo' => ['type' => 'string']], 'required' => ['termo']],
            fn (array $a): array => $this->buscarPacientes($a)
        );

        $server->registerTool(
            'listar_servicos',
            'Lista os serviços ativos da clínica (id, nome, valor) para usar em agendar.',
            ['type' => 'object', 'properties' => new \stdClass()],
            fn (array $a): array => $this->listarServicos()
        );
    }

    // ------------------------------------------------------------------

    public function incluirCliente(array $a): array
    {
        $data = $this->only($a, [
            'nome', 'cpf', 'cnpj', 'telefones', 'emails', 'nascimento', 'rua', 'numero',
            'complemento', 'bairro', 'cep', 'cidade', 'estado', 'observacoes',
        ]);

        $this->validar($data, [
            'nome'       => 'required|min_length[3]|max_length[255]',
            'cpf'        => 'permit_empty|max_length[14]|valid_cpf',
            'cnpj'       => 'permit_empty|max_length[18]|valid_cnpj',
            'nascimento' => 'permit_empty|valid_date[Y-m-d]',
        ]);

        $result = $this->clientesService()->create($data);

        return $this->resultado($result, 'cliente_id');
    }

    public function incluirPaciente(array $a): array
    {
        $data = [
            'cliente_id'               => $a['cliente_id'] ?? null,
            'paciente_nome'            => $a['nome'] ?? null,
            'paciente_especie'         => $a['especie'] ?? null,
            'paciente_raca'            => $a['raca'] ?? null,
            'paciente_sexo'            => $a['sexo'] ?? null,
            'paciente_nascimento'      => ($a['nascimento'] ?? '') !== '' ? $a['nascimento'] : null,
            'paciente_porte'           => $a['porte'] ?? null,
            'paciente_saude_obs'       => $a['observacoes'] ?? null,
            'paciente_status'          => 'Ativo',
        ];
        $data = array_filter($data, static fn ($v) => $v !== null && $v !== '');

        $this->validar($data, [
            'paciente_nome'       => 'required|min_length[2]|max_length[255]',
            'cliente_id'          => 'required|is_natural_no_zero',
            'paciente_sexo'       => 'permit_empty|in_list[Fêmea,Macho,Outro]',
            'paciente_nascimento' => 'permit_empty|valid_date[Y-m-d]',
        ]);

        if (!$this->clientesService()->findById((int) $data['cliente_id'])) {
            throw new ToolException('Cliente não encontrado.', ['cliente_id' => 'Nenhum cliente com este ID.']);
        }

        $result = $this->petsService()->create($data);

        return $this->resultado($result, 'paciente_id');
    }

    public function agendar(array $a): array
    {
        $servicos = array_values(array_filter(
            array_map('intval', (array) ($a['servico_ids'] ?? [])),
            static fn (int $id) => $id > 0
        ));

        $hora = (string) ($a['hora'] ?? '');
        if (preg_match('/^\d{2}:\d{2}$/', $hora) === 1) {
            $hora .= ':00';
        }

        $check = [
            'paciente_id' => $a['paciente_id'] ?? null,
            'data'        => $a['data'] ?? null,
            'hora'        => $hora,
            'servicos'    => $servicos === [] ? '' : 'ok',
            'status'      => $a['status'] ?? 'pendente',
            'duracao'     => $a['duracao_minutos'] ?? 30,
        ];
        $this->validar($check, [
            'paciente_id' => 'required|is_natural_no_zero',
            'data'        => 'required|valid_date[Y-m-d]',
            'hora'        => 'required|regex_match[/^([01]\d|2[0-3]):[0-5]\d:00$/]',
            'servicos'    => 'required',
            'status'      => 'in_list[pendente,confirmado]',
            'duracao'     => 'is_natural_no_zero',
        ], ['servicos' => ['required' => 'Informe ao menos um servico_id (veja listar_servicos).']]);

        if (!$this->petsService()->findById((int) $a['paciente_id'])) {
            throw new ToolException('Paciente não encontrado.', ['paciente_id' => 'Nenhum paciente com este ID.']);
        }

        $result = $this->agendaService()->create([
            'paciente_id' => (int) $a['paciente_id'],
            'age_data'    => $a['data'],
            'age_hora'    => $hora,
            'age_duracao' => (string) $check['duracao'],
            'age_status'  => $check['status'],
            'age_obs'     => $a['observacoes'] ?? null,
            'age_servico' => $servicos,
        ]);

        return $this->resultado($result, 'agendamento_id');
    }

    public function buscarClientes(array $a): array
    {
        $rows = $this->clientesService()->search('ativos', null, trim((string) ($a['termo'] ?? '')) ?: null);

        return ['total' => count($rows), 'clientes' => array_map(
            static fn (array $c) => array_intersect_key($c, array_flip(['id', 'nome', 'cpf', 'cnpj', 'telefones', 'emails'])),
            array_slice($rows, 0, 20)
        )];
    }

    public function buscarPacientes(array $a): array
    {
        $rows = $this->petsService()->search(trim((string) ($a['termo'] ?? '')));

        return ['total' => count($rows), 'pacientes' => array_map(
            static fn (array $p) => array_intersect_key($p, array_flip([
                'paciente_id', 'paciente_nome', 'paciente_especie', 'paciente_raca', 'cliente_id', 'pet_resp_nome', 'nome',
            ])),
            array_slice($rows, 0, 20)
        )];
    }

    public function listarServicos(): array
    {
        $rows = $this->servicosService()->getAllActive();

        return ['servicos' => array_map(
            static fn (array $s) => array_intersect_key($s, array_flip(['ser_id', 'ser_nome', 'ser_valor'])),
            $rows
        )];
    }

    // ------------------------------------------------------------------

    private function only(array $input, array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            if (isset($input[$k]) && is_scalar($input[$k]) && trim((string) $input[$k]) !== '') {
                $out[$k] = trim((string) $input[$k]);
            }
        }

        return $out;
    }

    private function validar(array $data, array $rules, array $messages = []): void
    {
        $validation = \Config\Services::validation(null, false);
        $validation->setRules($rules, $messages);

        if (!$validation->run($data)) {
            throw new ToolException('Dados inválidos.', $validation->getErrors());
        }
    }

    private function resultado(array $result, string $idKey): array
    {
        if (($result['status'] ?? '') !== 'success') {
            throw new ToolException((string) ($result['message'] ?? 'Não foi possível concluir a operação.'));
        }

        $out = ['message' => $result['message'] ?? 'OK'];
        if (isset($result['id'])) {
            $out[$idKey] = (int) $result['id'];
        }
        if (isset($result['ids'])) {
            $out['ids'] = $result['ids'];
        }

        return $out;
    }

    private function clientesService(): ClientesService
    {
        return $this->clientes ??= new ClientesService();
    }

    private function petsService(): PetsService
    {
        return $this->pets ??= new PetsService();
    }

    private function agendaService(): AgendaService
    {
        return $this->agenda ??= new AgendaService();
    }

    private function servicosService(): ServicosService
    {
        return $this->servicos ??= new ServicosService();
    }
}
