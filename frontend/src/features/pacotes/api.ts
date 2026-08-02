import { api, type ApiEnvelope } from '../../lib/api'
import type { Pacote, PacoteDetalhes, PacoteFormValues } from './types'

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
  }

  return api.post('/pacotes', payload)
}
