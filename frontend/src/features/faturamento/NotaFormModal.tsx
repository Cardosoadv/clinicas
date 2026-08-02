import { X } from 'lucide-react'
import { useState } from 'react'
import { PacientePicker } from '../../components/PacientePicker'
import { ApiError } from '../../lib/api'
import type { NotaFormValues } from './types'
import { emptyNotaForm } from './types'

interface NotaFormModalProps {
  onClose: () => void
  onSubmit: (values: NotaFormValues) => Promise<void>
}

export function NotaFormModal({ onClose, onSubmit }: NotaFormModalProps) {
  const [values, setValues] = useState<NotaFormValues>(emptyNotaForm)
  const [pacienteError, setPacienteError] = useState<string | undefined>(undefined)
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  async function handleSubmit() {
    setError(null)
    if (!values.paciente_id) {
      setPacienteError('Selecione o paciente.')
      return
    }
    setPacienteError(undefined)

    setIsSubmitting(true)
    try {
      await onSubmit(values)
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.body?.errors ? Object.values(err.body.errors).join(' ') : err.message)
      } else {
        setError('Erro ao emitir nota.')
      }
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal" onClick={(event) => event.stopPropagation()}>
        <div className="modal__header">
          <h2>Nova Nota</h2>
          <button type="button" onClick={onClose} aria-label="Fechar">
            <X size={18} />
          </button>
        </div>

        <div className="modal__form">
          {error && (
            <p className="error-message" role="alert">
              {error}
            </p>
          )}

          <div className="tabs" style={{ alignSelf: 'flex-start' }}>
            <button
              type="button"
              className={`tab${values.tipo === 'Recibo' ? ' tab--active' : ''}`}
              onClick={() => setValues((prev) => ({ ...prev, tipo: 'Recibo' }))}
            >
              Recibo
            </button>
            <button
              type="button"
              className={`tab${values.tipo === 'NFS-e' ? ' tab--active' : ''}`}
              onClick={() => setValues((prev) => ({ ...prev, tipo: 'NFS-e' }))}
            >
              NFS-e
            </button>
          </div>

          <div className="form-grid">
            <label className="form-field form-field--full">
              Paciente *
              <PacientePicker
                value={values.paciente_id}
                onChange={(pacienteId) => setValues((prev) => ({ ...prev, paciente_id: pacienteId }))}
                error={pacienteError}
              />
            </label>

            <label className="form-field">
              CPF/CNPJ do responsável
              <input
                value={values.cpf_responsavel}
                onChange={(event) => setValues((prev) => ({ ...prev, cpf_responsavel: event.target.value }))}
              />
            </label>

            <label className="form-field">
              Data de emissão *
              <input
                type="date"
                value={values.data_emissao}
                onChange={(event) => setValues((prev) => ({ ...prev, data_emissao: event.target.value }))}
                required
              />
            </label>

            <label className="form-field">
              Valor (R$) *
              <input
                type="number"
                min="0"
                step="0.01"
                value={values.valor}
                onChange={(event) => setValues((prev) => ({ ...prev, valor: event.target.value }))}
                required
              />
            </label>

            <label className="form-field form-field--full">
              Descrição *
              <textarea
                value={values.descricao}
                onChange={(event) => setValues((prev) => ({ ...prev, descricao: event.target.value }))}
                rows={2}
                required
              />
            </label>
          </div>

          <div className="modal__footer">
            <button type="button" className="btn btn--ghost" onClick={onClose}>
              Cancelar
            </button>
            <button type="button" className="btn btn--primary" disabled={isSubmitting} onClick={() => void handleSubmit()}>
              {isSubmitting ? 'Salvando...' : 'Emitir'}
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
