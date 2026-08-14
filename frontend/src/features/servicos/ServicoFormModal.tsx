import { X } from 'lucide-react'
import { useState, type ChangeEvent, type FormEvent } from 'react'
import { ApiError } from '../../lib/api'
import type { ServicoFormValues } from './types'
import { useEscapeKey } from '../../hooks/useEscapeKey'

interface ServicoFormModalProps {
  title: string
  initialValues: ServicoFormValues
  onClose: () => void
  onSubmit: (values: ServicoFormValues) => Promise<void>
}

export function ServicoFormModal({ title, initialValues, onClose, onSubmit }: ServicoFormModalProps) {
  const [values, setValues] = useState<ServicoFormValues>(initialValues)
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  function handleChange(field: keyof ServicoFormValues) {
    return (event: ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
      setValues((prev) => ({ ...prev, [field]: event.target.value }))
    }
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setError(null)
    setIsSubmitting(true)
    try {
      await onSubmit(values)
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.body?.errors ? Object.values(err.body.errors).join(' ') : err.message)
      } else {
        setError('Erro ao salvar serviço.')
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
            <label className="form-field">
              Ícone (emoji)
              <input value={values.ser_icone} onChange={handleChange('ser_icone')} maxLength={4} />
            </label>

            <label className="form-field form-field--full">
              Nome do serviço *
              <input value={values.ser_nome} onChange={handleChange('ser_nome')} required minLength={2} />
            </label>

            <label className="form-field">
              Valor (R$) *
              <input
                type="number"
                min="0"
                step="0.01"
                value={values.ser_valor}
                onChange={handleChange('ser_valor')}
                required
              />
            </label>

            <label className="form-field">
              Tempo estimado (min)
              <input type="number" min="0" value={values.ser_tempo_estimado} onChange={handleChange('ser_tempo_estimado')} />
            </label>

            <label className="form-field">
              Status
              <select value={values.ser_status} onChange={handleChange('ser_status')}>
                <option value="Ativo">Ativo</option>
                <option value="Inativo">Inativo</option>
              </select>
            </label>

            <label className="form-field form-field--full">
              Descrição
              <textarea value={values.ser_descricao} onChange={handleChange('ser_descricao')} rows={2} />
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
