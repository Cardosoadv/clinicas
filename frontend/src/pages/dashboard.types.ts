import type { Agendamento } from '../features/agenda/types'
import type { Produto } from '../features/estoque/types'
import type { Paciente } from '../features/pacientes/types'
import type { Vacina } from '../features/prontuarios/types'

export interface HomeKpis {
  hoje: number
  ativos: number
  receita: number
  pendentes: number
}

export interface ServicoStat {
  service_id: number
  service: string
  count: number
}

export interface AniversarianteHome {
  paciente_id: number
  paciente_nome: string
  paciente_nascimento: string | null
  paciente_avatar: string | null
  pet_resp_nome: string | null
  pet_resp_tel: string | null
}

export interface VacinaAlerta extends Vacina {
  paciente_nome: string
  paciente_avatar?: string | null
  tutor_nome: string
  tutor_tel: string | null
}

export interface HomeDashboardData {
  kpis: HomeKpis
  upcoming: Agendamento[]
  hoje: Agendamento[]
  servicos: ServicoStat[]
  recentes: Paciente[]
  aniversariantes: AniversarianteHome[]
  alertasEstoque: Produto[]
  proximasVacinas: VacinaAlerta[]
  posConsulta: Agendamento[]
}
