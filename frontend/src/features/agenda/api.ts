import { api, type ApiEnvelope } from '../../lib/api'
import type {
  Agendamento,
  AgendamentoFormValues,
  AgendamentoStatus,
  DayData,
  EquipeOption,
  PacienteLookup,
  ServicoOption,
} from './types'

export function fetchDayData(date: string): Promise<ApiEnvelope<DayData>> {
  return api.get<DayData>(`/agendamentos/dia?date=${date}`)
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
