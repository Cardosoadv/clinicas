import { api, type ApiEnvelope } from '../../lib/api'
import type { Paciente } from '../pacientes/types'
import type {
  Cliente,
  ClienteFormValues,
  ClienteNota,
  ClienteStatusFiltro,
  ClienteTipoFiltro,
  ClientesPainel,
  Comunicacao,
} from './types'

export function fetchClientes(
  status: ClienteStatusFiltro,
  tipo: ClienteTipoFiltro,
  search: string,
): Promise<ApiEnvelope<Cliente[]>> {
  const params = new URLSearchParams({ status })
  if (tipo) params.set('tipo', tipo)
  if (search.trim()) params.set('search', search.trim())

  return api.get<Cliente[]>(`/clientes?${params.toString()}`)
}

export function fetchClientesPainel(): Promise<ApiEnvelope<ClientesPainel>> {
  return api.get<ClientesPainel>('/clientes/painel')
}

export function fetchCliente(id: number): Promise<ApiEnvelope<Cliente>> {
  return api.get<Cliente>(`/clientes/${id}`)
}

export function fetchClientePacientes(id: number): Promise<ApiEnvelope<Paciente[]>> {
  return api.get<Paciente[]>(`/clientes/${id}/pacientes`)
}

export function fetchClienteNotas(id: number): Promise<ApiEnvelope<ClienteNota[]>> {
  return api.get<ClienteNota[]>(`/clientes/${id}/notas`)
}

export function createClienteNota(id: number, texto: string): Promise<ApiEnvelope> {
  return api.post(`/clientes/${id}/notas`, { texto })
}

export function deleteClienteNota(id: number, notaId: number): Promise<ApiEnvelope> {
  return api.delete(`/clientes/${id}/notas/${notaId}`)
}

export function fetchClienteComunicacoes(id: number): Promise<ApiEnvelope<Comunicacao[]>> {
  return api.get<Comunicacao[]>(`/clientes/${id}/comunicacoes`)
}

export function enviarComunicacao(id: number, mensagem: string): Promise<ApiEnvelope<{ link: string }>> {
  return api.post<{ link: string }>(`/clientes/${id}/comunicacoes`, { mensagem })
}

export function createCliente(payload: ClienteFormValues): Promise<ApiEnvelope> {
  return api.post('/clientes', payload)
}

export function updateCliente(id: number, payload: ClienteFormValues): Promise<ApiEnvelope> {
  return api.put(`/clientes/${id}`, payload)
}

export function deleteCliente(id: number): Promise<ApiEnvelope> {
  return api.delete(`/clientes/${id}`)
}
