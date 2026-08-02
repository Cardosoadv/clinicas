/**
 * Formatação e validação de CPF e CNPJ.
 *
 * O CNPJ segue o padrão alfanumérico da Receita Federal (Nota Técnica
 * COCAD/RFB nº 33/2024): os 12 primeiros caracteres da base podem ser
 * dígitos (0-9) ou letras maiúsculas (A-Z); os 2 dígitos verificadores
 * continuam sempre numéricos. Cada caractere é convertido para valor
 * numérico subtraindo 48 do seu código ASCII ('0'-'9' => 0-9,
 * 'A'-'Z' => 17-42) antes de aplicar o cálculo de módulo 11.
 * O CPF continua sendo sempre numérico (11 dígitos).
 */

function onlyDigits(value: string): string {
  return value.replace(/\D/g, '')
}

function onlyAlnumUpper(value: string): string {
  return value.replace(/[^0-9A-Za-z]/g, '').toUpperCase()
}

function charValue(char: string): number {
  return char.charCodeAt(0) - 48
}

function calcCheckDigit(chars: string[], weights: number[]): number {
  const sum = chars.reduce((acc, char, i) => acc + charValue(char) * weights[i], 0)
  const remainder = sum % 11
  return remainder < 2 ? 0 : 11 - remainder
}

export function formatCPF(value: string): string {
  const digits = onlyDigits(value).slice(0, 11)
  const parts = [digits.slice(0, 3), digits.slice(3, 6), digits.slice(6, 9)]
  let out = parts.filter(Boolean).join('.')
  const dv = digits.slice(9, 11)
  if (dv) out += `-${dv}`
  return out
}

export function formatCNPJ(value: string): string {
  const chars = onlyAlnumUpper(value).slice(0, 14)
  const parts = [chars.slice(0, 2), chars.slice(2, 5), chars.slice(5, 8)]
  let out = parts.filter(Boolean).join('.')
  const p4 = chars.slice(8, 12)
  if (p4) out += `/${p4}`
  const dv = chars.slice(12, 14)
  if (dv) out += `-${dv}`
  return out
}

export function isValidCPF(value: string): boolean {
  const digits = onlyDigits(value)
  if (digits.length !== 11 || /^(\d)\1{10}$/.test(digits)) return false

  const base = digits.slice(0, 9).split('')
  const dv1 = calcCheckDigit(base, [10, 9, 8, 7, 6, 5, 4, 3, 2])
  const dv2 = calcCheckDigit([...base, String(dv1)], [11, 10, 9, 8, 7, 6, 5, 4, 3, 2])

  return digits === base.join('') + dv1 + dv2
}

export function isValidCNPJ(value: string): boolean {
  const chars = onlyAlnumUpper(value)
  if (chars.length !== 14 || /^([0-9A-Z])\1{13}$/.test(chars)) return false

  const base = chars.slice(0, 12).split('')
  const providedDv = chars.slice(12, 14)
  if (!/^\d{2}$/.test(providedDv)) return false

  const dv1 = calcCheckDigit(base, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2])
  const dv2 = calcCheckDigit([...base, String(dv1)], [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2])

  return providedDv === `${dv1}${dv2}`
}
