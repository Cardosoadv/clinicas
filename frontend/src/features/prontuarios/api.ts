import { api, type ApiEnvelope } from '../../lib/api'
import type { Paciente } from '../pacientes/types'
import type {
  AnamneseFormValues,
  EvolucaoFormValues,
  PesoFormValues,
  PrescricaoDetalhada,
  PrescricaoFormValues,
  ProntuarioRecord,
  VacinaFormValues,
} from './types'

export function fetchProntuarioPacientes(search: string): Promise<ApiEnvelope<Paciente[]>> {
  const params = new URLSearchParams()
  if (search.trim()) params.set('search', search.trim())
  const qs = params.toString()
  return api.get<Paciente[]>(`/prontuarios${qs ? `?${qs}` : ''}`)
}

export function fetchProntuario(pacienteId: number): Promise<ApiEnvelope<ProntuarioRecord>> {
  return api.get<ProntuarioRecord>(`/prontuarios/pacientes/${pacienteId}`)
}

export function saveAnamnese(pacienteId: number, values: AnamneseFormValues, avatarFile: File | null): Promise<ApiEnvelope> {
  const formData = new FormData()
  formData.set('paciente_nome', values.paciente_nome)
  formData.set('paciente_nascimento', values.paciente_nascimento)
  formData.set('paciente_sexo', values.paciente_sexo)
  formData.set('paciente_especie', values.paciente_especie)
  formData.set('paciente_avatar', values.paciente_avatar)
  formData.set('paciente_endereco', values.paciente_endereco)
  formData.set('paciente_queixa', values.paciente_queixa)
  formData.set('paciente_saude_obs', values.paciente_saude_obs)
  formData.set('paciente_status', values.paciente_status)
  if (avatarFile) formData.set('avatar', avatarFile)

  return api.postForm(`/prontuarios/pacientes/${pacienteId}/anamnese`, formData)
}

export function addVacina(pacienteId: number, values: VacinaFormValues): Promise<ApiEnvelope> {
  return api.post(`/prontuarios/pacientes/${pacienteId}/vacinas`, {
    vacina_nome: values.vacina_nome,
    data_aplicacao: values.data_aplicacao,
    data_proxima_dose: values.data_proxima_dose || null,
    lote: values.lote || null,
    fabricante: values.fabricante || null,
    observacoes: values.observacoes || null,
  })
}

export function deleteVacina(id: number): Promise<ApiEnvelope> {
  return api.delete(`/prontuarios/vacinas/${id}`)
}

export function addPeso(pacienteId: number, values: PesoFormValues): Promise<ApiEnvelope> {
  return api.post(`/prontuarios/pacientes/${pacienteId}/pesos`, {
    peso: Number(values.peso),
    data_pesagem: values.data_pesagem,
    observacoes: values.observacoes || null,
  })
}

export function deletePeso(id: number): Promise<ApiEnvelope> {
  return api.delete(`/prontuarios/pesos/${id}`)
}

export function addPrescricao(pacienteId: number, values: PrescricaoFormValues): Promise<ApiEnvelope> {
  return api.post(`/prontuarios/pacientes/${pacienteId}/prescricoes`, {
    data_prescricao: values.data_prescricao,
    observacoes: values.observacoes || null,
    itens: values.itens.filter((item) => item.medicamento.trim()),
  })
}

export function fetchPrescricao(id: number): Promise<ApiEnvelope<PrescricaoDetalhada>> {
  return api.get<PrescricaoDetalhada>(`/prontuarios/prescricoes/${id}`)
}

export function addEvolucao(pacienteId: number, values: EvolucaoFormValues): Promise<ApiEnvelope> {
  return api.post(`/prontuarios/pacientes/${pacienteId}/evolucoes`, values)
}

export function editEvolucao(id: number, values: EvolucaoFormValues): Promise<ApiEnvelope> {
  return api.put(`/prontuarios/evolucoes/${id}`, values)
}

export function deleteEvolucao(id: number): Promise<ApiEnvelope> {
  return api.delete(`/prontuarios/evolucoes/${id}`)
}

export function addImagem(pacienteId: number, titulo: string, dataExame: string, arquivo: File): Promise<ApiEnvelope> {
  const formData = new FormData()
  formData.set('titulo', titulo)
  if (dataExame) formData.set('data_exame', dataExame)
  formData.set('arquivo', arquivo)
  return api.postForm(`/prontuarios/pacientes/${pacienteId}/imagens`, formData)
}

export function pacienteAvatarUrl(pacienteId: number, filename: string): string {
  return `${import.meta.env.VITE_API_BASE_URL}/pacientes/${pacienteId}/avatar/${filename}`
}

export function imagemUrl(pacienteId: number, filename: string): string {
  return `${import.meta.env.VITE_API_BASE_URL}/prontuarios/pacientes/${pacienteId}/imagens/${filename}`
}
