import { api, type ApiEnvelope } from '../../lib/api'
import type { Paciente, PacienteFormValues, PacienteStatusFiltro, PacientesPainel } from './types'

export function fetchPacientes(
  status: PacienteStatusFiltro,
  especie: string,
  search: string,
): Promise<ApiEnvelope<Paciente[]>> {
  const params = new URLSearchParams()
  if (status) params.set('status', status)
  if (especie) params.set('especie', especie)
  if (search.trim()) params.set('search', search.trim())

  const qs = params.toString()
  return api.get<Paciente[]>(`/pacientes${qs ? `?${qs}` : ''}`)
}

export function fetchPacientesPainel(): Promise<ApiEnvelope<PacientesPainel>> {
  return api.get<PacientesPainel>('/pacientes/painel')
}

export function fetchPacienteById(id: number | string): Promise<ApiEnvelope<Paciente>> {
  return api.get<Paciente>(`/pacientes/${id}`)
}

function toPayload(values: PacienteFormValues): Record<string, unknown> {
  return { ...values, cliente_id: Number(values.cliente_id) }
}

export function createPaciente(values: PacienteFormValues): Promise<ApiEnvelope> {
  return api.post('/pacientes', toPayload(values))
}

export function updatePaciente(id: number, values: PacienteFormValues): Promise<ApiEnvelope> {
  return api.put(`/pacientes/${id}`, toPayload(values))
}

export function deletePaciente(id: number): Promise<ApiEnvelope> {
  return api.delete(`/pacientes/${id}`)
}
