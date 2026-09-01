import type { AgendamentoStatus } from './types'

export const statusLabels: Record<AgendamentoStatus, string> = {
  pendente: 'Pendente',
  confirmado: 'Confirmado',
  concluido: 'Concluído',
  cancelado: 'Cancelado',
}

export const statusBadgeClass: Record<AgendamentoStatus, string> = {
  pendente: 'badge--warning',
  confirmado: 'badge--info',
  concluido: 'badge--success',
  cancelado: 'badge--danger',
}
