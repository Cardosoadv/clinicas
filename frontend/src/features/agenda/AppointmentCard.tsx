import { Clock, FileText, Receipt } from 'lucide-react'
import { Link } from 'react-router-dom'
import type { DragEvent } from 'react'
import type { Agendamento, AgendamentoStatus } from './types'
import { statusBadgeClass, statusLabels } from './statusMeta'
import { PacienteAvatar } from '../../components/PacienteAvatar'
interface AppointmentCardProps {
  agendamento: Agendamento
  onClick: () => void
  onStatusChange: (status: AgendamentoStatus) => void
  onFaturar: () => void
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
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault()
          onClick()
        }
      }}
    >
      <div className="appointment-card__main">
        <Link
          to={`/prontuarios/${agendamento.paciente_id}`}
          className="appointment-card__pet appointment-card__pet--link"
          title={`Abrir prontuário de ${agendamento.paciente_nome || 'paciente'} (botão do meio abre em nova aba)`}
          onClick={(event) => event.stopPropagation()}
          onAuxClick={(event) => event.stopPropagation()}
          draggable={false}
        >
          <PacienteAvatar avatar={agendamento.paciente_avatar} pacienteId={agendamento.paciente_id} alt={agendamento.paciente_nome} />
          {' '}
          {agendamento.paciente_nome}
        </Link>
        {agendamento.tutor_nome && <span className="appointment-card__tutor">({agendamento.tutor_nome})</span>}
        {agendamento.age_servico && <span className="appointment-card__servicos">{agendamento.age_servico}</span>}
      </div>

      <div className="appointment-card__side">
        <span className="appointment-card__time">
          <Clock size={12} />
          {agendamento.age_hora.slice(0, 5)}
        </span>
        <Link
          to={`/prontuarios/${agendamento.paciente_id}`}
          className="appointment-card__prontuario-btn"
          title="Abrir prontuário (clique com botão do meio para abrir em nova aba)"
          aria-label="Abrir prontuário"
          onClick={(event) => event.stopPropagation()}
          onAuxClick={(event) => event.stopPropagation()}
          draggable={false}
        >
          <FileText size={12} />
          Prontuário
        </Link>
        <select
          className={`badge ${statusBadgeClass[agendamento.age_status]} appointment-card__status`}
          aria-label="Status do agendamento"
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
          aria-label={Number(agendamento.age_faturado) === 1 ? 'Editar faturamento' : 'Faturar atendimento'}
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
