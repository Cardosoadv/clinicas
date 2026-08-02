export interface Servico {
  ser_id: number
  ser_nome: string
  ser_icone: string | null
  ser_valor: number
  ser_tempo_estimado: number | null
  ser_descricao: string | null
  ser_status: 'Ativo' | 'Inativo'
  created_at: string
  updated_at: string
  deleted_at: string | null
}

export interface ServicoProduto {
  id: number
  ser_id: number
  produto_id: number
  quantidade: number
}

export interface ServicoComProdutos extends Servico {
  produtos: ServicoProduto[]
}

export interface ServicoFormValues {
  ser_nome: string
  ser_icone: string
  ser_valor: string
  ser_tempo_estimado: string
  ser_descricao: string
  ser_status: 'Ativo' | 'Inativo'
}

export const emptyServicoForm: ServicoFormValues = {
  ser_nome: '',
  ser_icone: '🐾',
  ser_valor: '',
  ser_tempo_estimado: '',
  ser_descricao: '',
  ser_status: 'Ativo',
}

export function servicoToFormValues(servico: Servico): ServicoFormValues {
  return {
    ser_nome: servico.ser_nome,
    ser_icone: servico.ser_icone ?? '',
    ser_valor: String(servico.ser_valor),
    ser_tempo_estimado: servico.ser_tempo_estimado ? String(servico.ser_tempo_estimado) : '',
    ser_descricao: servico.ser_descricao ?? '',
    ser_status: servico.ser_status,
  }
}

export interface BomItem {
  produto_id: string
  quantidade: string
}

export interface ProdutoOption {
  id: number
  nome: string
  unidade_medida: string | null
}
