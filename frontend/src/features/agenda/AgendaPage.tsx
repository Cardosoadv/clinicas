import { CalendarDays, Clock3, Hourglass, Plus } from 'lucide-react'
import { useEffect, useState } from 'react'
import { ApiError } from '../../lib/api'
import {
  createAgendamento,
  faturarAgendamento,
  fetchDayData,
  fetchMonthDays,
  fetchServicosOptions,
  fetchUpcoming,
  rescheduleAgendamento,
  updateAgendamento,
  updateAgendamentoStatus,
} from './api'
import { AgendamentoFormModal } from './AgendamentoFormModal'
import { DayTimeline } from './DayTimeline'
import { FaturarModal } from './FaturarModal'
import { formatLongDate, todayKey } from './dateUtils'
import { MiniCalendar } from './MiniCalendar'
import './agenda.css'
import {
  agendamentoToFormValues,
  emptyAgendamentoForm,
  type Agendamento,
  type AgendamentoFormValues,
  type AgendamentoStatus,
  type AgendaStats,
  type FaturarFormValues,
  type ServicoOption,
} from './types'

type ModalState =
  | { mode: 'create'; hora: string }
  | { mode: 'edit'; agendamento: Agendamento }
  | { mode: 'faturar'; agendamento: Agendamento }
  | null

export function AgendaPage() {
  const [selectedDate, setSelectedDate] = useState(todayKey())
  const [calendarYear, setCalendarYear] = useState(() => Number(todayKey().slice(0, 4)))
  const [calendarMonth, setCalendarMonth] = useState(() => Number(todayKey().slice(5, 7)))

  const [appointments, setAppointments] = useState<Agendamento[]>([])
  const [stats, setStats] = useState<AgendaStats | null>(null)
  const [daysWithAppointments, setDaysWithAppointments] = useState<Set<string>>(new Set())
  const [upcoming, setUpcoming] = useState<Agendamento[]>([])
  const [isLoadingDay, setIsLoadingDay] = useState(true)
  const [dayError, setDayError] = useState<string | null>(null)
  const [servicosOptions, setServicosOptions] = useState<ServicoOption[]>([])

  const [modal, setModal] = useState<ModalState>(null)

  function loadDay(date: string) {
    setIsLoadingDay(true)
    setDayError(null)
    fetchDayData(date)
      .then((res) => {
        setAppointments(res.data?.appointments ?? [])
        setStats(res.data?.stats ?? null)
      })
      .catch((err) => setDayError(err instanceof ApiError ? err.message : 'Erro ao carregar agenda.'))
      .finally(() => setIsLoadingDay(false))
  }

  function loadMonthDays(year: number, month: number) {
    fetchMonthDays(year, month)
      .then((res) => setDaysWithAppointments(new Set((res.data ?? []).map((item) => item.age_data))))
      .catch(() => setDaysWithAppointments(new Set()))
  }

  function loadUpcoming() {
    fetchUpcoming()
      .then((res) => setUpcoming(res.data ?? []))
      .catch(() => setUpcoming([]))
  }

  useEffect(() => {
    loadDay(selectedDate)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selectedDate])

  useEffect(() => {
    loadMonthDays(calendarYear, calendarMonth)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [calendarYear, calendarMonth])

  useEffect(() => {
    loadUpcoming()
  }, [])

  useEffect(() => {
    fetchServicosOptions()
      .then((res) => setServicosOptions(res.data ?? []))
      .catch(() => setServicosOptions([]))
  }, [])

  function refreshAll() {
    loadDay(selectedDate)
    loadMonthDays(calendarYear, calendarMonth)
    loadUpcoming()
  }

  function handleNavigateMonth(direction: -1 | 1) {
    let newMonth = calendarMonth + direction
    let newYear = calendarYear
    if (newMonth < 1) {
      newMonth = 12
      newYear -= 1
    } else if (newMonth > 12) {
      newMonth = 1
      newYear += 1
    }
    setCalendarMonth(newMonth)
    setCalendarYear(newYear)
  }

  async function handleCreate(values: AgendamentoFormValues) {
    await createAgendamento(values)
    setModal(null)
    refreshAll()
  }

  async function handleUpdate(id: number, values: AgendamentoFormValues) {
    await updateAgendamento(id, values)
    setModal(null)
    refreshAll()
  }

  async function handleReschedule(agendamento: Agendamento, newHour: string) {
    const previous = appointments
    setAppointments((prev) =>
      prev.map((item) => (item.age_id === agendamento.age_id ? { ...item, age_hora: `${newHour}:00` } : item)),
    )
    try {
      await rescheduleAgendamento(agendamento.age_id, agendamento.age_data, newHour)
    } catch {
      setAppointments(previous)
    }
  }

  async function handleFaturar(agendamento: Agendamento, values: FaturarFormValues) {
    await faturarAgendamento(agendamento.age_id, values)
    setModal(null)
    refreshAll()
  }

  async function handleStatusChange(agendamento: Agendamento, status: AgendamentoStatus) {
    const previous = appointments
    setAppointments((prev) =>
      prev.map((item) => (item.age_id === agendamento.age_id ? { ...item, age_status: status } : item)),
    )
    try {
      await updateAgendamentoStatus(agendamento.age_id, status)
      loadUpcoming()
    } catch {
      setAppointments(previous)
    }
  }

  const modalTitle = modal?.mode === 'edit' ? 'Editar Agendamento' : 'Novo Agendamento'
  const modalInitialValues =
    modal?.mode === 'edit'
      ? agendamentoToFormValues(modal.agendamento)
      : emptyAgendamentoForm(selectedDate, modal?.mode === 'create' ? modal.hora : '08:00')
  const modalInitialPacienteNome = modal?.mode === 'edit' ? modal.agendamento.paciente_nome : undefined

  return (
    <div className="agenda-page">
      <div className="page-header">
        <div>
          <h2>Agendamentos</h2>
          <p className="page-subtitle">Gerencie os horários e atendimentos da clínica</p>
        </div>
        <button
          type="button"
          className="btn btn--primary"
          onClick={() => setModal({ mode: 'create', hora: '08:00' })}
        >
          <Plus size={16} />
          Novo Agendamento
        </button>
      </div>

      <div className="agenda-layout">
        <div className="agenda-sidebar">
          <MiniCalendar
            year={calendarYear}
            month={calendarMonth}
            selectedDate={selectedDate}
            daysWithAppointments={daysWithAppointments}
            onSelectDate={setSelectedDate}
            onNavigateMonth={handleNavigateMonth}
          />

          <div className="agenda-stat-pills">
            <div className="agenda-stat-pill">
              <CalendarDays size={18} />
              <div>
                <strong>{stats?.hoje ?? '—'}</strong>
                <span>Hoje</span>
              </div>
            </div>
            <div className="agenda-stat-pill">
              <Hourglass size={18} />
              <div>
                <strong>{stats?.pendentes ?? '—'}</strong>
                <span>Pendentes</span>
              </div>
            </div>
          </div>

          <div className="side-card">
            <h3>
              <Clock3 size={16} />
              Próximas 48h
            </h3>
            {upcoming.length === 0 && <p className="empty-state">Nenhum agendamento nas próximas 48h.</p>}
            {upcoming.map((agendamento) => (
              <div className="upcoming-item" key={agendamento.age_id}>
                <div className="upcoming-item__info">
                  <strong>{agendamento.paciente_nome}</strong>
                  {agendamento.tutor_nome && <span> ({agendamento.tutor_nome})</span>}
                  {agendamento.age_servico && <p className="upcoming-item__servico">{agendamento.age_servico}</p>}
                </div>
                <span className="upcoming-item__time">{agendamento.age_hora.slice(0, 5)}</span>
              </div>
            ))}
          </div>
        </div>

        <div className="agenda-main">
          <div className="agenda-day-header">
            <div>
              <h3>{formatLongDate(selectedDate)}</h3>
              <p className="page-subtitle">
                {appointments.length} agendamento{appointments.length === 1 ? '' : 's'}
              </p>
            </div>
            <div className="agenda-day-header__actions">
              {selectedDate !== todayKey() && (
                <button type="button" className="btn btn--ghost" onClick={() => setSelectedDate(todayKey())}>
                  Hoje
                </button>
              )}
              <button
                type="button"
                className="btn btn--primary"
                onClick={() => setModal({ mode: 'create', hora: '08:00' })}
              >
                <Plus size={14} /> Novo
              </button>
            </div>
          </div>

          {isLoadingDay && <p className="empty-state">Carregando...</p>}
          {!isLoadingDay && dayError && <p className="empty-state empty-state--error">{dayError}</p>}

          {!isLoadingDay && !dayError && (
            <DayTimeline
              appointments={appointments}
              onCreateAt={(hora) => setModal({ mode: 'create', hora })}
              onEdit={(agendamento) => setModal({ mode: 'edit', agendamento })}
              onReschedule={handleReschedule}
              onStatusChange={handleStatusChange}
              onFaturar={(agendamento) => setModal({ mode: 'faturar', agendamento })}
            />
          )}
        </div>
      </div>

      {modal && (modal.mode === 'create' || modal.mode === 'edit') && (
        <AgendamentoFormModal
          title={modalTitle}
          initialValues={modalInitialValues}
          initialPacienteNome={modalInitialPacienteNome}
          onClose={() => setModal(null)}
          onSubmit={(values) =>
            modal.mode === 'edit' ? handleUpdate(modal.agendamento.age_id, values) : handleCreate(values)
          }
        />
      )}

      {modal && modal.mode === 'faturar' && (
        <FaturarModal
          agendamento={modal.agendamento}
          servicosOptions={servicosOptions}
          onClose={() => setModal(null)}
          onSubmit={(values) => handleFaturar(modal.agendamento, values)}
        />
      )}
    </div>
  )
}
