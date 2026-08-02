<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Regras de validação de CPF e CNPJ.
 *
 * CNPJ segue o padrão alfanumérico da Receita Federal (Nota Técnica
 * COCAD/RFB nº 33/2024): os 12 primeiros caracteres da base podem ser
 * dígitos (0-9) ou letras maiúsculas (A-Z); os 2 dígitos verificadores
 * continuam sempre numéricos. Cada caractere é convertido para valor
 * numérico subtraindo 48 do seu código ASCII ('0'-'9' => 0-9,
 * 'A'-'Z' => 17-42) antes de aplicar o cálculo de módulo 11.
 */
class DocumentoRules
{
    public function valid_cpf($str, ?string &$error = null): bool
    {
        $digits = preg_replace('/\D/', '', (string) $str) ?? '';

        if (strlen($digits) !== 11 || preg_match('/^(\d)\1{10}$/', $digits) === 1) {
            $error = 'CPF inválido.';
            return false;
        }

        $base = str_split(substr($digits, 0, 9));
        $dv1  = $this->calcCheckDigit($base, [10, 9, 8, 7, 6, 5, 4, 3, 2]);
        $dv2  = $this->calcCheckDigit([...$base, (string) $dv1], [11, 10, 9, 8, 7, 6, 5, 4, 3, 2]);

        if ($digits !== implode('', $base) . $dv1 . $dv2) {
            $error = 'CPF inválido.';
            return false;
        }

        return true;
    }

    public function valid_cnpj($str, ?string &$error = null): bool
    {
        $chars = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', (string) $str) ?? '');

        if (strlen($chars) !== 14 || preg_match('/^([0-9A-Z])\1{13}$/', $chars) === 1) {
            $error = 'CNPJ inválido.';
            return false;
        }

        $base       = str_split(substr($chars, 0, 12));
        $providedDv = substr($chars, 12, 2);

        // Os dígitos verificadores permanecem sempre numéricos, mesmo no CNPJ alfanumérico.
        if (preg_match('/^\d{2}$/', $providedDv) !== 1) {
            $error = 'CNPJ inválido.';
            return false;
        }

        $dv1 = $this->calcCheckDigit($base, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $dv2 = $this->calcCheckDigit([...$base, (string) $dv1], [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        if ($providedDv !== $dv1 . $dv2) {
            $error = 'CNPJ inválido.';
            return false;
        }

        return true;
    }

    /**
     * @param list<string> $chars
     * @param list<int>    $weights
     */
    private function calcCheckDigit(array $chars, array $weights): int
    {
        $sum = 0;
        foreach ($chars as $i => $char) {
            $sum += (ord($char) - 48) * $weights[$i];
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
