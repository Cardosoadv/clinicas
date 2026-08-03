import { createContext, useContext } from 'react'
import type { DashboardData, LancamentosData, Nota } from './types'

export interface FaturamentoContextValue {
  dashboard: DashboardData | null
  lancamentos: LancamentosData | null
  notas: Nota[] | null
  isLoading: boolean
  error: string | null
  reloadDashboard: () => void
  reloadLancamentos: () => void
  reloadNotas: () => void
}

export const FaturamentoContext = createContext<FaturamentoContextValue | null>(null)

export function useFaturamento(): FaturamentoContextValue {
  const ctx = useContext(FaturamentoContext)
  if (!ctx) {
    throw new Error('useFaturamento deve ser usado dentro de um FaturamentoLayout.')
  }
  return ctx
}
