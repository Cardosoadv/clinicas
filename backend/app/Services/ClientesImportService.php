<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ClientesRepository;
use Exception;

/**
 * @property ClientesRepository $repository
 */
class ClientesImportService extends BaseService
{
    /**
     * @param ClientesRepository|null $repository
     */
    public function __construct(?ClientesRepository $repository = null)
    {
        $this->repository = $repository ?? new ClientesRepository();
    }

    /**
     * Importa clientes a partir de um arquivo CSV.
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

        // Detectar delimitador (tenta ; primeiro, depois , e tab)
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return $this->error('Arquivo vazio.');
        }
        rewind($handle);
        
        $delimiters = [';', ',', "\t"];
        $delimiter = ';';
        foreach ($delimiters as $d) {
            if (strpos($firstLine, $d) !== false) {
                $delimiter = $d;
                break;
            }
        }

        // Ler cabeçalho
        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            return $this->error('Arquivo CSV vazio ou inválido.');
        }

        // Limpar BOM e espaços do cabeçalho
        $header = array_map(function($h) {
            return trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h));
        }, $header);

        $mapping = $this->getMapping($header);
        
        $importedCount = 0;
        $ignored = [];
        $errors = [];
        $rowCount = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowCount++;
            $data = [];
            
            foreach ($mapping as $csvIndex => $dbField) {
                if (isset($row[$csvIndex])) {
                    $val = trim($row[$csvIndex]);
                    
                    // Tratamento especial para datas
                    if ($dbField === 'nascimento' && !empty($val)) {
                        $val = $this->parseDate($val);
                    }
                    
                    $data[$dbField] = $val;
                }
            }

            // Precisamos do ID original e Nome para o relatório de ignorados
            $originalId = '';
            $idIndex = array_search('ID Cliente', $header);
            if ($idIndex !== false && isset($row[$idIndex])) {
                $originalId = $row[$idIndex];
            }
            
            $nome = $data['nome'] ?? 'Desconhecido';

            if (empty($data['nome'])) {
                $errors[] = "Linha $rowCount: Nome não informado.";
                continue;
            }

            // Verificar duplicidade por CPF
            if (!empty($data['cpf'])) {
                $existing = $this->repository->findByCpf($data['cpf']);
                if ($existing) {
                    $ignored[] = [
                        'nome' => $nome,
                        'id_original' => $originalId,
                        'motivo' => 'CPF já cadastrado (' . $data['cpf'] . ')'
                    ];
                    continue;
                }
            }

            try {
                $id = $this->repository->create($data);
                if ($id) {
                    $importedCount++;
                } else {
                    $errors[] = "Linha $rowCount ($nome): Erro ao inserir no banco.";
                }
            } catch (Exception $e) {
                $errors[] = "Linha $rowCount ($nome): " . $e->getMessage();
            }
        }

        fclose($handle);

        return $this->success('Processamento concluído.', [
            'total_processado' => $rowCount,
            'importados' => $importedCount,
            'ignorados' => $ignored,
            'erros' => $errors
        ]);
    }

    /**
     * Mapeia as colunas do CSV para os campos do banco de dados.
     *
     * @param array $header
     * @return array
     */
    private function getMapping(array $header): array
    {
        $map = [
            'Nome'           => 'nome',
            'Razão'          => 'razao_social',
            'Apelido'        => 'apelido',
            'Obs. cliente'   => 'observacoes',
            'RG'             => 'rg',
            'CPF'            => 'cpf',
            'CNPJ'           => 'cnpj',
            'Nascimento'     => 'nascimento',
            'Telefones'      => 'telefones',
            'Emails'         => 'emails',
            'Rua'            => 'rua',
            'Número'         => 'numero',
            'Complemento'    => 'complemento',
            'Bairro'         => 'bairro',
            'Cep'            => 'cep',
            'Cidade'         => 'cidade',
            'Estado'         => 'estado',
            'Vendedor'       => 'vendedor',
            'Empresa'        => 'empresa',
            'Última compra'  => 'ultima_compra',
            'Origem cliente' => 'origem_cliente',
            'ID Cliente'     => 'exportacao',
        ];

        $mapping = [];
        foreach ($header as $index => $label) {
            foreach ($map as $csvLabel => $dbField) {
                // Comparação case-insensitive e flexível
                if (mb_strtolower($label) === mb_strtolower($csvLabel)) {
                    $mapping[$index] = $dbField;
                    break;
                }
            }
        }

        return $mapping;
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
            // Assume dd-mm-yyyy se o primeiro for dia (<=31) e o último for ano (4 dígitos ou >31)
            if (strlen($parts[2]) === 4 || (int)$parts[0] <= 31) {
                return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            }
        }
        
        return $date; // Retorna original se não conseguir converter
    }
}
