import { ChevronLeft, ChevronRight } from 'lucide-react'
import { buildMonthGrid, monthLabels, todayKey, weekdayLabels } from './dateUtils'

interface MiniCalendarProps {
  year: number
  month: number
  selectedDate: string
  daysWithAppointments: Set<string>
  onSelectDate: (date: string) => void
  onNavigateMonth: (direction: -1 | 1) => void
}

export function MiniCalendar({
  year,
  month,
  selectedDate,
  daysWithAppointments,
  onSelectDate,
  onNavigateMonth,
}: MiniCalendarProps) {
  const cells = buildMonthGrid(year, month)
  const today = todayKey()

  return (
    <div className="mini-calendar">
      <div className="mini-calendar__header">
        <h3>
          {monthLabels[month - 1]} {year}
        </h3>
        <div className="mini-calendar__nav">
          <button type="button" onClick={() => onNavigateMonth(-1)} aria-label="Mês anterior">
            <ChevronLeft size={16} />
          </button>
          <button type="button" onClick={() => onNavigateMonth(1)} aria-label="Próximo mês">
            <ChevronRight size={16} />
          </button>
        </div>
      </div>

      <div className="mini-calendar__weekdays">
        {weekdayLabels.map((label) => (
          <span key={label}>{label}</span>
        ))}
      </div>

      <div className="mini-calendar__grid">
        {cells.map((cell) => {
          const hasAppointments = daysWithAppointments.has(cell.dateKey)
          const isSelected = cell.dateKey === selectedDate
          const isToday = cell.dateKey === today

          return (
            <button
              type="button"
              key={cell.dateKey}
              className={[
                'mini-calendar__day',
                !cell.inCurrentMonth && 'mini-calendar__day--outside',
                isSelected && 'mini-calendar__day--selected',
                isToday && !isSelected && 'mini-calendar__day--today',
              ]
                .filter(Boolean)
                .join(' ')}
              onClick={() => onSelectDate(cell.dateKey)}
              disabled={!cell.inCurrentMonth}
            >
              {cell.day}
              {hasAppointments && <span className="mini-calendar__dot" />}
            </button>
          )
        })}
      </div>
    </div>
  )
}
