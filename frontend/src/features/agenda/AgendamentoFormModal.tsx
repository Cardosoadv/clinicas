import { X } from 'lucide-react'
import { useEffect, useState, type FormEvent } from 'react'
import { ApiError } from '../../lib/api'
import { fetchEquipeOptions, fetchServicosOptions } from './api'
import { PacientePicker } from '../../components/PacientePicker'
import type { AgendamentoFormValues, AgendamentoStatus, EquipeOption, ServicoOption } from './types'
import { useEscapeKey } from '../../hooks/useEscapeKey'

interface AgendamentoFormModalProps {
  title: string
  initialValues: AgendamentoFormValues
  initialPacienteNome?: string
  onClose: () => void
  onSubmit: (values: AgendamentoFormValues) => Promise<void>
}

const duracoes = [15, 30, 45, 60, 90, 120]

const statusOptions: { value: AgendamentoStatus; label: string }[] = [
  { value: 'pendente', label: 'Pendente' },
  { value: 'confirmado', label: 'Confirmado' },
  { value: 'concluido', label: 'Concluído' },
  { value: 'cancelado', label: 'Cancelado' },
]

export function AgendamentoFormModal({
  title,
  initialValues,
  initialPacienteNome,
  onClose,
  onSubmit,
}: AgendamentoFormModalProps) {
  const [values, setValues] = useState<AgendamentoFormValues>(initialValues)
  const [servicos, setServicos] = useState<ServicoOption[]>([])
  const [equipe, setEquipe] = useState<EquipeOption[]>([])
  const [pacienteError, setPacienteError] = useState<string | undefined>(undefined)
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  useEffect(() => {
    fetchServicosOptions()
      .then((res) => setServicos(res.data ?? []))
      .catch(() => setServicos([]))
    fetchEquipeOptions()
      .then((res) => setEquipe(res.data ?? []))
      .catch(() => setEquipe([]))
  }, [])

  function toggleServico(id: string) {
    setValues((prev) => ({
      ...prev,
      age_servico: prev.age_servico.includes(id)
        ? prev.age_servico.filter((item) => item !== id)
        : [...prev.age_servico, id],
    }))
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setError(null)

    if (!values.paciente_id) {
      setPacienteError('Selecione o paciente para o agendamento.')
      return
    }
    setPacienteError(undefined)

    if (values.age_servico.length === 0) {
      setError('Selecione ao menos um serviço.')
      return
    }

    setIsSubmitting(true)
    try {
      await onSubmit(values)
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.body?.errors ? Object.values(err.body.errors).join(' ') : err.message)
      } else {
        setError('Erro ao salvar agendamento.')
      }
    } finally {
      setIsSubmitting(false)
    }
  }

  useEscapeKey(onClose)

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal" onClick={(event) => event.stopPropagation()} role="dialog" aria-modal="true">
        <div className="modal__header">
          <h2>{title}</h2>
          <button type="button" onClick={onClose} aria-label="Fechar">
            <X size={18} />
          </button>
        </div>

        <form className="modal__form" onSubmit={handleSubmit}>
          {error && (
            <p className="error-message" role="alert">
              {error}
            </p>
          )}

          <div className="form-grid">
            <label className="form-field form-field--full">
              Paciente *
              <PacientePicker
                value={values.paciente_id}
                displayName={initialPacienteNome}
                onChange={(pacienteId) => setValues((prev) => ({ ...prev, paciente_id: pacienteId }))}
                error={pacienteError}
              />
            </label>

            <div className="form-field form-field--full">
              Serviços *
              <div className="servico-checklist">
                {servicos.length === 0 && <p className="cliente-picker__hint">Nenhum serviço cadastrado.</p>}
                {servicos.map((servico) => (
                  <label key={servico.ser_id} className="servico-checklist__item">
                    <input
                      type="checkbox"
                      checked={values.age_servico.includes(String(servico.ser_id))}
                      onChange={() => toggleServico(String(servico.ser_id))}
                    />
                    {servico.ser_icone || '🐾'} {servico.ser_nome}
                  </label>
                ))}
              </div>
            </div>

            <label className="form-field">
              Data *
              <input
                type="date"
                value={values.age_data}
                onChange={(event) => setValues((prev) => ({ ...prev, age_data: event.target.value }))}
                required
              />
            </label>

            <label className="form-field">
              Hora *
              <input
                type="time"
                value={values.age_hora}
                onChange={(event) => setValues((prev) => ({ ...prev, age_hora: event.target.value }))}
                required
              />
            </label>

            <label className="form-field">
              Duração
              <select
                value={values.age_duracao}
                onChange={(event) => setValues((prev) => ({ ...prev, age_duracao: event.target.value }))}
              >
                {duracoes.map((minutos) => (
                  <option key={minutos} value={minutos}>
                    {minutos} min
                  </option>
                ))}
              </select>
            </label>

            <label className="form-field">
              Status
              <select
                value={values.age_status}
                onChange={(event) =>
                  setValues((prev) => ({ ...prev, age_status: event.target.value as AgendamentoStatus }))
                }
              >
                {statusOptions.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </label>

            <label className="form-field form-field--full">
              Veterinário responsável
              <select
                value={values.age_veterinario}
                onChange={(event) => setValues((prev) => ({ ...prev, age_veterinario: event.target.value }))}
              >
                <option value="">Não definido</option>
                {equipe.map((membro) => (
                  <option key={membro.equ_id} value={membro.equ_id}>
                    {membro.equ_nome}
                    {membro.equ_is_veterinario ? ' (Veterinário)' : ''}
                  </option>
                ))}
              </select>
            </label>

            <label className="form-field form-field--full">
              Observações
              <textarea
                value={values.age_obs}
                onChange={(event) => setValues((prev) => ({ ...prev, age_obs: event.target.value }))}
                rows={2}
              />
            </label>
          </div>

          <div className="modal__footer">
            <button type="button" className="btn btn--ghost" onClick={onClose}>
              Cancelar
            </button>
            <button type="submit" className="btn btn--primary" disabled={isSubmitting}>
              {isSubmitting ? 'Salvando...' : 'Salvar'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
