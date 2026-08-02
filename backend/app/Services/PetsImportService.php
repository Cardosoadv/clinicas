<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PetsRepository;
use App\Repositories\ClientesRepository;
use Exception;

/**
 * @property PetsRepository $repository
 */
class PetsImportService extends BaseService
{
    protected ClientesRepository $clientesRepository;

    /**
     * @param PetsRepository|null $repository
     * @param ClientesRepository|null $clientesRepository
     */
    public function __construct(?PetsRepository $repository = null, ?ClientesRepository $clientesRepository = null)
    {
        $this->repository = $repository ?? new PetsRepository();
        $this->clientesRepository = $clientesRepository ?? new ClientesRepository();
    }

    /**
     * Importa pets a partir de um arquivo CSV.
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

        // Delimitador padrão (;) conforme solicitado
        $delimiter = ';';

        // Ler cabeçalho
        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            return $this->error('Arquivo CSV vazio ou inválido.');
        }

        // Limpar cabeçalho
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
                    if ($dbField === 'paciente_nascimento' && !empty($val)) {
                        $val = $this->parseDate($val);
                    }
                    
                    $data[$dbField] = $val;
                }
            }

            // Precisamos do ID Cliente para vínculo
            $exportId = '';
            $idIndex = array_search('ID Cliente', $header);
            if ($idIndex !== false && isset($row[$idIndex])) {
                $exportId = $row[$idIndex];
            }

            $petNome = $data['paciente_nome'] ?? 'Sem Nome';

            if (empty($exportId)) {
                $errors[] = "Linha $rowCount ($petNome): ID Cliente não informado no CSV.";
                continue;
            }

            // Buscar cliente_id real
            $cliente = $this->clientesRepository->findByExportacao($exportId);
            if (!$cliente) {
                $ignored[] = [
                    'pet' => $petNome,
                    'export_id' => $exportId,
                    'motivo' => 'Tutor não encontrado (ID Exportação: ' . $exportId . ')'
                ];
                continue;
            }

            $clienteId = (int)$cliente['id'];
            $data['cliente_id'] = $clienteId;

            // Verificar duplicidade (mesmo nome para o mesmo cliente)
            $existing = $this->repository->findByNomeAndCliente($petNome, $clienteId);
            if ($existing) {
                $ignored[] = [
                    'pet' => $petNome,
                    'export_id' => $exportId,
                    'motivo' => 'Pet já cadastrado para este tutor'
                ];
                continue;
            }

            // Outros mapeamentos específicos
            if (isset($data['paciente_status'])) {
                $data['paciente_status'] = (mb_strtolower($data['paciente_status']) === 'ativo') ? 'Ativo' : 'Inativo';
            }

            try {
                $id = $this->repository->create($data);
                if ($id) {
                    $importedCount++;
                } else {
                    $errors[] = "Linha $rowCount ($petNome): Erro ao inserir no banco.";
                }
            } catch (Exception $e) {
                $errors[] = "Linha $rowCount ($petNome): " . $e->getMessage();
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
            'Nome Pet'        => 'paciente_nome',
            'Espécie'         => 'paciente_especie',
            'Raça'            => 'paciente_raca',
            'Gênero'          => 'paciente_sexo',
            'Porte'           => 'paciente_porte',
            'Pelagem'         => 'paciente_pelagem',
            'Nascimento'      => 'paciente_nascimento',
            'Características' => 'paciente_caracteristicas',
            'Ativo'           => 'paciente_status',
        ];

        $mapping = [];
        foreach ($header as $index => $label) {
            foreach ($map as $csvLabel => $dbField) {
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
            if (strlen($parts[2]) === 4 || (int)$parts[0] <= 31) {
                return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            }
        }
        
        return $date;
    }
}
