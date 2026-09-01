import { CalendarDays, Clock, Receipt, Stethoscope } from 'lucide-react'
import { PacienteAvatar } from '../../components/PacienteAvatar'
import { formatShortDate } from './dateUtils'
import { statusBadgeClass, statusLabels } from './statusMeta'
import type { Agendamento, AgendamentoStatus, EquipeOption } from './types'

interface AgendamentoOverviewCardProps {
  agendamento: Agendamento
  veterinarioNome?: string
  onClick: () => void
  onStatusChange: (status: AgendamentoStatus) => void
  onFaturar: () => void
}

export function AgendamentoOverviewCard({
  agendamento,
  veterinarioNome,
  onClick,
  onStatusChange,
  onFaturar,
}: AgendamentoOverviewCardProps) {
  return (
    <div
      className={`agendamento-card agendamento-card--${agendamento.age_status}`}
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
      <div className="agendamento-card__header">
        <span className="agendamento-card__pet">
          <PacienteAvatar avatar={agendamento.paciente_avatar} pacienteId={agendamento.paciente_id} alt={agendamento.paciente_nome} />
          {' '}
          {agendamento.paciente_nome}
        </span>
        <select
          className={`badge ${statusBadgeClass[agendamento.age_status]} agendamento-card__status`}
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
      </div>

      {agendamento.tutor_nome && <p className="agendamento-card__tutor">Tutor: {agendamento.tutor_nome}</p>}

      <div className="agendamento-card__meta">
        <span>
          <CalendarDays size={13} />
          {formatShortDate(agendamento.age_data)}
        </span>
        <span>
          <Clock size={13} />
          {agendamento.age_hora.slice(0, 5)}
        </span>
        {veterinarioNome && (
          <span>
            <Stethoscope size={13} />
            {veterinarioNome}
          </span>
        )}
      </div>

      {agendamento.age_servico && <p className="agendamento-card__servicos">{agendamento.age_servico}</p>}

      <div className="agendamento-card__footer">
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

export function findVeterinarioNome(equipe: EquipeOption[], id: number | null): string | undefined {
  if (!id) return undefined
  return equipe.find((membro) => membro.equ_id === id)?.equ_nome
}
