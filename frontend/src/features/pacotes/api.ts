import { api, type ApiEnvelope } from '../../lib/api'
import type {
  Pacote,
  PacoteDetalhes,
  PacoteEditFormValues,
  PacoteFormValues,
  PacoteTipo,
  PreAgendamentoValues,
} from './types'

export function fetchPacotes(): Promise<ApiEnvelope<Pacote[]>> {
  return api.get<Pacote[]>('/pacotes')
}

export function fetchPacoteDetalhes(id: number): Promise<ApiEnvelope<PacoteDetalhes>> {
  return api.get<PacoteDetalhes>(`/pacotes/${id}`)
}

export function fetchPacotesDisponiveis(pacienteId: number): Promise<ApiEnvelope<Pacote[]>> {
  return api.get<Pacote[]>(`/pacientes/${pacienteId}/pacotes-disponiveis`)
}

export function createPacote(values: PacoteFormValues): Promise<ApiEnvelope> {
  const payload: Record<string, unknown> = {
    paciente_id: Number(values.paciente_id),
    nome: values.nome,
    tipo: values.tipo,
    data_validade: values.data_validade || null,
    observacoes: values.observacoes || null,
    valor_total: values.valor_total ? Number(values.valor_total) : 0,
    desconto: values.desconto ? Number(values.desconto) : 0,
    forma_pagamento: values.forma_pagamento,
    vencimento: values.vencimento || null,
  }

  if (values.tipo === 'Serviços') {
    payload.itens = values.itens
      .filter((item) => item.servico_id || item.item_nome)
      .map((item) => ({
        servico_id: item.servico_id ? Number(item.servico_id) : null,
        item_nome: item.item_nome || null,
        quantidade: item.quantidade ? Number(item.quantidade) : 1,
        valor_unitario: item.valor_unitario ? Number(item.valor_unitario) : 0,
      }))

    if (values.preagendar) {
      payload.preagendar = true
      payload.preagendamento = preAgendamentoPayload(values.preagendamento)
    }
  }

  return api.post('/pacotes', payload)
}

function preAgendamentoPayload(values: PreAgendamentoValues): Record<string, unknown> {
  return {
    data_inicial: values.data_inicial,
    periodicidade: values.periodicidade,
    hora: values.hora,
    duracao: Number(values.duracao) || 30,
    veterinario_id: values.veterinario_id ? Number(values.veterinario_id) : null,
  }
}

export function preAgendarPacote(id: number, values: PreAgendamentoValues): Promise<ApiEnvelope> {
  return api.post(`/pacotes/${id}/preagendar`, preAgendamentoPayload(values))
}

export function updatePacote(id: number, values: PacoteEditFormValues, tipo: PacoteTipo): Promise<ApiEnvelope> {
  const payload: Record<string, unknown> = {
    nome: values.nome,
    status: values.status,
    data_validade: values.data_validade || null,
    observacoes: values.observacoes || null,
  }

  if (tipo === 'Crédito') {
    payload.saldo_valor = values.saldo_valor ? Number(values.saldo_valor) : 0
  }

  if (tipo === 'Serviços' && values.status === 'Pendente') {
    payload.itens = values.itens
      .filter((item) => item.servico_id || item.item_nome)
      .map((item) => ({
        servico_id: item.servico_id ? Number(item.servico_id) : null,
        item_nome: item.item_nome || null,
        quantidade: item.quantidade ? Number(item.quantidade) : 1,
        valor_unitario: item.valor_unitario ? Number(item.valor_unitario) : 0,
      }))
  }

  return api.put(`/pacotes/${id}`, payload)
}

export function deletePacote(id: number): Promise<ApiEnvelope> {
  return api.delete(`/pacotes/${id}`)
}
