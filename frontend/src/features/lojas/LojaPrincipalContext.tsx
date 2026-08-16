import { createContext, useContext, useEffect, useState, type ReactNode } from 'react'
import { fetchLojaPrincipal, lojaLogoUrl } from './api'
import type { Loja } from './types'

interface LojaPrincipalContextValue {
  loja: Loja | null
  isLoading: boolean
}

const LojaPrincipalContext = createContext<LojaPrincipalContextValue>({ loja: null, isLoading: true })

export function LojaPrincipalProvider({ children }: { children: ReactNode }) {
  const [loja, setLoja] = useState<Loja | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    fetchLojaPrincipal()
      .then((res) => setLoja(res.data ?? null))
      .catch(() => setLoja(null))
      .finally(() => setIsLoading(false))
  }, [])

  return <LojaPrincipalContext.Provider value={{ loja, isLoading }}>{children}</LojaPrincipalContext.Provider>
}

export function useLojaPrincipal(): LojaPrincipalContextValue {
  return useContext(LojaPrincipalContext)
}

/** URL da logo da loja principal, ou null se não houver logo cadastrada. */
export function useLojaPrincipalLogoUrl(): string | null {
  const { loja } = useLojaPrincipal()
  if (!loja?.logo) return null
  return lojaLogoUrl(loja.id, loja.logo)
}
