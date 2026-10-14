<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\DocumentacaoPaginaModel;

/**
 * Acesso às páginas da documentação guardadas no banco.
 * `telas` e `palavras_chave` saem como listas (no banco ficam em JSON).
 */
class DocumentacaoPaginaRepository
{
    public function __construct(private ?DocumentacaoPaginaModel $model = null)
    {
        $this->model ??= new DocumentacaoPaginaModel();
    }

    /** A tabela existe? (antes da migration CreateDocumentacaoPaginas, não). */
    public function disponivel(): bool
    {
        return $this->model->db->tableExists('documentacao_paginas');
    }

    /** @return list<array<string, mixed>> */
    public function todas(): array
    {
        return array_map(self::normalizar(...), $this->model->orderBy('slug')->findAll());
    }

    public function porId(int $id): ?array
    {
        $linha = $this->model->find($id);

        return $linha ? self::normalizar($linha) : null;
    }

    public function porSlug(string $slug): ?array
    {
        $linha = $this->model->where('slug', $slug)->first();

        return $linha ? self::normalizar($linha) : null;
    }

    public function model(): DocumentacaoPaginaModel
    {
        return $this->model;
    }

    /** @param array<string, mixed> $linha */
    public static function normalizar(array $linha): array
    {
        foreach (['telas', 'palavras_chave'] as $lista) {
            $valor = json_decode((string) ($linha[$lista] ?? ''), true);
            $linha[$lista] = is_array($valor) ? array_values(array_map('strval', $valor)) : [];
        }
        $linha['id'] = (int) $linha['id'];
        $linha['ordem'] = (int) ($linha['ordem'] ?? 100);
        $linha['resumo'] = (string) ($linha['resumo'] ?? '');
        $linha['markdown'] = (string) ($linha['markdown'] ?? '');
        $linha['permissao'] = ($linha['permissao'] ?? '') !== '' ? $linha['permissao'] : null;

        return $linha;
    }
}
