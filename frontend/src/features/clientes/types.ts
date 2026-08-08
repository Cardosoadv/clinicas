export interface Cliente {
  id: number
  nome: string
  razao_social: string | null
  apelido: string | null
  cpf: string | null
  cnpj: string | null
  telefones: string | null
  emails: string | null
  nascimento: string | null
  cidade: string | null
  estado: string | null
  observacoes: string | null
  created_at: string
  updated_at: string
  deleted_at: string | null
}

export interface ClienteDuplicadoGrupo {
  nome: string
  telefones: string
  quantidade: number
  clientes: Cliente[]
}

export interface ClienteNota {
  id: number
  cliente_id: number
  texto: string
  autor: string | null
  created_at: string
  updated_at: string
}

export interface Comunicacao {
  id: number
  cliente_id: number
  canal: string
  tipo: 'agendamento' | 'cobranca' | 'pos_consulta' | 'vacina' | 'aniversario' | 'manual'
  paciente_nome: string | null
  mensagem: string
  created_at: string
}

export const comunicacaoTipoLabel: Record<Comunicacao['tipo'], string> = {
  agendamento: 'Lembrete de agendamento',
  cobranca: 'Cobrança',
  pos_consulta: 'Pós-consulta',
  vacina: 'Lembrete de vacina',
  aniversario: 'Aniversário do paciente',
  manual: 'Mensagem manual',
}

export interface ClienteStats {
  total: number
  ativos: number
  pessoa_fisica: number
  pessoa_juridica: number
}

export interface ClientesPainel {
  stats: ClienteStats
  recentes: Cliente[]
  aniversariantes: Cliente[]
}

export type ClienteStatusFiltro = 'ativos' | 'inativos' | 'todos'
export type ClienteTipoFiltro = 'fisica' | 'juridica' | ''

export interface ClienteFormValues {
  nome: string
  razao_social: string
  apelido: string
  cpf: string
  cnpj: string
  telefones: string
  emails: string
  cidade: string
  estado: string
  observacoes: string
}

export const emptyClienteForm: ClienteFormValues = {
  nome: '',
  razao_social: '',
  apelido: '',
  cpf: '',
  cnpj: '',
  telefones: '',
  emails: '',
  cidade: '',
  estado: '',
  observacoes: '',
}

export function clienteToFormValues(cliente: Cliente): ClienteFormValues {
  return {
    nome: cliente.nome,
    razao_social: cliente.razao_social ?? '',
    apelido: cliente.apelido ?? '',
    cpf: cliente.cpf ?? '',
    cnpj: cliente.cnpj ?? '',
    telefones: cliente.telefones ?? '',
    emails: cliente.emails ?? '',
    cidade: cliente.cidade ?? '',
    estado: cliente.estado ?? '',
    observacoes: cliente.observacoes ?? '',
  }
}
