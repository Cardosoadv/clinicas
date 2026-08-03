import { createContext, useContext } from 'react'
import type { ProntuarioRecord } from './types'

export interface ProntuarioContextValue {
  pacienteId: number
  record: ProntuarioRecord
  reload: () => void
  prefillEvolucao: string | null
  setPrefillEvolucao: (text: string | null) => void
}

export const ProntuarioContext = createContext<ProntuarioContextValue | null>(null)

export function useProntuario(): ProntuarioContextValue {
  const ctx = useContext(ProntuarioContext)
  if (!ctx) {
    throw new Error('useProntuario deve ser usado dentro de um ProntuarioLayout.')
  }
  return ctx
}
