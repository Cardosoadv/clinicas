import { api, type ApiEnvelope } from '../lib/api'
import type { HomeDashboardData } from './dashboard.types'

export function fetchHomeDashboard(): Promise<ApiEnvelope<HomeDashboardData>> {
  return api.get<HomeDashboardData>('/dashboard')
}
