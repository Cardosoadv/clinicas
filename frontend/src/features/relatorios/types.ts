export interface Periodo {
  inicio: string
  fim: string
  inicio_fmt: string
  fim_fmt: string
  mes_referencia: string
}

export interface Lancamento {
  id: number
  data: string
  historico: string
  tipo: 'cobranca' | 'despesa'
  natureza: 'Entrada' | 'Saída'
  valor: number
  status: string
  forma_pgto: string
  categoria: string
  origem: string
}

export interface ExtratoData {
  periodo: Periodo
  lancamentos: Lancamento[]
  total_entradas: number
  total_saidas: number
  saldo_periodo: number
}

export interface DREData {
  periodo: Periodo
  receita_bruta: number
  descontos: number
  receita_liquida: number
  total_despesas: number
  resultado_liquido: number
  margem_liquida: number
  por_servico: { servico: string; total: number; qtd: number }[]
  por_categoria: { categoria: string; total: number; qtd: number }[]
  variacao_receita: number
  variacao_despesa: number
}

export interface Movimento {
  data: string
  historico: string
  entrada: number
  saida: number
  forma_pgto: string
  tipo: 'entrada' | 'saida'
  saldo_acumulado: number
}

export interface LivroCaixaData {
  periodo: Periodo
  saldo_inicial: number
  movimentos: Movimento[]
  total_entradas: number
  total_saidas: number
  saldo_final: number
}

export interface PeriodoFiltro {
  inicio: string
  fim: string
}

export function defaultPeriodo(): PeriodoFiltro {
  const now = new Date()
  const inicio = new Date(now.getFullYear(), now.getMonth(), 1)
  const fim = new Date(now.getFullYear(), now.getMonth() + 1, 0)
  return { inicio: inicio.toISOString().slice(0, 10), fim: fim.toISOString().slice(0, 10) }
}
