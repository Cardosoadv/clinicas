<?php

declare(strict_types=1);

namespace App\Libraries\Mcp;

use RuntimeException;

/** Falha de uma ferramenta (validação/regra de negócio): vira resultado com isError=true. */
class ToolException extends RuntimeException
{
    public function __construct(string $message, private readonly array $errors = [])
    {
        parent::__construct($message);
    }

    public function payload(): array
    {
        return array_filter(['message' => $this->getMessage(), 'errors' => $this->errors]);
    }
}
