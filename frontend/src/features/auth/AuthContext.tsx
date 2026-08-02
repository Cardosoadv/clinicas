import { createContext, useContext, useEffect, useState, type ReactNode } from 'react'
import { fetchCurrentUser, login as loginRequest, logout as logoutRequest } from './api'
import type { AuthUser, LoginPayload } from './types'

interface AuthContextValue {
  user: AuthUser | null
  isLoading: boolean
  login: (payload: LoginPayload) => Promise<void>
  logout: () => Promise<void>
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  // Ao carregar a aplicação, pergunta ao backend se já existe uma sessão
  // válida (cookie) — evita mandar o usuário pro login a cada refresh.
  useEffect(() => {
    fetchCurrentUser()
      .then((res) => setUser(res.data ?? null))
      .catch(() => setUser(null))
      .finally(() => setIsLoading(false))
  }, [])

  async function login(payload: LoginPayload) {
    const res = await loginRequest(payload)
    setUser(res.user)
  }

  async function logout() {
    await logoutRequest()
    setUser(null)
  }

  return <AuthContext.Provider value={{ user, isLoading, login, logout }}>{children}</AuthContext.Provider>
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext)
  if (!ctx) {
    throw new Error('useAuth deve ser usado dentro de um AuthProvider')
  }
  return ctx
}
