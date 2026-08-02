export interface Paciente {
  paciente_id: number
  cliente_id: number
  paciente_avatar: string | null
  paciente_nome: string
  paciente_nascimento: string | null
  paciente_sexo: string | null
  paciente_raca: string | null
  paciente_especie: string | null
  paciente_pelagem: string | null
  paciente_porte: string | null
  paciente_sangue: string | null
  paciente_status: 'Ativo' | 'Inativo' | 'Suspenso'
  created_at: string
  updated_at: string
  deleted_at: string | null
  // presentes quando a listagem faz join com o tutor
  pet_resp_nome?: string | null
  pet_resp_tel?: string | null
  pet_resp_cpf?: string | null
}

export interface PacienteStats {
  total: number
  ativos: number
  inativos: number
  suspensos: number
}

export interface PacientesPainel {
  stats: PacienteStats
  recentes: Paciente[]
  aniversariantes: Paciente[]
  especies: string[]
}

/** '' representa "Todos" — o backend não filtra por status quando vazio. */
export type PacienteStatusFiltro = 'Ativo' | 'Inativo' | 'Suspenso' | ''

export interface PacienteFormValues {
  cliente_id: string
  paciente_nome: string
  paciente_especie: string
  paciente_raca: string
  paciente_sexo: string
  paciente_nascimento: string
  paciente_porte: string
  paciente_pelagem: string
  paciente_sangue: string
  paciente_status: string
}

export const emptyPacienteForm: PacienteFormValues = {
  cliente_id: '',
  paciente_nome: '',
  paciente_especie: '',
  paciente_raca: '',
  paciente_sexo: '',
  paciente_nascimento: '',
  paciente_porte: '',
  paciente_pelagem: '',
  paciente_sangue: '',
  paciente_status: 'Ativo',
}

export function pacienteToFormValues(paciente: Paciente): PacienteFormValues {
  return {
    cliente_id: String(paciente.cliente_id),
    paciente_nome: paciente.paciente_nome,
    paciente_especie: paciente.paciente_especie ?? '',
    paciente_raca: paciente.paciente_raca ?? '',
    paciente_sexo: paciente.paciente_sexo ?? '',
    paciente_nascimento: paciente.paciente_nascimento ?? '',
    paciente_porte: paciente.paciente_porte ?? '',
    paciente_pelagem: paciente.paciente_pelagem ?? '',
    paciente_sangue: paciente.paciente_sangue ?? '',
    paciente_status: paciente.paciente_status ?? 'Ativo',
  }
}
