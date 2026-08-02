<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\FatDespesaRepository;
use Exception;

/**
 * @property FatDespesaRepository $repository
 */
class DespesasImportService extends BaseService
{
    /**
     * @param FatDespesaRepository|null $repository
     */
    public function __construct(?FatDespesaRepository $repository = null)
    {
        $this->repository = $repository ?? new FatDespesaRepository();
    }

    /**
     * Importa despesas a partir de um arquivo CSV do Sispet.
     *
     * Cabeçalho esperado (delimitador ;):
     * Situação;Conta;Tipo documento;Vencimento;Competência;Pagamento;Contato;Razão;CPF;CNPJ;
     * Fones;Emails;Valor documento;Acréscimo;Desconto;Valor pago;Detalhes;Origem;Incluído por;
     * Unidade;Planos de contas I;Planos de contas II;Planos de contas III;Tipo de despesa
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

        $delimiter = ';';

        // Ler e limpar cabeçalho (remove BOM + espaços)
        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            return $this->error('Arquivo CSV vazio ou inválido.');
        }

        $header = array_map(function ($h) {
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h); // Remove BOM UTF-8
            return trim($h);
        }, $header);

        $mapping = $this->getMapping($header);

        $importedCount = 0;
        $ignored       = [];
        $errors        = [];
        $rowCount      = 0;

        $allProcessedData = [];
        $duplicatesMap = [];

        // Dicionário: forma de pagamento Sispet → sistema
        $dicFormas = [
            'pix/ transf./ depós.' => 'pix',
            'pix'                  => 'pix',
            'boleto'               => 'boleto',
            'dinheiro'             => 'dinheiro',
            'cartão de crédito'    => 'cartao_credito',
            'cartão de débito'     => 'cartao_debito',
        ];

        // 1ª Passagem: Ler CSV e preparar dados
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowCount++;

            // Ignorar linhas completamente vazias
            if (count(array_filter($row)) === 0) {
                continue;
            }

            $rawData = [];
            foreach ($mapping as $csvIndex => $dbField) {
                if (isset($row[$csvIndex])) {
                    $rawData[$dbField] = trim($row[$csvIndex]);
                }
            }

            // Validação básica: valor positivo
            $valorDoc = $this->parseMoeda($rawData['valor'] ?? '0');
            if ($valorDoc <= 0) {
                $ignored[] = [
                    'line'       => $rowCount,
                    'fornecedor' => $rawData['fornecedor'] ?? '—',
                    'detalhe'    => $rawData['descricao'] ?? '—',
                    'motivo'     => 'Valor inválido ou zerado',
                ];
                continue;
            }

            // Montar dados para fat_despesas
            $statusRaw = $rawData['status'] ?? '';
            $status    = (mb_strtolower($statusRaw) === 'pago') ? 'Pago' : 'Pendente';

            $categoria  = $rawData['categoria'] ?? $rawData['categoria2'] ?? $rawData['tipo_despesa'] ?? 'Geral';
            if (empty($categoria)) {
                $categoria = 'Geral';
            }

            $fornecedor = $rawData['fornecedor'] ?? '';

            $vencimentoRaw = $rawData['vencimento'] ?? '';
            $dataVencimento = !empty($vencimentoRaw) ? $this->parseDate($vencimentoRaw) : date('Y-m-d');

            $pagamentoRaw = $rawData['pagamento'] ?? '';
            if ($status === 'Pago' && !empty($pagamentoRaw)) {
                $dataVencimento = $this->parseDate($pagamentoRaw);
            }

            $descricao = $rawData['descricao'] ?? '';
            if (empty($descricao)) {
                $descricao = "Importado do Sispet — {$categoria}";
            }

            $dbData = [
                'descricao'       => $descricao,
                'detalhe'         => $descricao, // Alias for backward compatibility in reports
                'categoria'       => $categoria,
                'fornecedor'      => $fornecedor,
                'valor'           => $valorDoc,
                'data_vencimento' => $dataVencimento,
                'forma_pagamento' => $dicFormas[mb_strtolower($rawData['forma_pagamento'] ?? 'outros')] ?? ($rawData['forma_pagamento'] ?? 'outros'),
                'status'          => $status,
            ];

            $allProcessedData[] = [
                'line'   => $rowCount,
                'dbData' => $dbData
            ];
        }
        fclose($handle);

        // 2ª Passagem: Verificar duplicados em lotes para evitar problemas de memória e limite de parâmetros SQL
        $batchSize = 250;
        $totalItems = count($allProcessedData);

        for ($i = 0; $i < $totalItems; $i += $batchSize) {
            $batch = array_slice($allProcessedData, $i, $batchSize);
            $query = $this->repository->getModel();

            $hasCriteria = false;
            foreach ($batch as $item) {
                $dbData = $item['dbData'];
                $query->orGroupStart()
                    ->where('fornecedor', $dbData['fornecedor'])
                    ->where('valor', $dbData['valor'])
                    ->where('data_vencimento', $dbData['data_vencimento'])
                ->groupEnd();
                $hasCriteria = true;
            }

            if ($hasCriteria) {
                $existing = $query->findAll();
                foreach ($existing as $ex) {
                    $mapKey = $this->generateLookupKey($ex['fornecedor'], (float)$ex['valor'], $ex['data_vencimento']);
                    $duplicatesMap[$mapKey] = true;
                }
            }
        }

        // 3ª Passagem: Inserir dados
        foreach ($allProcessedData as $item) {
            $line   = $item['line'];
            $dbData = $item['dbData'];
            $mapKey = $this->generateLookupKey($dbData['fornecedor'], (float)$dbData['valor'], $dbData['data_vencimento']);

            if (isset($duplicatesMap[$mapKey])) {
                $ignored[] = [
                    'line'       => $line,
                    'fornecedor' => $dbData['fornecedor'] ?: '—',
                    'detalhe'    => $dbData['descricao'] ?? $dbData['detalhe'] ?? '—',
                    'motivo'     => 'Provável duplicado (já existe lançamento idêntico)',
                ];
                continue;
            }

            try {
                $id = $this->repository->create($dbData);
                if ($id) {
                    $importedCount++;
                    // Adicionar ao mapa para evitar duplicados dentro do próprio CSV
                    $duplicatesMap[$mapKey] = true;
                } else {
                    $errors[] = "Linha {$line} ({$dbData['fornecedor']}): Erro ao salvar no banco.";
                }
            } catch (Exception $e) {
                $errors[] = "Linha {$line} ({$dbData['fornecedor']}): " . $e->getMessage();
            }
        }

        return $this->success('Importação de despesas concluída.', [
            'total_processado' => $rowCount,
            'importados'       => $importedCount,
            'ignorados'        => $ignored,
            'erros'            => $errors,
        ]);
    }

    /**
     * Gera uma chave de busca normalizada para evitar problemas com formatação de floats.
     */
    private function generateLookupKey(string $fornecedor, float $valor, string $data): string
    {
        // Normalizamos o valor para 2 casas decimais e usamos um separador incomum
        return trim($fornecedor) . '::' . number_format($valor, 2, '.', '') . '::' . $data;
    }

    /**
     * Mapeia as colunas do cabeçalho CSV para chaves internas.
     *
     * @param array $header
     * @return array
     */
    private function getMapping(array $header): array
    {
        $map = [
            'Situação'            => 'status',
            'Tipo documento'      => 'forma_pagamento',
            'Vencimento'          => 'vencimento',
            'Pagamento'           => 'pagamento',
            'Razão'               => 'fornecedor',
            'Valor documento'     => 'valor',
            'Detalhes'            => 'descricao',
            'Planos de contas III'=> 'categoria',
            'Planos de contas II' => 'categoria2',
            'Tipo de despesa'     => 'tipo_despesa',
        ];

        $mapping = [];
        foreach ($header as $index => $label) {
            foreach ($map as $csvLabel => $dbField) {
                if (mb_strtolower(trim($label)) === mb_strtolower($csvLabel)) {
                    $mapping[$index] = $dbField;
                    break;
                }
            }
        }

        return $mapping;
    }

    /**
     * Converte "1.234,56" ou "1234.56" para float.
     *
     * @param string $val
     * @return float
     */
    private function parseMoeda(string $val): float
    {
        $val = trim($val);
        // Notação científica do Excel (ex: 7,44778E+12 → CNPJ, mas no valor usamos parseMoeda)
        // Para valores financeiros reais, apenas converter BR → EN
        $val = str_replace('.', '', $val);   // Remove separador de milhar
        $val = str_replace(',', '.', $val);  // Converte decimal
        return (float) $val;
    }

    /**
     * Converte dd/mm/yyyy para yyyy-mm-dd.
     *
     * @param string $date
     * @return string
     */
    private function parseDate(string $date): string
    {
        $date  = str_replace(['/', '.'], '-', trim($date));
        $parts = explode('-', $date);

        if (count($parts) === 3 && strlen($parts[2]) === 4) {
            // dd-mm-yyyy → yyyy-mm-dd
            return "{$parts[2]}-{$parts[1]}-{$parts[0]}";
        }

        return $date;
    }

}
