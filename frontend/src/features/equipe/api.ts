import { api, type ApiEnvelope } from '../../lib/api'
import type { EquipeFormValues, MembroEquipe, UnlinkedUser } from './types'

export function fetchEquipe(): Promise<ApiEnvelope<MembroEquipe[]>> {
  return api.get<MembroEquipe[]>('/equipe')
}

export function fetchUnlinkedUsers(): Promise<ApiEnvelope<UnlinkedUser[]>> {
  return api.get<UnlinkedUser[]>('/equipe/nao-vinculados')
}

function toPayload(values: EquipeFormValues): Record<string, unknown> {
  const payload: Record<string, unknown> = {
    equ_pronome: values.equ_pronome || null,
    equ_nome: values.equ_nome,
    equ_crmv: values.equ_crmv || null,
    equ_especialidade: values.equ_especialidade || null,
    equ_is_veterinario: values.equ_is_veterinario ? 1 : 0,
    equ_status: values.equ_status,
  }

  if (values.user_id) {
    payload.user_id = Number(values.user_id)
  } else if (values.novoUsuarioEmail) {
    payload.email = values.novoUsuarioEmail
    if (values.novoUsuarioSenha) {
      payload.password = values.novoUsuarioSenha
    }
  }

  return payload
}

export function createMembro(values: EquipeFormValues): Promise<ApiEnvelope> {
  return api.post('/equipe', toPayload(values))
}

export function updateMembro(id: number, values: EquipeFormValues): Promise<ApiEnvelope> {
  return api.put(`/equipe/${id}`, toPayload(values))
}

export function deleteMembro(id: number): Promise<ApiEnvelope> {
  return api.delete(`/equipe/${id}`)
}
