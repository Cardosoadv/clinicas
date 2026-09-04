import type { Cobranca } from '../faturamento/types'

export type AgendamentoStatus = 'pendente' | 'confirmado' | 'cancelado' | 'concluido'

export interface Agendamento {
  age_id: number
  age_grupo_id: string | null
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

export interface FaturarFormValues {
  valor: string
  desconto: string
  forma_pagamento: string
  pacote_id: string
  parcelas: string
  vencimento: string
  status: FaturarStatus
  observacoes: string
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
    forma_pagamento: 'Pix',
    pacote_id: '',
    parcelas: '1',
    vencimento: today,
    status: 'Pago',
    observacoes: '',
  }
}

export function cobrancaToFaturarForm(cobranca: Cobranca): FaturarFormValues {
  return {
    valor: String(cobranca.valor),
    desconto: String(cobranca.desconto),
    forma_pagamento: cobranca.forma_pagamento,
    pacote_id: cobranca.pacote_id ? String(cobranca.pacote_id) : '',
    parcelas: cobranca.parcelas,
    vencimento: cobranca.vencimento,
    status: (cobranca.status === 'Atrasado' ? 'Pendente' : cobranca.status) as FaturarStatus,
    observacoes: cobranca.observacoes ?? '',
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
  }
}
