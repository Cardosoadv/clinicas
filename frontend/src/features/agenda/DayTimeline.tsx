import { Moon, Plus } from 'lucide-react'
import { useState, type DragEvent } from 'react'
import { AppointmentCard } from './AppointmentCard'
import type { Agendamento, AgendamentoStatus } from './types'

interface DayTimelineProps {
  appointments: Agendamento[]
  onCreateAt: (hora: string) => void
  onEdit: (agendamento: Agendamento) => void
  onReschedule: (agendamento: Agendamento, newHour: string) => void
  onStatusChange: (agendamento: Agendamento, status: AgendamentoStatus) => void
}

const HOURS = Array.from({ length: 12 }, (_, i) => i + 8) // 08h às 19h
const LUNCH_HOUR = 12

function hourLabel(hour: number): string {
  return `${String(hour).padStart(2, '0')}:00`
}

export function DayTimeline({ appointments, onCreateAt, onEdit, onReschedule, onStatusChange }: DayTimelineProps) {
  const [dragOverHour, setDragOverHour] = useState<number | null>(null)

  const byHour = new Map<number, Agendamento[]>()
  for (const appt of appointments) {
    const hour = Number(appt.age_hora.slice(0, 2))
    if (!byHour.has(hour)) byHour.set(hour, [])
    byHour.get(hour)?.push(appt)
  }

  function handleDrop(event: DragEvent<HTMLDivElement>, hour: number) {
    event.preventDefault()
    setDragOverHour(null)
    const ageId = event.dataTransfer.getData('text/age-id')
    if (!ageId) return

    const agendamento = appointments.find((item) => String(item.age_id) === ageId)
    if (agendamento && Number(agendamento.age_hora.slice(0, 2)) !== hour) {
      onReschedule(agendamento, hourLabel(hour))
    }
  }

  return (
    <div className="timeline">
      {HOURS.map((hour) => {
        const isLunch = hour === LUNCH_HOUR
        const slotAppointments = byHour.get(hour) ?? []
        const isDragOver = dragOverHour === hour

        return (
          <div key={hour} className="timeline__row">
            <div className="timeline__hour">{hourLabel(hour)}</div>

            {isLunch ? (
              <div className="timeline__slot timeline__slot--lunch">
                <Moon size={14} />
                <span>Horário de Almoço</span>
              </div>
            ) : (
              <div
                className={`timeline__slot${isDragOver ? ' timeline__slot--dragover' : ''}`}
                onDragOver={(event) => {
                  event.preventDefault()
                  setDragOverHour(hour)
                }}
                onDragLeave={() => setDragOverHour((current) => (current === hour ? null : current))}
                onDrop={(event) => handleDrop(event, hour)}
              >
                {slotAppointments.length === 0 ? (
                  <button type="button" className="timeline__empty" onClick={() => onCreateAt(hourLabel(hour))}>
                    <Plus size={14} /> Horário livre — Criar Agendamento
                  </button>
                ) : (
                  <div className="timeline__cards">
                    {slotAppointments.map((agendamento) => (
                      <AppointmentCard
                        key={agendamento.age_id}
                        agendamento={agendamento}
                        onClick={() => onEdit(agendamento)}
                        onStatusChange={(status) => onStatusChange(agendamento, status)}
                      />
                    ))}
                    <button type="button" className="timeline__add-more" onClick={() => onCreateAt(hourLabel(hour))}>
                      <Plus size={12} /> adicionar
                    </button>
                  </div>
                )}
              </div>
            )}
          </div>
        )
      })}
    </div>
  )
}
