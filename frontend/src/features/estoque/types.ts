export interface Produto {
  id: number
  nome: string
  descricao: string | null
  unidade_medida: string | null
  quantidade_atual: number
  estoque_minimo: number
  valor_venda: number
  created_at: string
  updated_at: string
  deleted_at: string | null
}

export interface ProdutoFormValues {
  nome: string
  descricao: string
  unidade_medida: string
  quantidade_atual: string
  estoque_minimo: string
  valor_venda: string
}

export const emptyProdutoForm: ProdutoFormValues = {
  nome: '',
  descricao: '',
  unidade_medida: 'Unidade',
  quantidade_atual: '0',
  estoque_minimo: '0',
  valor_venda: '',
}

export function produtoToFormValues(produto: Produto): ProdutoFormValues {
  return {
    nome: produto.nome,
    descricao: produto.descricao ?? '',
    unidade_medida: produto.unidade_medida ?? '',
    quantidade_atual: String(produto.quantidade_atual),
    estoque_minimo: String(produto.estoque_minimo),
    valor_venda: String(produto.valor_venda),
  }
}

export type MovimentacaoTipo = 'Entrada' | 'Saída'

export interface MovimentacaoFormValues {
  produto_id: string
  quantidade: string
  valor_unitario: string
  valor_total: string
  observacao: string
  lancar_financeiro: boolean
  fornecedor: string
  forma_pagamento: string
  data_vencimento: string
  status_pagamento: 'Pago' | 'Pendente'
}

export function emptyMovimentacaoForm(): MovimentacaoFormValues {
  return {
    produto_id: '',
    quantidade: '',
    valor_unitario: '',
    valor_total: '',
    observacao: '',
    lancar_financeiro: false,
    fornecedor: '',
    forma_pagamento: 'Pix',
    data_vencimento: new Date().toISOString().slice(0, 10),
    status_pagamento: 'Pago',
  }
}
