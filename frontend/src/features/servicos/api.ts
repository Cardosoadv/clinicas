import { api, type ApiEnvelope } from '../../lib/api'
import type { BomItem, ProdutoOption, Servico, ServicoComProdutos, ServicoFormValues } from './types'

export function fetchServicos(search?: string, status?: string): Promise<ApiEnvelope<Servico[]>> {
  const params = new URLSearchParams()
  if (search && search.trim()) params.set('search', search.trim())
  if (status && status.trim()) params.set('status', status.trim())
  const query = params.toString()
  return api.get<Servico[]>(`/servicos${query ? `?${query}` : ''}`)
}

export function fetchProdutoOptions(): Promise<ApiEnvelope<ProdutoOption[]>> {
  return api.get<ProdutoOption[]>('/estoque/produtos')
}

export function fetchServicoComProdutos(id: number): Promise<ApiEnvelope<ServicoComProdutos>> {
  return api.get<ServicoComProdutos>(`/servicos/${id}`)
}

function toPayload(values: ServicoFormValues): Record<string, unknown> {
  return {
    ser_nome: values.ser_nome,
    ser_icone: values.ser_icone || null,
    ser_valor: values.ser_valor ? Number(values.ser_valor) : 0,
    ser_tempo_estimado: values.ser_tempo_estimado ? Number(values.ser_tempo_estimado) : null,
    ser_descricao: values.ser_descricao || null,
    ser_status: values.ser_status,
  }
}

export function createServico(values: ServicoFormValues): Promise<ApiEnvelope> {
  return api.post('/servicos', toPayload(values))
}

export function updateServico(id: number, values: ServicoFormValues): Promise<ApiEnvelope> {
  return api.put(`/servicos/${id}`, toPayload(values))
}

export function deleteServico(id: number): Promise<ApiEnvelope> {
  return api.delete(`/servicos/${id}`)
}

export function syncServicoProdutos(id: number, itens: BomItem[]): Promise<ApiEnvelope> {
  const produtos = itens
    .filter((item) => item.produto_id)
    .map((item) => ({ produto_id: Number(item.produto_id), quantidade: Number(item.quantidade) || 1 }))
  return api.put(`/servicos/${id}/produtos`, { produtos })
}
