import type { Paciente } from '../pacientes/types'

export interface Evolucao {
  id: number
  paciente_id: number
  veterinario_id: number | null
  data_registro: string
  titulo: string
  descricao: string
  veterinario_nome: string | null
  created_at: string
  updated_at: string
}

export interface Imagem {
  id: number
  paciente_id: number
  titulo: string
  arquivo_url: string
  data_exame: string
  created_at: string
  updated_at: string
}

export interface Vacina {
  id: number
  paciente_id: number
  vacina_nome: string
  data_aplicacao: string
  data_proxima_dose: string | null
  lote: string | null
  fabricante: string | null
  observacoes: string | null
  created_at: string
  updated_at: string
}

export interface Peso {
  id: number
  paciente_id: number
  peso: number
  data_pesagem: string
  observacoes: string | null
  created_at: string
  updated_at: string
}

export interface PrescricaoItem {
  id: number
  prescricao_id: number
  medicamento: string
  dosagem: string | null
  frequencia: string | null
  duracao: string | null
  via_administracao: string | null
  particao: string | null
  created_at: string
  updated_at: string
}

export interface Prescricao {
  id: number
  paciente_id: number
  veterinario_id: number | null
  data_prescricao: string
  observacoes: string | null
  veterinario_nome: string | null
  created_at: string
  updated_at: string
}

export interface PrescricaoDetalhada extends Prescricao {
  itens: PrescricaoItem[]
}

export interface Odontograma {
  id: number
  paciente_id: number
  data_registro: string
  mapa_dentes: string
  observacoes: string | null
  created_at: string
  updated_at: string
}

export interface HistoricoAgendamento {
  age_id: number
  paciente_id: number
  age_data: string
  age_hora: string
  age_obs: string | null
  age_status: string
  age_servico: string | null
}

export interface HistoricoProcedimento {
  id: number
  paciente_id: number
  servico: string
  valor: number
  desconto: number | null
  data_servico: string
  status: string
}

export interface Historico {
  agendamentos: HistoricoAgendamento[]
  procedimentos: HistoricoProcedimento[]
}

export interface Estatisticas {
  consultas: number
  imagens: number
  total_investido: number
}

export interface ProntuarioRecord {
  pet: Paciente
  odontograma: Odontograma | null
  evolucoes: Evolucao[]
  imagens: Imagem[]
  vacinas: Vacina[]
  pesos: Peso[]
  prescricoes: Prescricao[]
  historico: Historico
  estatisticas: Estatisticas
}

export interface AnamneseFormValues {
  paciente_nome: string
  paciente_nascimento: string
  paciente_sexo: string
  paciente_especie: string
  paciente_avatar: string
  paciente_endereco: string
  paciente_queixa: string
  paciente_saude_obs: string
  paciente_status: string
}

export function pacienteToAnamneseForm(pet: Paciente): AnamneseFormValues {
  return {
    paciente_nome: pet.paciente_nome,
    paciente_nascimento: pet.paciente_nascimento ?? '',
    paciente_sexo: pet.paciente_sexo ?? '',
    paciente_especie: pet.paciente_especie ?? '',
    paciente_avatar: pet.paciente_avatar ?? '',
    paciente_endereco: pet.paciente_endereco ?? '',
    paciente_queixa: pet.paciente_queixa ?? '',
    paciente_saude_obs: pet.paciente_saude_obs ?? '',
    paciente_status: pet.paciente_status ?? 'Ativo',
  }
}

export interface VacinaFormValues {
  vacina_nome: string
  data_aplicacao: string
  data_proxima_dose: string
  lote: string
  fabricante: string
  observacoes: string
}

export const emptyVacinaForm: VacinaFormValues = {
  vacina_nome: '',
  data_aplicacao: new Date().toISOString().slice(0, 10),
  data_proxima_dose: '',
  lote: '',
  fabricante: '',
  observacoes: '',
}

export interface PesoFormValues {
  peso: string
  data_pesagem: string
  observacoes: string
}

export const emptyPesoForm: PesoFormValues = {
  peso: '',
  data_pesagem: new Date().toISOString().slice(0, 10),
  observacoes: '',
}

export interface PrescricaoItemFormValues {
  medicamento: string
  dosagem: string
  frequencia: string
  duracao: string
  via_administracao: string
  particao: string
}

export const emptyPrescricaoItem: PrescricaoItemFormValues = {
  medicamento: '',
  dosagem: '',
  frequencia: '',
  duracao: '',
  via_administracao: '',
  particao: '1 Inteiro',
}

export interface PrescricaoFormValues {
  data_prescricao: string
  observacoes: string
  itens: PrescricaoItemFormValues[]
}

export const emptyPrescricaoForm: PrescricaoFormValues = {
  data_prescricao: new Date().toISOString().slice(0, 10),
  observacoes: '',
  itens: [{ ...emptyPrescricaoItem }],
}

export interface EvolucaoFormValues {
  titulo: string
  descricao: string
}

export const emptyEvolucaoForm: EvolucaoFormValues = {
  titulo: '',
  descricao: '',
}

export const evolucaoTemplates: { label: string; titulo: string; descricao: string }[] = [
  { label: '📋 Consulta', titulo: 'Consulta de Rotina', descricao: 'Paciente apresentou-se em bom estado geral. ' },
  { label: '💉 Vacina', titulo: 'Aplicação de Vacina', descricao: 'Vacina aplicada sem intercorrências. ' },
  { label: '🔄 Retorno', titulo: 'Retorno', descricao: 'Paciente retornou para reavaliação. ' },
  { label: '🏥 Pós-Cirúrgico', titulo: 'Acompanhamento Pós-Cirúrgico', descricao: 'Paciente em recuperação pós-cirúrgica. ' },
]
