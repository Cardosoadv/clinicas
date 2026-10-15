/** Helpers puros da lista de despesas: status exibido, rótulo de parcela, navegação de mês e escopo de grupo. */

export type Escopo = 'apenas_esta' | 'esta_e_seguintes' | 'todas'
export type DespesaStatusExibido = 'Pendente' | 'Pago' | 'Cancelado' | 'Atrasado'

export const escopoLabels: Record<Escopo, string> = {
  apenas_esta: 'Apenas esta parcela',
  esta_e_seguintes: 'Esta e as seguintes',
  todas: 'Todas as parcelas',
}

export interface ParcelaDoGrupo {
  id: number
  des_parcela_num: number | null
  data_vencimento: string
  status: string
}

/** Data local de hoje no formato AAAA-MM-DD. */
export function hojeISO(agora: Date = new Date()): string {
  const mes = String(agora.getMonth() + 1).padStart(2, '0')
  const dia = String(agora.getDate()).padStart(2, '0')
  return `${agora.getFullYear()}-${mes}-${dia}`
}

/** "Atrasado" não existe no banco: é uma despesa Pendente com vencimento anterior a hoje. */
export function statusExibido(status: string, vencimento: string, hoje: string): DespesaStatusExibido {
  if (status === 'Pendente' && vencimento < hoje) return 'Atrasado'
  return status as DespesaStatusExibido
}

/** "Parcela 2/6", ou null para despesa sem parcelamento. */
export function rotuloParcela(despesa: { des_parcela_num: number | null; des_parcela_total: number | null }): string | null {
  if (despesa.des_parcela_num === null || despesa.des_parcela_total === null) return null
  return `Parcela ${despesa.des_parcela_num}/${despesa.des_parcela_total}`
}

/** Mês (AAAA-MM) da data informada. */
export function mesDe(data: string): string {
  return data.slice(0, 7)
}

/** Soma `delta` meses a um mês AAAA-MM. */
export function deslocarMes(mes: string, delta: number): string {
  const [ano, m] = mes.split('-').map(Number)
  const total = ano * 12 + (m - 1) + delta
  return `${Math.floor(total / 12)}-${String((total % 12) + 1).padStart(2, '0')}`
}

/**
 * Parcelas do grupo atingidas pelo escopo a partir de `atual` (mesma regra do backend).
 * Em recorrências, sem número de parcela, "esta e seguintes" usa a data de vencimento.
 */
export function parcelasDoEscopo<T extends ParcelaDoGrupo>(grupo: T[], atual: T, escopo: Escopo): T[] {
  if (escopo === 'apenas_esta' || grupo.length === 0) return [atual]
  if (escopo === 'todas') return grupo

  return grupo.filter((d) =>
    atual.des_parcela_num !== null && d.des_parcela_num !== null
      ? d.des_parcela_num >= atual.des_parcela_num
      : d.data_vencimento >= atual.data_vencimento,
  )
}

export interface ResumoEscopo {
  afetadas: number
  pendentes: number
  pagas: number
}

export function resumoEscopo<T extends ParcelaDoGrupo>(grupo: T[], atual: T, escopo: Escopo): ResumoEscopo {
  const alvo = parcelasDoEscopo(grupo, atual, escopo)
  return {
    afetadas: alvo.length,
    pendentes: alvo.filter((d) => d.status === 'Pendente').length,
    pagas: alvo.filter((d) => d.status === 'Pago').length,
  }
}

export function pluralParcelas(n: number): string {
  return n === 1 ? '1 parcela' : `${n} parcelas`
}
