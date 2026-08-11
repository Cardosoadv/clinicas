import { X } from 'lucide-react'
import { useState, type ChangeEvent, type FormEvent } from 'react'
import { ApiError } from '../../lib/api'
import type { ProdutoFormValues } from './types'
import { useEscapeKey } from '../../hooks/useEscapeKey'

interface ProdutoFormModalProps {
  title: string
  initialValues: ProdutoFormValues
  onClose: () => void
  onSubmit: (values: ProdutoFormValues) => Promise<void>
}

export function ProdutoFormModal({ title, initialValues, onClose, onSubmit }: ProdutoFormModalProps) {
  const [values, setValues] = useState<ProdutoFormValues>(initialValues)
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  function handleChange(field: keyof ProdutoFormValues) {
    return (event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
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
        setError('Erro ao salvar produto.')
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
              Nome do produto *
              <input value={values.nome} onChange={handleChange('nome')} required minLength={2} />
            </label>

            <label className="form-field">
              Unidade de medida
              <input value={values.unidade_medida} onChange={handleChange('unidade_medida')} placeholder="Unidade, Caixa, Frasco..." />
            </label>

            <label className="form-field">
              Valor de revenda (R$)
              <input type="number" min="0" step="0.01" value={values.valor_venda} onChange={handleChange('valor_venda')} />
            </label>

            <label className="form-field">
              Saldo atual
              <input type="number" min="0" step="0.01" value={values.quantidade_atual} onChange={handleChange('quantidade_atual')} />
            </label>

            <label className="form-field">
              Estoque mínimo
              <input type="number" min="0" step="0.01" value={values.estoque_minimo} onChange={handleChange('estoque_minimo')} />
            </label>

            <label className="form-field form-field--full">
              Descrição
              <textarea value={values.descricao} onChange={handleChange('descricao')} rows={2} />
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
