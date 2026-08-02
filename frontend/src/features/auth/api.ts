import { api, type ApiEnvelope } from '../../lib/api'
import type { AuthUser, LoginPayload } from './types'

interface LoginResponse extends ApiEnvelope {
  user: AuthUser
}

export function login(payload: LoginPayload): Promise<LoginResponse> {
  return api.post<never>('/auth/login', payload) as Promise<LoginResponse>
}

export function logout(): Promise<ApiEnvelope> {
  return api.post('/auth/logout')
}

export function fetchCurrentUser(): Promise<ApiEnvelope<AuthUser | null>> {
  return api.get<AuthUser | null>('/auth/me')
}
