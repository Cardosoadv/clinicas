import { ExternalLink, FileText, MapPin, X } from 'lucide-react'
import { useEffect, useState, type FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { ApiError } from '../../lib/api'
import { fetchEquipeOptions, fetchServicosOptions } from './api'
import { fetchPacienteById } from '../pacientes/api'
import { PacientePicker } from '../../components/PacientePicker'
import { ServicosPicker } from './ServicosPicker'
import type { AgendamentoFormValues, AgendamentoStatus, EquipeOption, ServicoOption } from './types'
import { useEscapeKey } from '../../hooks/useEscapeKey'

interface AgendamentoFormModalProps {
  title: string
  initialValues: AgendamentoFormValues
  initialPacienteNome?: string
  initialPacienteEndereco?: string | null
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
  initialPacienteEndereco,
  onClose,
  onSubmit,
}: AgendamentoFormModalProps) {
  const [values, setValues] = useState<AgendamentoFormValues>(initialValues)
  const [servicos, setServicos] = useState<ServicoOption[]>([])
  const [equipe, setEquipe] = useState<EquipeOption[]>([])
  const [pacienteError, setPacienteError] = useState<string | undefined>(undefined)
  const [pacienteEndereco, setPacienteEndereco] = useState<string | null>(initialPacienteEndereco ?? null)
  const [isLoadingEndereco, setIsLoadingEndereco] = useState(false)
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

  useEffect(() => {
    if (initialPacienteEndereco !== undefined && initialPacienteEndereco !== null) {
      setPacienteEndereco(initialPacienteEndereco)
    }
  }, [initialPacienteEndereco])

  useEffect(() => {
    if (!values.paciente_id) {
      setPacienteEndereco(null)
      return
    }
    if (pacienteEndereco !== null) return

    setIsLoadingEndereco(true)
    fetchPacienteById(values.paciente_id)
      .then((res) => {
        setPacienteEndereco(res.data?.paciente_endereco || '')
      })
      .catch(() => {
        setPacienteEndereco('')
      })
      .finally(() => {
        setIsLoadingEndereco(false)
      })
  }, [values.paciente_id, pacienteEndereco])

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
          <div className="modal__header-actions">
            {values.paciente_id && (
              <Link
                to={`/prontuarios/${values.paciente_id}/historico`}
                className="modal-header-prontuario-btn"
                title="Abrir histórico do paciente (clique com botão do meio para abrir em nova aba)"
                onAuxClick={(event) => event.stopPropagation()}
              >
                <FileText size={14} />
                <span>Histórico</span>
                <ExternalLink size={12} />
              </Link>
            )}
            <button type="button" onClick={onClose} aria-label="Fechar">
              <X size={18} />
            </button>
          </div>
        </div>

        <form className="modal__form" onSubmit={handleSubmit}>
          {error && (
            <p className="error-message" role="alert">
              {error}
            </p>
          )}

          <div className="form-grid">
            <div className="form-field form-field--full">
              <label htmlFor="agendamento-paciente-input">Paciente *</label>
              <PacientePicker
                id="agendamento-paciente-input"
                value={values.paciente_id}
                displayName={initialPacienteNome}
                onChange={(pacienteId, _pacienteNome, paciente) => {
                  setValues((prev) => ({ ...prev, paciente_id: pacienteId }))
                  if (!pacienteId) {
                    setPacienteEndereco(null)
                  } else if (paciente) {
                    setPacienteEndereco(paciente.paciente_endereco || '')
                  } else {
                    setIsLoadingEndereco(true)
                    fetchPacienteById(pacienteId)
                      .then((res) => setPacienteEndereco(res.data?.paciente_endereco || ''))
                      .catch(() => setPacienteEndereco(''))
                      .finally(() => setIsLoadingEndereco(false))
                  }
                }}
                error={pacienteError}
              />
              {values.paciente_id && (
                <div className="agendamento-paciente-meta">
                  <div className="paciente-endereco-label">
                    <MapPin size={13} className="paciente-endereco-label__icon" />
                    <span className="paciente-endereco-label__text">
                      <strong>Endereço:</strong>{' '}
                      {isLoadingEndereco
                        ? 'Carregando endereço...'
                        : pacienteEndereco
                        ? pacienteEndereco
                        : 'Não informado'}
                    </span>
                  </div>
                  <Link
                    to={`/prontuarios/${values.paciente_id}`}
                    className="paciente-prontuario-link"
                    title="Abrir prontuário do paciente (clique com botão do meio para abrir em nova aba)"
                    onAuxClick={(event) => event.stopPropagation()}
                  >
                    <FileText size={13} />
                    <span>Ver Prontuário</span>
                    <ExternalLink size={12} />
                  </Link>
                </div>
              )}
            </div>

            <div className="form-field form-field--full">
              <label htmlFor="agendamento-servicos-input">Serviços *</label>
              <ServicosPicker
                id="agendamento-servicos-input"
                selectedIds={values.age_servico}
                servicos={servicos}
                onChange={(nextIds) => {
                  setValues((prev) => ({ ...prev, age_servico: nextIds }))
                }}
              />
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
