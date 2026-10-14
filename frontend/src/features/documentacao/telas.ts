/**
 * Identificador de cada tela para a ajuda contextual. As páginas da documentação
 * declaram em `telas` os identificadores que abrem a página pelo botão "?"
 * (ex.: `telas: [agenda, agenda-todos]`). Telas sem identificador não têm ajuda.
 */

const TELAS: Array<[RegExp, (m: RegExpMatchArray) => string]> = [
  [/^\/$/, () => 'dashboard'],
  [/^\/agenda$/, () => 'agenda'],
  [/^\/agenda\/todos$/, () => 'agenda-todos'],
  [/^\/clientes\/duplicados$/, () => 'clientes-duplicados'],
  [/^\/clientes\/\d+$/, () => 'cliente-detalhe'],
  [/^\/prontuarios\/\d+\/([a-z-]+)$/, (m) => `prontuario-${m[1]}`],
  [/^\/faturamento\/([a-z-]+)$/, (m) => `faturamento-${m[1]}`],
  [/^\/relatorios\/([a-z-]+)$/, (m) => `relatorio-${m[1]}`],
  [/^\/documentacao-admin$/, () => 'documentacao-admin'],
  [/^\/documentacao-admin\/(nova|\d+)$/, () => 'documentacao-editor'],
  [/^\/(pacientes|clientes|prontuarios|servicos|pacotes|estoque|equipe|lojas|configuracoes)$/, (m) => m[1]],
]

export function telaDaRota(pathname: string): string | null {
  const caminho = pathname.length > 1 ? pathname.replace(/\/+$/, '') : pathname
  for (const [padrao, tela] of TELAS) {
    const m = caminho.match(padrao)
    if (m) return tela(m)
  }
  return null
}
