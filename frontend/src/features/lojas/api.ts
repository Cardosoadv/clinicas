import { api, BASE_URL, type ApiEnvelope } from '../../lib/api'
import type { Loja, LojaFormValues } from './types'

export function fetchLojas(): Promise<ApiEnvelope<Loja[]>> {
  return api.get<Loja[]>('/lojas')
}

/** Loja usada como identidade visual do sistema (nome, endereço, logo). */
export function fetchLojaPrincipal(): Promise<ApiEnvelope<Loja | null>> {
  return api.get<Loja | null>('/lojas/principal')
}

function buildFormData(values: LojaFormValues, logoFile: File | null): FormData {
  const formData = new FormData()
  for (const [key, value] of Object.entries(values)) {
    formData.set(key, value)
  }
  if (logoFile) {
    formData.set('logo_file', logoFile)
  }
  return formData
}

export function createLoja(values: LojaFormValues, logoFile: File | null): Promise<ApiEnvelope> {
  return api.postForm('/lojas', buildFormData(values, logoFile))
}

export function updateLoja(id: number, values: LojaFormValues, logoFile: File | null): Promise<ApiEnvelope> {
  return api.postForm(`/lojas/${id}`, buildFormData(values, logoFile))
}

export function deleteLoja(id: number): Promise<ApiEnvelope> {
  return api.delete(`/lojas/${id}`)
}

export function lojaLogoUrl(id: number, filename: string): string {
  return `${BASE_URL}/lojas/${id}/logo/${filename}`
}
