export interface AuthUser {
  id: number
  username: string | null
  email: string
  /** Permissões efetivas do Shield (ex.: `documentacao.editar`). */
  permissoes?: string[]
}

export interface LoginPayload {
  email: string
  password: string
  remember?: boolean
}
