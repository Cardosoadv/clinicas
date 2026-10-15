/**
 * Cálculo da prévia de despesas parceladas.
 * Espelha a regra do backend (FatService::resolverParcelas): divisão em centavos com a
 * última parcela absorvendo a diferença, e vencimentos mensais sem "pular" mês.
 */

export type Periodicidade = 'mensal' | 'quinzenal' | 'semanal'

export const MAX_PARCELAS = 60

export interface ParcelaForm {
  vencimento: string
  valor: string
}

export function paraCentavos(valor: string | number): number {
  const numero = Number(valor)
  return Number.isFinite(numero) ? Math.round(numero * 100) : 0
}

function formatarReais(centavos: number): string {
  return (centavos / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function parseData(data: string): { ano: number; mes: number; dia: number } | null {
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(data)
  if (!match) return null
  const [ano, mes, dia] = [Number(match[1]), Number(match[2]), Number(match[3])]
  const real = new Date(Date.UTC(ano, mes - 1, dia))
  const valida = real.getUTCFullYear() === ano && real.getUTCMonth() === mes - 1 && real.getUTCDate() === dia
  return valida ? { ano, mes, dia } : null
}

function formatarData(date: Date): string {
  return date.toISOString().slice(0, 10)
}

/** Divide o total em `qtd` parcelas; a última absorve a diferença de centavos. */
export function dividirEmCentavos(totalCentavos: number, qtd: number): number[] {
  const base = Math.floor(totalCentavos / qtd)
  const diferenca = totalCentavos - base * qtd
  return Array.from({ length: qtd }, (_, i) => base + (i === qtd - 1 ? diferenca : 0))
}

/** Vencimento da parcela `indice` (0 = primeira). Mensal mantém o dia, limitado ao fim do mês. */
export function vencimentoDaParcela(inicio: string, periodicidade: Periodicidade, indice: number): string {
  const data = parseData(inicio)
  if (!data) return ''

  if (periodicidade === 'semanal') {
    return formatarData(new Date(Date.UTC(data.ano, data.mes - 1, data.dia + 7 * indice)))
  }
  if (periodicidade === 'quinzenal') {
    return formatarData(new Date(Date.UTC(data.ano, data.mes - 1, data.dia + 14 * indice)))
  }

  const mesAlvo = data.mes - 1 + indice
  const ano = data.ano + Math.floor(mesAlvo / 12)
  const mes = ((mesAlvo % 12) + 12) % 12
  const ultimoDia = new Date(Date.UTC(ano, mes + 1, 0)).getUTCDate()
  return formatarData(new Date(Date.UTC(ano, mes, Math.min(data.dia, ultimoDia))))
}

/** Gera a prévia. Retorna lista vazia enquanto os dados estiverem incompletos ou inválidos. */
export function gerarParcelas(total: string, qtd: string, inicio: string, periodicidade: Periodicidade): ParcelaForm[] {
  const quantidade = Number(qtd)
  const totalCentavos = paraCentavos(total)

  if (!Number.isInteger(quantidade) || quantidade < 2 || quantidade > MAX_PARCELAS) return []
  if (totalCentavos < quantidade || !parseData(inicio)) return []

  return dividirEmCentavos(totalCentavos, quantidade).map((centavos, i) => ({
    vencimento: vencimentoDaParcela(inicio, periodicidade, i),
    valor: (centavos / 100).toFixed(2),
  }))
}

/** Mensagem de erro do parcelamento, ou null se estiver consistente. */
export function validarParcelamento(total: string, parcelas: ParcelaForm[]): string | null {
  if (parcelas.length === 0) {
    return `Informe o valor total, a quantidade de parcelas (2 a ${MAX_PARCELAS}) e o primeiro vencimento.`
  }
  if (parcelas.some((p) => paraCentavos(p.valor) <= 0)) {
    return 'Todas as parcelas devem ter valor maior que zero.'
  }
  if (parcelas.some((p) => !parseData(p.vencimento))) {
    return 'Informe o vencimento de todas as parcelas.'
  }

  const diferenca = parcelas.reduce((soma, p) => soma + paraCentavos(p.valor), 0) - paraCentavos(total)
  if (diferenca !== 0) {
    const sentido = diferenca > 0 ? 'acima' : 'abaixo'
    return `A soma das parcelas está ${formatarReais(Math.abs(diferenca))} ${sentido} do valor total.`
  }

  return null
}
