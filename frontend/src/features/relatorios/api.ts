import { api, type ApiEnvelope } from '../../lib/api'
import type { DREData, ExtratoData, LivroCaixaData, PeriodoFiltro } from './types'

function qs(filtro: PeriodoFiltro, extra?: Record<string, string>): string {
  const params = new URLSearchParams({ inicio: filtro.inicio, fim: filtro.fim, ...extra })
  return params.toString()
}

export function fetchExtrato(filtro: PeriodoFiltro, tipo?: string, status?: string): Promise<ApiEnvelope<ExtratoData>> {
  const extra: Record<string, string> = {}
  if (tipo) extra.tipo = tipo
  if (status) extra.status = status
  return api.get<ExtratoData>(`/relatorios/extrato?${qs(filtro, extra)}`)
}

export function fetchDRE(filtro: PeriodoFiltro): Promise<ApiEnvelope<DREData>> {
  return api.get<DREData>(`/relatorios/dre?${qs(filtro)}`)
}

export function fetchLivroCaixa(filtro: PeriodoFiltro): Promise<ApiEnvelope<LivroCaixaData>> {
  return api.get<LivroCaixaData>(`/relatorios/livro-caixa?${qs(filtro)}`)
}
