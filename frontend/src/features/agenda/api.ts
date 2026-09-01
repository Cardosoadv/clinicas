import { api, type ApiEnvelope } from '../../lib/api'
import type { Cobranca } from '../faturamento/types'
import type {
  Agendamento,
  AgendamentoFormValues,
  AgendamentosFiltro,
  AgendamentoStatus,
  DayData,
  EquipeOption,
  FaturarFormValues,
  PacienteLookup,
  ServicoOption,
} from './types'

export function fetchDayData(date: string): Promise<ApiEnvelope<DayData>> {
  return api.get<DayData>(`/agendamentos/dia?date=${date}`)
}

export function fetchAgendamentos(filtro: AgendamentosFiltro): Promise<ApiEnvelope<Agendamento[]>> {
  const params = new URLSearchParams()
  if (filtro.status) params.set('status', filtro.status)
  if (filtro.data_inicio) params.set('data_inicio', filtro.data_inicio)
  if (filtro.data_fim) params.set('data_fim', filtro.data_fim)
  if (filtro.servico_id) params.set('servico_id', filtro.servico_id)
  if (filtro.veterinario_id) params.set('veterinario_id', filtro.veterinario_id)
  if (filtro.search) params.set('search', filtro.search)

  const query = params.toString()
  return api.get<Agendamento[]>(`/agendamentos${query ? `?${query}` : ''}`)
}

export function fetchUpcoming(): Promise<ApiEnvelope<Agendamento[]>> {
  return api.get<Agendamento[]>('/agendamentos/proximos')
}

export function fetchMonthDays(year: number, month: number): Promise<ApiEnvelope<{ age_data: string }[]>> {
  const mm = String(month).padStart(2, '0')
  return api.get<{ age_data: string }[]>(`/agendamentos/dias-do-mes?year=${year}&month=${mm}`)
}

export function searchPacientesLookup(term: string): Promise<ApiEnvelope<PacienteLookup[]>> {
  return api.get<PacienteLookup[]>(`/agendamentos/buscar-pacientes?term=${encodeURIComponent(term)}`)
}

export function fetchServicosOptions(): Promise<ApiEnvelope<ServicoOption[]>> {
  return api.get<ServicoOption[]>('/servicos')
}

export function fetchEquipeOptions(): Promise<ApiEnvelope<EquipeOption[]>> {
  return api.get<EquipeOption[]>('/equipe')
}

function toPayload(values: AgendamentoFormValues): Record<string, unknown> {
  return {
    ...values,
    paciente_id: Number(values.paciente_id),
    age_veterinario: values.age_veterinario ? Number(values.age_veterinario) : null,
  }
}

export function createAgendamento(values: AgendamentoFormValues): Promise<ApiEnvelope> {
  return api.post('/agendamentos', toPayload(values))
}

export function updateAgendamento(id: number, values: AgendamentoFormValues): Promise<ApiEnvelope> {
  return api.put(`/agendamentos/${id}`, toPayload(values))
}

/** Payload mínimo (sem age_servico) para não afetar os serviços já vinculados. */
export function rescheduleAgendamento(id: number, data: string, hora: string): Promise<ApiEnvelope> {
  return api.put(`/agendamentos/${id}`, { age_data: data, age_hora: hora })
}

export function updateAgendamentoStatus(id: number, status: AgendamentoStatus): Promise<ApiEnvelope> {
  return api.patch(`/agendamentos/${id}/status`, { status })
}

function toFaturarPayload(values: FaturarFormValues): Record<string, unknown> {
  return {
    ...values,
    valor: Number(values.valor),
    desconto: values.desconto ? Number(values.desconto) : 0,
    pacote_id: values.pacote_id ? Number(values.pacote_id) : null,
  }
}

export function faturarAgendamento(id: number, values: FaturarFormValues): Promise<ApiEnvelope> {
  return api.post(`/agendamentos/${id}/faturar`, toFaturarPayload(values))
}

export function fetchFaturamento(id: number): Promise<ApiEnvelope<Cobranca | null>> {
  return api.get<Cobranca | null>(`/agendamentos/${id}/faturamento`)
}
