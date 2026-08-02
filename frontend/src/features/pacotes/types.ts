export type PacoteTipo = 'Serviços' | 'Crédito'
export type PacoteStatus = 'Pendente' | 'Ativo' | 'Esgotado' | 'Cancelado'

export interface Pacote {
  id: number
  paciente_id: number
  nome: string
  tipo: PacoteTipo
  saldo_valor: number
  data_validade: string | null
  status: PacoteStatus
  observacoes: string | null
  created_at: string
  updated_at: string
  deleted_at: string | null
  paciente_nome?: string
  paciente_avatar?: string | null
}

export interface PacoteItem {
  id: number
  pacote_id: number
  servico_id: number | null
  item_nome: string | null
  quantidade_total: number
  quantidade_usada: number
  valor_unitario: number
}

export interface PacoteUso {
  id: number
  pacote_id: number
  servico_id: number | null
  quantidade: number
  valor_descontado: number
  data_uso: string
  observacao: string | null
}

export interface PacoteDetalhes extends Pacote {
  itens: (PacoteItem & { servico_nome?: string | null })[]
  historico: (PacoteUso & { servico_nome?: string | null })[]
}

export interface ItemFormRow {
  servico_id: string
  item_nome: string
  quantidade: string
  valor_unitario: string
}

export interface PacoteFormValues {
  paciente_id: string
  nome: string
  tipo: PacoteTipo
  data_validade: string
  observacoes: string
  itens: ItemFormRow[]
  valor_total: string
  desconto: string
  forma_pagamento: string
  vencimento: string
}

export function emptyPacoteForm(): PacoteFormValues {
  return {
    paciente_id: '',
    nome: '',
    tipo: 'Serviços',
    data_validade: '',
    observacoes: '',
    itens: [{ servico_id: '', item_nome: '', quantidade: '1', valor_unitario: '' }],
    valor_total: '',
    desconto: '0',
    forma_pagamento: 'Cartão de Crédito',
    vencimento: new Date().toISOString().slice(0, 10),
  }
}
