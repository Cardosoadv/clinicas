import type { Cobranca } from '../faturamento/types'

export type AgendamentoStatus = 'pendente' | 'confirmado' | 'cancelado' | 'concluido'

export interface Agendamento {
  age_id: number
  age_grupo_id: string | null
  /** Pacote de serviços ao qual a sessão pertence (pré-agendamento do pacote) */
  pacote_id: number | null
  paciente_id: number
  age_data: string
  age_hora: string
  age_duracao: string
  age_obs: string | null
  age_lembrete: string | null
  age_status: AgendamentoStatus
  age_veterinario: number | null
  age_faturado: number
  age_recorrencia: string | null
  age_recorrencia_fim: string | null
  created_at: string
  updated_at: string
  deleted_at: string | null
  // presentes quando a listagem faz join com paciente/tutor/serviços
  paciente_nome?: string
  paciente_avatar?: string | null
  paciente_sexo?: string | null
  tutor_nome?: string
  paciente_endereco?: string | null
  age_servico?: string
  age_servico_ids?: string
}

export interface AgendaStats {
  hoje: number
  pendentes: number
}

export interface AgendamentosFiltro {
  status: AgendamentoStatus | ''
  data_inicio: string
  data_fim: string
  servico_id: string
  veterinario_id: string
  search: string
}

export interface DayData {
  appointments: Agendamento[]
  stats: AgendaStats
  date: string
}

export interface PacienteLookup {
  paciente_id: number
  paciente_nome: string
  paciente_avatar: string | null
  pet_resp_nome?: string | null
  pet_resp_tel?: string | null
  paciente_endereco?: string | null
}

export type AgendamentoRecorrencia = 'nenhuma' | 'semanal' | 'quinzenal' | 'mensal'

export interface AgendamentoFormValues {
  paciente_id: string
  age_servico: string[]
  age_data: string
  age_hora: string
  age_duracao: string
  age_obs: string
  age_lembrete: string
  age_status: AgendamentoStatus
  age_veterinario: string
  age_recorrencia: AgendamentoRecorrencia
  age_recorrencia_fim: string
  /** Dias da semana (0 = domingo ... 6 = sábado) para recorrência semanal/quinzenal */
  age_recorrencia_dias: number[]
}

export function emptyAgendamentoForm(data: string, hora: string): AgendamentoFormValues {
  return {
    paciente_id: '',
    age_servico: [],
    age_data: data,
    age_hora: hora,
    age_duracao: '30',
    age_obs: '',
    age_lembrete: '',
    age_status: 'pendente',
    age_veterinario: '',
    age_recorrencia: 'nenhuma',
    age_recorrencia_fim: '',
    age_recorrencia_dias: [],
  }
}

export interface ServicoOption {
  ser_id: number
  ser_nome: string
  ser_icone: string | null
  ser_valor: number
}

export interface EquipeOption {
  equ_id: number
  equ_nome: string
  equ_is_veterinario: number
}

export type FaturarStatus = 'Pendente' | 'Pago' | 'Cancelado'

/** Material (produto do estoque) utilizado no atendimento: medicamento, seringa, luva... */
export interface FaturarMaterial {
  produto_id: string
  quantidade: string
  valor_unitario: string
}

/** Material já lançado, como retornado pela API junto da cobrança */
export interface AgendamentoMaterial {
  id: number
  agendamento_id: number
  produto_id: number
  produto_nome: string
  unidade_medida: string | null
  quantidade: number
  valor_unitario: number
}

export type CobrancaAgendamento = Cobranca & { materiais?: AgendamentoMaterial[] }

export interface FaturarFormValues {
  /** Valor dos serviços; o total cobrado soma também os materiais */
  valor: string
  desconto: string
  forma_pagamento: string
  pacote_id: string
  parcelas: string
  vencimento: string
  status: FaturarStatus
  observacoes: string
  materiais: FaturarMaterial[]
}

export function totalMateriais(materiais: FaturarMaterial[]): number {
  return materiais.reduce((sum, item) => sum + Number(item.quantidade || 0) * Number(item.valor_unitario || 0), 0)
}

export function emptyFaturarForm(agendamento: Agendamento, servicosOptions: ServicoOption[]): FaturarFormValues {
  const today = new Date().toISOString().slice(0, 10)
  const servicoIds = agendamento.age_servico_ids ? agendamento.age_servico_ids.split(',').map(Number) : []
  const valorTotal = servicosOptions
    .filter((servico) => servicoIds.includes(servico.ser_id))
    .reduce((sum, servico) => sum + Number(servico.ser_valor), 0)

  return {
    valor: valorTotal ? String(valorTotal) : '',
    desconto: '0',
    // Sessão pré-agendada por um pacote: já sugere faturar com ele
    forma_pagamento: agendamento.pacote_id ? 'Pacote' : 'Pix',
    pacote_id: agendamento.pacote_id ? String(agendamento.pacote_id) : '',
    parcelas: '1',
    vencimento: today,
    status: 'Pago',
    observacoes: '',
    materiais: [],
  }
}

export function cobrancaToFaturarForm(cobranca: CobrancaAgendamento): FaturarFormValues {
  const materiais: FaturarMaterial[] = (cobranca.materiais ?? []).map((material) => ({
    produto_id: String(material.produto_id),
    quantidade: String(Number(material.quantidade)),
    valor_unitario: String(Number(material.valor_unitario)),
  }))
  // A cobrança guarda o total; o campo do formulário mostra só a parte dos serviços
  const valorServicos = Math.max(0, Number(cobranca.valor) - totalMateriais(materiais))

  return {
    valor: String(Math.round(valorServicos * 100) / 100),
    desconto: String(cobranca.desconto),
    forma_pagamento: cobranca.forma_pagamento,
    pacote_id: cobranca.pacote_id ? String(cobranca.pacote_id) : '',
    parcelas: cobranca.parcelas,
    vencimento: cobranca.vencimento,
    status: (cobranca.status === 'Atrasado' ? 'Pendente' : cobranca.status) as FaturarStatus,
    observacoes: cobranca.observacoes ?? '',
    materiais,
  }
}

export function agendamentoToFormValues(agendamento: Agendamento): AgendamentoFormValues {
  return {
    paciente_id: String(agendamento.paciente_id),
    age_servico: agendamento.age_servico_ids ? agendamento.age_servico_ids.split(',') : [],
    age_data: agendamento.age_data,
    age_hora: agendamento.age_hora.slice(0, 5),
    age_duracao: agendamento.age_duracao || '30',
    age_obs: agendamento.age_obs ?? '',
    age_lembrete: agendamento.age_lembrete ?? '',
    age_status: agendamento.age_status,
    age_veterinario: agendamento.age_veterinario ? String(agendamento.age_veterinario) : '',
    age_recorrencia: 'nenhuma',
    age_recorrencia_fim: '',
    age_recorrencia_dias: [],
  }
}
