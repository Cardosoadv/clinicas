import { useEffect, useState } from 'react'
import { fetchEquipeOptions } from '../agenda/api'
import type { EquipeOption } from '../agenda/types'
import type { PreAgendamentoPeriodicidade, PreAgendamentoValues } from './types'

interface PreAgendamentoFieldsProps {
  values: PreAgendamentoValues
  onChange: (values: PreAgendamentoValues) => void
}

const duracoes = [15, 30, 45, 60, 90, 120]

/**
 * Campos para pré-agendar na Agenda as sessões de um pacote de serviços:
 * data inicial, periodicidade, horário, duração e veterinário.
 */
export function PreAgendamentoFields({ values, onChange }: PreAgendamentoFieldsProps) {
  const [equipe, setEquipe] = useState<EquipeOption[]>([])

  useEffect(() => {
    fetchEquipeOptions()
      .then((res) => setEquipe(res.data ?? []))
      .catch(() => setEquipe([]))
  }, [])

  function set<K extends keyof PreAgendamentoValues>(field: K, value: PreAgendamentoValues[K]) {
    onChange({ ...values, [field]: value })
  }

  return (
    <div className="form-grid">
      <label className="form-field">
        Data inicial *
        <input
          type="date"
          value={values.data_inicial}
          onChange={(event) => set('data_inicial', event.target.value)}
          required
        />
      </label>
      <label className="form-field">
        Periodicidade
        <select
          value={values.periodicidade}
          onChange={(event) => set('periodicidade', event.target.value as PreAgendamentoPeriodicidade)}
        >
          <option value="semanal">Semanal</option>
          <option value="mensal">Mensal</option>
        </select>
      </label>
      <label className="form-field">
        Horário *
        <input type="time" value={values.hora} onChange={(event) => set('hora', event.target.value)} required />
      </label>
      <label className="form-field">
        Duração de cada sessão
        <select value={values.duracao} onChange={(event) => set('duracao', event.target.value)}>
          {duracoes.map((minutos) => (
            <option key={minutos} value={minutos}>
              {minutos} min
            </option>
          ))}
        </select>
      </label>
      <label className="form-field form-field--full">
        Veterinário responsável
        <select value={values.veterinario_id} onChange={(event) => set('veterinario_id', event.target.value)}>
          <option value="">Não definido</option>
          {equipe.map((membro) => (
            <option key={membro.equ_id} value={membro.equ_id}>
              {membro.equ_nome}
              {membro.equ_is_veterinario ? ' (Veterinário)' : ''}
            </option>
          ))}
        </select>
      </label>
      <small className="form-field--full page-subtitle">
        Cada sessão de um item com serviço do catálogo vira um agendamento, repetido{' '}
        {values.periodicidade === 'mensal' ? 'todo mês' : 'toda semana'} a partir da data inicial. Com mais de um
        item, cada um fica no horário seguinte ao anterior, no mesmo dia. Itens de nome livre não são agendados.
      </small>
    </div>
  )
}
