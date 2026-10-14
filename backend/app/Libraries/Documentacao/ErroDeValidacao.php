<?php

declare(strict_types=1);

namespace App\Libraries\Documentacao;

/**
 * Entrada recusada na edição da documentação. `erros()` usa o campo como chave
 * (`titulo`, `telas.1`), pronto para a resposta 422 da API.
 */
class ErroDeValidacao extends \RuntimeException
{
    /** @param array<string, string> $erros */
    public function __construct(private array $erros)
    {
        parent::__construct(implode(' ', $erros) ?: 'Dados inválidos.');
    }

    /** @return array<string, string> */
    public function erros(): array
    {
        return $this->erros;
    }

    public static function campo(string $campo, string $mensagem): self
    {
        return new self([$campo => $mensagem]);
    }
}
