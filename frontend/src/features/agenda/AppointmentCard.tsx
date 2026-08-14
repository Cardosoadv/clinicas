import { Clock, Receipt } from 'lucide-react'
import type { DragEvent } from 'react'
import type { Agendamento, AgendamentoStatus } from './types'
import { PacienteAvatar } from '../../components/PacienteAvatar'
interface AppointmentCardProps {
  agendamento: Agendamento
  onClick: () => void
  onStatusChange: (status: AgendamentoStatus) => void
  onFaturar: () => void
}

const statusLabels: Record<AgendamentoStatus, string> = {
  pendente: 'Pendente',
  confirmado: 'Confirmado',
  concluido: 'Concluído',
  cancelado: 'Cancelado',
}

const statusBadgeClass: Record<AgendamentoStatus, string> = {
  pendente: 'badge--warning',
  confirmado: 'badge--info',
  concluido: 'badge--success',
  cancelado: 'badge--danger',
}

/** Card de agendamento na timeline — arrastável (HTML5 drag-and-drop) para reagendar de horário. */
export function AppointmentCard({ agendamento, onClick, onStatusChange, onFaturar }: AppointmentCardProps) {
  function handleDragStart(event: DragEvent<HTMLDivElement>) {
    event.dataTransfer.setData('text/age-id', String(agendamento.age_id))
    event.dataTransfer.effectAllowed = 'move'
  }

  return (
    <div
      className={`appointment-card appointment-card--${agendamento.age_status}`}
      draggable
      onDragStart={handleDragStart}
      onClick={onClick}
      role="button"
      tabIndex={0}
      onKeyDown={(event) => {
        if (event.key === 'Enter') onClick()
      }}
    >
      <div className="appointment-card__main">
        <span className="appointment-card__pet">
          <PacienteAvatar avatar={agendamento.paciente_avatar} pacienteId={agendamento.paciente_id} alt={agendamento.paciente_nome} />
          {' '}
          {agendamento.paciente_nome}
        </span>
        {agendamento.tutor_nome && <span className="appointment-card__tutor">({agendamento.tutor_nome})</span>}
        {agendamento.age_servico && <span className="appointment-card__servicos">{agendamento.age_servico}</span>}
      </div>

      <div className="appointment-card__side">
        <span className="appointment-card__time">
          <Clock size={12} />
          {agendamento.age_hora.slice(0, 5)}
        </span>
        <select
          className={`badge ${statusBadgeClass[agendamento.age_status]} appointment-card__status`}
          value={agendamento.age_status}
          onClick={(event) => event.stopPropagation()}
          onChange={(event) => onStatusChange(event.target.value as AgendamentoStatus)}
        >
          {(Object.keys(statusLabels) as AgendamentoStatus[]).map((status) => (
            <option key={status} value={status}>
              {statusLabels[status]}
            </option>
          ))}
        </select>
        <button
          type="button"
          className={`appointment-card__bill-btn${Number(agendamento.age_faturado) === 1 ? ' appointment-card__bill-btn--done' : ''}`}
          title={Number(agendamento.age_faturado) === 1 ? 'Editar faturamento' : 'Faturar atendimento'}
          onClick={(event) => {
            event.stopPropagation()
            onFaturar()
          }}
        >
          <Receipt size={12} />
          {Number(agendamento.age_faturado) === 1 ? 'Faturado' : 'Faturar'}
        </button>
      </div>
    </div>
  )
}
