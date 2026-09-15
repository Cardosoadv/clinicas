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

export type PacotePreagendarPeriodicidade = 'semanal' | 'mensal'

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
  preagendar: boolean
  preagendar_data_inicial: string
  preagendar_periodicidade: PacotePreagendarPeriodicidade
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
    preagendar: false,
    preagendar_data_inicial: new Date().toISOString().slice(0, 10),
    preagendar_periodicidade: 'semanal',
  }
}

export interface PacoteEditFormValues {
  nome: string
  status: PacoteStatus
  data_validade: string
  observacoes: string
  saldo_valor: string
  itens: ItemFormRow[]
}

export function pacoteToEditFormValues(pacote: PacoteDetalhes): PacoteEditFormValues {
  return {
    nome: pacote.nome,
    status: pacote.status,
    data_validade: pacote.data_validade ?? '',
    observacoes: pacote.observacoes ?? '',
    saldo_valor: String(pacote.saldo_valor ?? 0),
    itens:
      pacote.itens.length > 0
        ? pacote.itens.map((item) => ({
            servico_id: item.servico_id ? String(item.servico_id) : '',
            item_nome: item.item_nome ?? '',
            quantidade: String(item.quantidade_total),
            valor_unitario: String(item.valor_unitario),
          }))
        : [{ servico_id: '', item_nome: '', quantidade: '1', valor_unitario: '' }],
  }
}
