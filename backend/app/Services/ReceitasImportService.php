<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\FatCobrancaRepository;
use App\Repositories\FatNotaRepository;
use App\Repositories\PetsRepository;
use App\Repositories\ClientesRepository;
use App\Models\ServicosModel;
use Exception;

/**
 * @property FatCobrancaRepository $repository
 */
class ReceitasImportService extends BaseService
{
    protected PetsRepository $petsRepository;
    protected ClientesRepository $clientesRepository;
    protected FatNotaRepository $notaRepository;

    /**
     * @param FatCobrancaRepository|null $repository
     * @param PetsRepository|null $petsRepository
     * @param ClientesRepository|null $clientesRepository
     * @param FatNotaRepository|null $notaRepository
     */
    public function __construct(
        ?FatCobrancaRepository $repository = null,
        ?PetsRepository $petsRepository = null,
        ?ClientesRepository $clientesRepository = null,
        ?FatNotaRepository $notaRepository = null
    ) {
        $this->repository = $repository ?? new FatCobrancaRepository();
        $this->petsRepository = $petsRepository ?? new PetsRepository();
        $this->clientesRepository = $clientesRepository ?? new ClientesRepository();
        $this->notaRepository = $notaRepository ?? new FatNotaRepository();
    }

    /**
     * Importa receitas a partir de um arquivo CSV do Sispet.
     * 
     * @param string $filePath Caminho para o arquivo CSV temporário
     * @return array Resumo da importação
     */
    public function importFromCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return $this->error('Não foi possível abrir o arquivo.');
        }

        // Delimitador padrão (;) conforme Sispet
        $delimiter = ';';

        // Ler cabeçalho
        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            return $this->error('Arquivo CSV vazio ou inválido.');
        }

        // Limpar cabeçalho (BOM e espaços)
        $header = array_map(function($h) {
            // Remove BOM UTF-8 se presente
            $h = preg_replace('/^\xEF\xBB\xBF/', '', (string)$h);
            return trim((string)$h);
        }, $header);

        $mapping = $this->getMapping($header);
        
        $importedCount = 0;
        $ignored = [];
        $errors = [];
        $rowCount = 0;

        // Dicionários de mapeamento conforme solicitado pelo usuário
        $dicServicos = [
            'Receita c/ venda de serviços' => 'Banho/Tosa',
        ];

        $dicFormas = [
            'pix/ transf./ depós.' => 'pix',
        ];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowCount++;
            $rawData = [];
            
            // Cruzar colunas do CSV com o cabeçalho mapeado
            foreach ($mapping as $csvIndex => $dbField) {
                if (isset($row[$csvIndex])) {
                    $rawData[$dbField] = trim($row[$csvIndex]);
                }
            }

            // Precisamos identificar o Pet e o Cliente (Tutor)
            $clienteNome = '';
            $petNome = '';
            
            $clienteIdx = array_search('Cliente', $header, true);
            $petIdx = array_search('Pet', $header, true);

            if ($clienteIdx !== false && isset($row[$clienteIdx])) {
                $clienteNome = trim($row[$clienteIdx]);
            }
            if ($petIdx !== false && isset($row[$petIdx])) {
                $petNome = trim($row[$petIdx]);
            }

            if (empty($clienteNome) || empty($petNome)) {
                $errors[] = "Linha $rowCount: Cliente ou Pet não informados.";
                continue;
            }

            // Buscar Cliente
            $cliente = $this->clientesRepository->findByName($clienteNome);
            if (!$cliente) {
                $ignored[] = [
                    'cliente' => $clienteNome,
                    'pet' => $petNome,
                    'motivo' => 'Cliente não encontrado'
                ];
                continue;
            }

            // Buscar Pet vinculado ao Cliente
            $pet = $this->petsRepository->findByNomeAndCliente($petNome, (int)$cliente['id']);
            if (!$pet) {
                $ignored[] = [
                    'cliente' => $clienteNome,
                    'pet' => $petNome,
                    'motivo' => 'Pet não encontrado para este cliente'
                ];
                continue;
            }

            $petId = (int)$pet['paciente_id'];

            // Preparar dados para inserção
            $statusRaw = $rawData['status'] ?? '';
            $dbData = [
                'paciente_id' => $petId,
                'status' => (mb_strtolower($statusRaw) === 'pago') ? 'Pago' : 'Pendente',
                'valor'  => $this->parseMoeda($rawData['valor'] ?? '0'),
                'desconto' => $this->parseMoeda($rawData['desconto'] ?? '0'),
                'vencimento' => !empty($rawData['vencimento'] ?? '') ? $this->parseDate($rawData['vencimento']) : null,
                'data_servico' => !empty($rawData['data_servico'] ?? '') ? $this->parseDate($rawData['data_servico']) : date('Y-m-d'),
                'observacoes' => $rawData['observacoes'] ?? '',
                'parcelas' => 1
            ];

            // Aplicar Dicionário de Serviços
            $servicoOriginal = $rawData['servico'] ?? 'Serviço Importado';
            $dbData['servico'] = $dicServicos[$servicoOriginal] ?? $servicoOriginal;

            // Aplicar Dicionário de Formas de Pagamento
            $formaOriginal = $rawData['forma_pagamento'] ?? 'Outros';
            $dbData['forma_pagamento'] = $dicFormas[$formaOriginal] ?? $formaOriginal;

            // Verificar Duplicidade (mesmo pet, data, valor e serviço)
            if ($this->checkDuplicate($dbData)) {
                $ignored[] = [
                    'cliente' => $clienteNome,
                    'pet' => $petNome,
                    'motivo' => 'Provável duplicado (já existe lançamento idêntico)'
                ];
                continue;
            }

            try {
                $id = $this->repository->create($dbData);
                if ($id) {
                    $importedCount++;

                    // Criar Nota/Recibo se estiver pago para contabilizar no financeiro
                    if ($dbData['status'] === 'Pago') {
                        $notaData = [
                            'cobranca_id'     => $id,
                            'paciente_id'     => $petId,
                            'tipo'            => 'Recibo',
                            'cpf_responsavel' => $cliente['cpf'] ?? '',
                            'data_emissao'    => $dbData['data_servico'],
                            'descricao'       => $dbData['servico'],
                            'valor'           => $dbData['valor']
                        ];
                        $this->notaRepository->create($notaData);
                    }
                } else {
                    $errors[] = "Linha $rowCount ($petNome): Erro ao salvar banco.";
                }
            } catch (Exception $e) {
                $errors[] = "Linha $rowCount ($petNome): " . $e->getMessage();
            }
        }

        fclose($handle);

        return $this->success('Importação concluída.', [
            'total_processado' => $rowCount,
            'importados' => $importedCount,
            'ignorados' => $ignored,
            'erros' => $errors
        ]);
    }

    /**
     * Mapeia as colunas do CSV para chaves temporárias para o DB.
     *
     * @param array $header
     * @return array
     */
    private function getMapping(array $header): array
    {
        $map = [
            'Situação'            => 'status',
            'Planos de contas III'=> 'servico',
            'Valor documento'     => 'valor',
            'Desconto'            => 'desconto',
            'Vencimento'          => 'vencimento',
            'Pagamento'           => 'data_servico',
            'Forma'               => 'forma_pagamento',
            'Detalhes'            => 'observacoes',
        ];

        $mapping = [];
        foreach ($header as $index => $label) {
            foreach ($map as $csvLabel => $dbField) {
                if (mb_strtolower((string)$label) === mb_strtolower($csvLabel)) {
                    $mapping[$index] = $dbField;
                    break;
                }
            }
        }

        return $mapping;
    }

    /**
     * Converte "50,00" ou "50" para 50.00
     *
     * @param string $val
     * @return float
     */
    private function parseMoeda(string $val): float
    {
        $val = str_replace('.', '', $val); // Remove separador de milhar se houver
        $val = str_replace(',', '.', $val); // Converte decimal
        return (float) $val;
    }

    /**
     * Converte data de dd/mm/yyyy para yyyy-mm-dd
     *
     * @param string $date
     * @return string
     */
    private function parseDate(string $date): string
    {
        $date = str_replace(['/', '.'], '-', $date);
        $parts = explode('-', $date);
        
        if (count($parts) === 3) {
            if (strlen($parts[2]) === 4) { // dd-mm-yyyy
                return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            }
        }
        
        return $date;
    }

    /**
     * Verifica duplicidade básica.
     *
     * @param array $data
     * @return bool
     */
    private function checkDuplicate(array $data): bool
    {
        return $this->repository->getModel()
            ->where('paciente_id', $data['paciente_id'])
            ->where('data_servico', $data['data_servico'])
            ->where('valor', $data['valor'])
            ->where('servico', $data['servico'])
            ->first() !== null;
    }
}
