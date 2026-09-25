import { fetchTemplates } from '../features/configuracoes/api'

export function formatDataExtenso(date: Date): string {
  return new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' }).format(date).toUpperCase()
}

// Os templates mudam raramente: busca uma vez e reaproveita entre impressões.
let templatesCache: Promise<{ header: string; footer: string }> | null = null

export function loadTemplates(): Promise<{ header: string; footer: string }> {
  if (!templatesCache) {
    templatesCache = fetchTemplates()
      .then((res) => {
        const data = res.data ?? []
        return {
          header: data.find((d) => d.meta_key === 'report_header')?.meta_value?.trim() ?? '',
          footer: data.find((d) => d.meta_key === 'report_footer')?.meta_value?.trim() ?? '',
        }
      })
      .catch(() => {
        templatesCache = null
        return { header: '', footer: '' }
      })
  }
  return templatesCache
}

/** Invalida o cache após salvar um template em Configurações. */
export function invalidateReportTemplates(): void {
  templatesCache = null
}
