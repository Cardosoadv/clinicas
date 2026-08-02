export type CobrancaStatus = 'Pendente' | 'Pago' | 'Atrasado' | 'Cancelado'
export type DespesaStatus = 'Pendente' | 'Pago' | 'Cancelado'

export interface Cobranca {
  id: number
  paciente_id: number
  pacote_id: number | null
  servico: string
  servico_id: number | null
  valor: number
  desconto: number
  data_servico: string
  forma_pagamento: string
  parcelas: string
  vencimento: string
  status: CobrancaStatus
  observacoes: string | null
  created_at: string
  updated_at: string
  paciente_nome?: string
}

export interface Despesa {
  id: number
  descricao: string
  categoria: string
  fornecedor: string | null
  valor: number
  data_vencimento: string
  forma_pagamento: string | null
  status: DespesaStatus
  des_grupo_id: string | null
  des_parcela_num: number | null
  des_parcela_total: number | null
  des_recorrencia: string | null
  comprovante: string | null
  created_at: string
  updated_at: string
}

export interface Nota {
  id: number
  hash: string
  cobranca_id: number | null
  paciente_id: number
  tipo: 'Recibo' | 'NFS-e'
  cpf_responsavel: string | null
  data_emissao: string
  descricao: string
  valor: number
  created_at: string
}

export interface DashboardKpis {
  receitas: number
  despesas: number
  saldo: number
  a_receber: number
  vencidos: number
}

export interface DashboardData {
  kpis: DashboardKpis
  transacoes: Array<(Cobranca | Despesa) & { origem?: string }>
  notas: Nota[]
  stats: {
    por_servico: { servico: string; total: number }[]
    formas_pgto: { forma_pagamento: string; total: number }[]
    top_pets: { paciente_nome: string; paciente_avatar: string | null; qtd: number; total: number }[]
    chart_data: { points: { label: string; receita: number; despesa: number }[]; max_value: number }
  }
  recebiveis: Cobranca[]
}

export interface LancamentosData {
  cobrancas: Cobranca[]
  despesas: Despesa[]
}

export function isDespesa(item: Cobranca | Despesa): item is Despesa {
  return 'descricao' in item && 'categoria' in item
}

export const categoriasDespesa = [
  '🏢 Infraestrutura',
  '💊 Insumos Pet',
  '💡 Utilidades',
  '🔧 Equipamentos',
  '👥 Pessoal',
  '📞 Comunicação',
  '💻 Sistema operacional',
  '📋 Impostos e taxas',
  '🩺 Saúde/Veterinário',
  '📦 Estoque',
  '🚗 Transporte',
  '🏠 Aluguel',
  '⚖️ Jurídico',
  '📝 Outra',
]

export const formasPagamentoCobranca = ['Pix', 'Dinheiro', 'Cartão de Crédito', 'Cartão de Débito', 'Boleto', 'Pacote']
export const formasPagamentoDespesa = ['Pix', 'Boleto', 'Dinheiro', 'Cartão Empresarial', 'Transferência', 'Débito Automático']

export interface CobrancaFormValues {
  paciente_id: string
  servico: string
  servico_id: string
  valor: string
  desconto: string
  data_servico: string
  forma_pagamento: string
  parcelas: string
  vencimento: string
  status: CobrancaStatus
  observacoes: string
  pacote_id: string
}

export function emptyCobrancaForm(): CobrancaFormValues {
  const today = new Date().toISOString().slice(0, 10)
  return {
    paciente_id: '',
    servico: '',
    servico_id: '',
    valor: '',
    desconto: '0',
    data_servico: today,
    forma_pagamento: 'Pix',
    parcelas: '1',
    vencimento: today,
    status: 'Pendente',
    observacoes: '',
    pacote_id: '',
  }
}

export function cobrancaToFormValues(cobranca: Cobranca): CobrancaFormValues {
  return {
    paciente_id: String(cobranca.paciente_id),
    servico: cobranca.servico,
    servico_id: cobranca.servico_id ? String(cobranca.servico_id) : '',
    valor: String(cobranca.valor),
    desconto: String(cobranca.desconto),
    data_servico: cobranca.data_servico,
    forma_pagamento: cobranca.forma_pagamento,
    parcelas: cobranca.parcelas,
    vencimento: cobranca.vencimento,
    status: cobranca.status,
    observacoes: cobranca.observacoes ?? '',
    pacote_id: cobranca.pacote_id ? String(cobranca.pacote_id) : '',
  }
}

export interface DespesaFormValues {
  descricao: string
  categoria: string
  fornecedor: string
  valor: string
  data_vencimento: string
  forma_pagamento: string
  status: DespesaStatus
}

export function emptyDespesaForm(): DespesaFormValues {
  return {
    descricao: '',
    categoria: categoriasDespesa[0],
    fornecedor: '',
    valor: '',
    data_vencimento: new Date().toISOString().slice(0, 10),
    forma_pagamento: 'Pix',
    status: 'Pendente',
  }
}

export function despesaToFormValues(despesa: Despesa): DespesaFormValues {
  return {
    descricao: despesa.descricao,
    categoria: despesa.categoria,
    fornecedor: despesa.fornecedor ?? '',
    valor: String(despesa.valor),
    data_vencimento: despesa.data_vencimento,
    forma_pagamento: despesa.forma_pagamento ?? '',
    status: despesa.status,
  }
}

export interface NotaFormValues {
  paciente_id: string
  tipo: 'Recibo' | 'NFS-e'
  cpf_responsavel: string
  data_emissao: string
  descricao: string
  valor: string
}

export function emptyNotaForm(): NotaFormValues {
  return {
    paciente_id: '',
    tipo: 'Recibo',
    cpf_responsavel: '',
    data_emissao: new Date().toISOString().slice(0, 10),
    descricao: '',
    valor: '',
  }
}
