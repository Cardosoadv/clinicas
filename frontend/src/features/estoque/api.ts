import { api, type ApiEnvelope } from '../../lib/api'
import type { MovimentacaoFormValues, MovimentacaoTipo, Produto, ProdutoFormValues } from './types'

export function fetchProdutos(): Promise<ApiEnvelope<Produto[]>> {
  return api.get<Produto[]>('/estoque/produtos')
}

export function fetchAlertas(): Promise<ApiEnvelope<Produto[]>> {
  return api.get<Produto[]>('/estoque/alertas')
}

function toPayload(values: ProdutoFormValues): Record<string, unknown> {
  return {
    nome: values.nome,
    descricao: values.descricao || null,
    unidade_medida: values.unidade_medida || null,
    quantidade_atual: values.quantidade_atual ? Number(values.quantidade_atual) : 0,
    estoque_minimo: values.estoque_minimo ? Number(values.estoque_minimo) : 0,
    valor_venda: values.valor_venda ? Number(values.valor_venda) : 0,
  }
}

export function createProduto(values: ProdutoFormValues): Promise<ApiEnvelope> {
  return api.post('/estoque/produtos', toPayload(values))
}

export function updateProduto(id: number, values: ProdutoFormValues): Promise<ApiEnvelope> {
  return api.put(`/estoque/produtos/${id}`, toPayload(values))
}

export function deleteProduto(id: number): Promise<ApiEnvelope> {
  return api.delete(`/estoque/produtos/${id}`)
}

export function registrarMovimentacao(
  tipo: MovimentacaoTipo,
  values: MovimentacaoFormValues,
  produtoNome: string,
): Promise<ApiEnvelope> {
  const payload: Record<string, unknown> = {
    produto_id: Number(values.produto_id),
    quantidade: Number(values.quantidade),
    observacao: values.observacao || null,
  }

  if (tipo === 'Entrada') {
    payload.valor_unitario = values.valor_unitario ? Number(values.valor_unitario) : null
    payload.valor_total = values.valor_total ? Number(values.valor_total) : null
    payload.produto_nome = produtoNome
    if (values.lancar_financeiro) {
      payload.lancar_financeiro = true
      payload.fornecedor = values.fornecedor || null
      payload.forma_pagamento = values.forma_pagamento || null
      payload.data_vencimento = values.data_vencimento || null
      payload.status_pagamento = values.status_pagamento
    }
    return api.post('/estoque/movimentacoes/entrada', payload)
  }

  return api.post('/estoque/movimentacoes/saida', payload)
}
