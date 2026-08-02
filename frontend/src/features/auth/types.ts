export interface AuthUser {
  id: number
  username: string | null
  email: string
}

export interface LoginPayload {
  email: string
  password: string
  remember?: boolean
}
