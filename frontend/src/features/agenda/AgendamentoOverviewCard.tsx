import { CalendarDays, Clock, FileText, Receipt, Stethoscope } from 'lucide-react'
import { Link } from 'react-router-dom'
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
        <Link
          to={`/prontuarios/${agendamento.paciente_id}`}
          className="agendamento-card__pet agendamento-card__pet--link"
          title={`Abrir prontuário de ${agendamento.paciente_nome || 'paciente'} (botão do meio abre em nova aba)`}
          onClick={(event) => event.stopPropagation()}
          onAuxClick={(event) => event.stopPropagation()}
        >
          <PacienteAvatar avatar={agendamento.paciente_avatar} pacienteId={agendamento.paciente_id} alt={agendamento.paciente_nome} />
          {' '}
          {agendamento.paciente_nome}
        </Link>
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
        <Link
          to={`/prontuarios/${agendamento.paciente_id}`}
          className="appointment-card__prontuario-btn"
          title="Abrir prontuário (clique com botão do meio para abrir em nova aba)"
          aria-label="Abrir prontuário"
          onClick={(event) => event.stopPropagation()}
          onAuxClick={(event) => event.stopPropagation()}
        >
          <FileText size={12} />
          Prontuário
        </Link>
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
