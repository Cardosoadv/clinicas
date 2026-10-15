import { api, BASE_URL, type ApiEnvelope } from '../../lib/api'
import type { Escopo } from './despesas'
import {
  despesaToFormValues,
  type CobrancaFormValues,
  type DashboardData,
  type Despesa,
  type DespesaFormValues,
  type DespesasPagina,
  type LancamentosData,
  type Nota,
  type NotaFormValues,
  type ReciboPublicoData,
} from './types'

export function fetchDashboard(): Promise<ApiEnvelope<DashboardData>> {
  return api.get<DashboardData>('/faturamento/dashboard')
}

export function fetchLancamentos(): Promise<ApiEnvelope<LancamentosData>> {
  return api.get<LancamentosData>('/faturamento/lancamentos')
}

function cobrancaPayload(values: CobrancaFormValues): Record<string, unknown> {
  return {
    paciente_id: Number(values.paciente_id),
    servico: values.servico,
    servico_id: values.servico_id ? Number(values.servico_id) : null,
    valor: Number(values.valor) || 0,
    desconto: Number(values.desconto) || 0,
    data_servico: values.data_servico,
    forma_pagamento: values.forma_pagamento,
    parcelas: values.parcelas,
    vencimento: values.vencimento,
    status: values.status,
    observacoes: values.observacoes || null,
    pacote_id: values.forma_pagamento === 'Pacote' && values.pacote_id ? Number(values.pacote_id) : null,
  }
}

export function createCobranca(values: CobrancaFormValues): Promise<ApiEnvelope> {
  return api.post('/faturamento/cobrancas', cobrancaPayload(values))
}

export function updateCobranca(id: number, values: CobrancaFormValues): Promise<ApiEnvelope> {
  return api.put(`/faturamento/cobrancas/${id}`, cobrancaPayload(values))
}

function despesaPayload(values: DespesaFormValues): Record<string, unknown> {
  const payload: Record<string, unknown> = {
    descricao: values.descricao,
    categoria: values.categoria,
    fornecedor: values.fornecedor || null,
    valor: Number(values.valor) || 0,
    data_vencimento: values.data_vencimento,
    forma_pagamento: values.forma_pagamento || null,
    status: values.status,
  }

  if (values.parcelar) {
    // valor = total; as parcelas (já conferidas na prévia) vão prontas para o backend
    payload.is_parcelado = 'true'
    payload.parcelas = values.parcelas.map((p) => ({ valor: Number(p.valor), vencimento: p.vencimento }))
  }

  return payload
}

export function createDespesa(values: DespesaFormValues): Promise<ApiEnvelope> {
  return api.post('/faturamento/despesas', despesaPayload(values))
}

/**
 * Edita uma despesa. Com `escopo` diferente de `apenas_esta` (e a despesa original), envia só os campos
 * alterados, para não sobrescrever nas demais parcelas dados que o usuário não mexeu.
 */
export function updateDespesa(
  id: number,
  values: DespesaFormValues,
  escopo: Escopo = 'apenas_esta',
  original?: Despesa,
): Promise<ApiEnvelope> {
  let payload = despesaPayload(values)
  if (escopo === 'apenas_esta') {
    return api.put(`/faturamento/despesas/${id}`, payload)
  }

  if (original) {
    const antes = despesaPayload(despesaToFormValues(original))
    payload = Object.fromEntries(Object.entries(payload).filter(([campo, valor]) => valor !== antes[campo]))
  }
  return api.put(`/faturamento/despesas/${id}`, { ...payload, escopo })
}

export function marcarDespesaPaga(id: number): Promise<ApiEnvelope> {
  return api.put(`/faturamento/despesas/${id}`, { status: 'Pago' })
}

export function cancelarDespesa(id: number, escopo: Escopo): Promise<ApiEnvelope> {
  return api.post(`/faturamento/despesas/${id}/cancelar`, { escopo })
}

export function fetchDespesas(filtros: { mes: string; status: string; page: number }): Promise<ApiEnvelope<DespesasPagina>> {
  const params = new URLSearchParams({ page: String(filtros.page) })
  if (filtros.mes) params.set('mes', filtros.mes)
  if (filtros.status) params.set('status', filtros.status)
  return api.get<DespesasPagina>(`/faturamento/despesas?${params.toString()}`)
}

/** Parcelas de um grupo, em ordem de parcela. */
export async function fetchGrupoDespesas(grupoId: string): Promise<Despesa[]> {
  const result = await api.get(`/faturamento/despesas/grupo/${encodeURIComponent(grupoId)}`)
  return Array.isArray(result.parcelas) ? (result.parcelas as Despesa[]) : []
}

export function uploadComprovante(id: number, file: File): Promise<ApiEnvelope> {
  const formData = new FormData()
  formData.set('comprovante', file)
  return api.postForm(`/faturamento/despesas/${id}/comprovante`, formData)
}

export function comprovanteUrl(id: number, filename: string): string {
  return `${BASE_URL}/faturamento/despesas/${id}/comprovante/${filename}`
}

export function fetchNotas(): Promise<ApiEnvelope<Nota[]>> {
  return api.get<Nota[]>('/faturamento/notas')
}

export function createNota(values: NotaFormValues): Promise<ApiEnvelope> {
  return api.post('/faturamento/notas', {
    paciente_id: Number(values.paciente_id),
    tipo: values.tipo,
    cpf_responsavel: values.cpf_responsavel || null,
    data_emissao: values.data_emissao,
    descricao: values.descricao,
    valor: Number(values.valor) || 0,
  })
}

export function reciboImprimirUrl(id: number, hash: string): string {
  const basePath = window.APP_CONFIG?.BASE_PATH || ''
  return `${basePath}/recibo/${id}/${hash}`
}

export function fetchReciboPublico(id: number, hash: string): Promise<ApiEnvelope<ReciboPublicoData>> {
  return api.get<ReciboPublicoData>(`/publico/recibos/${id}/${hash}`)
}
