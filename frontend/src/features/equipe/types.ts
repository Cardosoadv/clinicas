export interface MembroEquipe {
  equ_id: number
  user_id: number | null
  equ_pronome: string | null
  equ_nome: string
  equ_crmv: string | null
  equ_especialidade: string | null
  equ_is_veterinario: number
  equ_status: 'Ativo' | 'Inativo'
  created_at: string
  updated_at: string
}

export interface UnlinkedUser {
  id: number
  username: string | null
  email: string
}

export const honorificos = ['', 'Dr.', 'Dra.', 'Sr.', 'Sra.']

export interface EquipeFormValues {
  user_id: string
  novoUsuarioEmail: string
  novoUsuarioSenha: string
  equ_pronome: string
  equ_nome: string
  equ_crmv: string
  equ_especialidade: string
  equ_is_veterinario: boolean
  equ_status: 'Ativo' | 'Inativo'
}

export const emptyEquipeForm: EquipeFormValues = {
  user_id: '',
  novoUsuarioEmail: '',
  novoUsuarioSenha: '',
  equ_pronome: '',
  equ_nome: '',
  equ_crmv: '',
  equ_especialidade: '',
  equ_is_veterinario: false,
  equ_status: 'Ativo',
}

export function membroToFormValues(membro: MembroEquipe): EquipeFormValues {
  return {
    user_id: membro.user_id ? String(membro.user_id) : '',
    novoUsuarioEmail: '',
    novoUsuarioSenha: '',
    equ_pronome: membro.equ_pronome ?? '',
    equ_nome: membro.equ_nome,
    equ_crmv: membro.equ_crmv ?? '',
    equ_especialidade: membro.equ_especialidade ?? '',
    equ_is_veterinario: Boolean(membro.equ_is_veterinario),
    equ_status: membro.equ_status,
  }
}

export function formatNomeComPronome(membro: MembroEquipe): string {
  return membro.equ_pronome ? `${membro.equ_pronome} ${membro.equ_nome}` : membro.equ_nome
}
