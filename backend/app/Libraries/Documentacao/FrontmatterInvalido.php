<?php

declare(strict_types=1);

namespace App\Libraries\Documentacao;

/**
 * Cabeçalho de página da documentação que não pôde ser lido.
 * `erros()` usa o campo (ou `linha N`) como chave.
 */
class FrontmatterInvalido extends \RuntimeException
{
    /** @param array<string, string> $erros */
    public function __construct(private array $erros)
    {
        parent::__construct(implode(' ', array_map(
            static fn (string $campo, string $msg): string => "{$campo}: {$msg}",
            array_keys($erros),
            $erros,
        )));
    }

    /** @return array<string, string> */
    public function erros(): array
    {
        return $this->erros;
    }
}
