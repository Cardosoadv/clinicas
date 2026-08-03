import { api, BASE_URL, type ApiEnvelope } from '../../lib/api'
import type {
  CobrancaFormValues,
  DashboardData,
  DespesaFormValues,
  LancamentosData,
  Nota,
  NotaFormValues,
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
  return {
    descricao: values.descricao,
    categoria: values.categoria,
    fornecedor: values.fornecedor || null,
    valor: Number(values.valor) || 0,
    data_vencimento: values.data_vencimento,
    forma_pagamento: values.forma_pagamento || null,
    status: values.status,
  }
}

export function createDespesa(values: DespesaFormValues): Promise<ApiEnvelope> {
  return api.post('/faturamento/despesas', despesaPayload(values))
}

export function updateDespesa(id: number, values: DespesaFormValues): Promise<ApiEnvelope> {
  return api.put(`/faturamento/despesas/${id}`, despesaPayload(values))
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
