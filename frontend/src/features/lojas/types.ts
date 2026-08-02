export interface Loja {
  id: number
  nome: string
  cnpj: string | null
  email: string | null
  telefone: string | null
  endereco: string | null
  cidade: string | null
  estado: string | null
  cep: string | null
  logo: string | null
  status: 'Ativo' | 'Inativo'
  created_at: string
  updated_at: string
}

export interface LojaFormValues {
  nome: string
  cnpj: string
  email: string
  telefone: string
  endereco: string
  cidade: string
  estado: string
  cep: string
  status: 'Ativo' | 'Inativo'
}

export const emptyLojaForm: LojaFormValues = {
  nome: '',
  cnpj: '',
  email: '',
  telefone: '',
  endereco: '',
  cidade: '',
  estado: '',
  cep: '',
  status: 'Ativo',
}

export function lojaToFormValues(loja: Loja): LojaFormValues {
  return {
    nome: loja.nome,
    cnpj: loja.cnpj ?? '',
    email: loja.email ?? '',
    telefone: loja.telefone ?? '',
    endereco: loja.endereco ?? '',
    cidade: loja.cidade ?? '',
    estado: loja.estado ?? '',
    cep: loja.cep ?? '',
    status: loja.status,
  }
}
