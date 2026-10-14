/**
 * Endereços da documentação no React Router e leitura dos links que o backend
 * gera dentro das páginas.
 *
 * - leitura: `/documentacao`, `/documentacao/{slug}` e `/documentacao/{slug}#{ancora}`;
 * - edição: `/documentacao-admin` (lista), `/documentacao-admin/nova` e `/documentacao-admin/{id}`.
 *
 * No HTML das páginas os links internos vêm relativos, sem barra inicial
 * (`documentacao/agenda/faturar#materiais`, `agenda`, `./`): o `<base href>`
 * resolve o subcaminho de deploy, e o clique é interceptado para navegar sem recarregar.
 */

export const PREFIXO = '/documentacao'
export const PREFIXO_EDICAO = '/documentacao-admin'

export function caminhoDaDocumentacao(slug?: string | null, ancora?: string | null): string {
  let caminho = PREFIXO
  if (slug) caminho += `/${slug.split('/').map(encodeURIComponent).join('/')}`
  if (slug && ancora) caminho += `#${encodeURIComponent(ancora)}`
  return caminho
}

export function caminhoDaEdicao(id?: number | 'nova' | null): string {
  return id ? `${PREFIXO_EDICAO}/${id}` : PREFIXO_EDICAO
}

/**
 * Destino (no React Router) de um link do conteúdo, ou `null` quando ele deve seguir
 * o comportamento padrão do navegador (externo, `mailto:`, sem `href`).
 */
export function destinoInterno(href: string | null): string | null {
  if (!href || /^[a-z][a-z0-9+.-]*:/i.test(href) || href.startsWith('//')) return null
  if (href === './' || href === '.') return '/'
  return `/${href.replace(/^\.?\//, '')}`
}

/** O destino é uma página da documentação? */
export function ehDaDocumentacao(destino: string): boolean {
  return destino === PREFIXO || destino.startsWith(`${PREFIXO}/`) || destino.startsWith(`${PREFIXO}#`)
}

/** ISO 8601 → `14/10/2026`; `—` quando não há data. */
export function dataCurta(iso: string | null): string {
  const data = iso ? new Date(iso) : null
  return data && !Number.isNaN(data.getTime()) ? data.toLocaleDateString('pt-BR') : '—'
}
