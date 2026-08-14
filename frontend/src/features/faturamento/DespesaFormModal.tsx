import { X } from 'lucide-react'
import { useState, type ChangeEvent } from 'react'
import { ApiError } from '../../lib/api'
import { uploadComprovante } from './api'
import { categoriasDespesa, formasPagamentoDespesa, type DespesaFormValues } from './types'
import { useEscapeKey } from '../../hooks/useEscapeKey'

interface DespesaFormModalProps {
  title: string
  initialValues: DespesaFormValues
  onClose: () => void
  onSubmit: (values: DespesaFormValues) => Promise<number | void>
}

export function DespesaFormModal({ title, initialValues, onClose, onSubmit }: DespesaFormModalProps) {
  const [values, setValues] = useState<DespesaFormValues>(initialValues)
  const [comprovante, setComprovante] = useState<File | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  function handleChange(field: keyof DespesaFormValues) {
    return (event: ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
      setValues((prev) => ({ ...prev, [field]: event.target.value }))
    }
  }

  async function handleSubmit() {
    setError(null)
    setIsSubmitting(true)
    try {
      const id = await onSubmit(values)
      if (id && comprovante) {
        await uploadComprovante(id, comprovante)
      }
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.body?.errors ? Object.values(err.body.errors).join(' ') : err.message)
      } else {
        setError('Erro ao salvar despesa.')
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

        <div className="modal__form">
          {error && (
            <p className="error-message" role="alert">
              {error}
            </p>
          )}

          <div className="form-grid">
            <label className="form-field form-field--full">
              Descrição *
              <input value={values.descricao} onChange={handleChange('descricao')} required />
            </label>

            <label className="form-field">
              Categoria
              <select value={values.categoria} onChange={handleChange('categoria')}>
                {categoriasDespesa.map((categoria) => (
                  <option key={categoria} value={categoria}>
                    {categoria}
                  </option>
                ))}
              </select>
            </label>

            <label className="form-field">
              Fornecedor
              <input value={values.fornecedor} onChange={handleChange('fornecedor')} />
            </label>

            <label className="form-field">
              Valor (R$) *
              <input type="number" min="0" step="0.01" value={values.valor} onChange={handleChange('valor')} required />
            </label>

            <label className="form-field">
              Vencimento *
              <input type="date" value={values.data_vencimento} onChange={handleChange('data_vencimento')} required />
            </label>

            <label className="form-field">
              Forma de pagamento
              <select value={values.forma_pagamento} onChange={handleChange('forma_pagamento')}>
                {formasPagamentoDespesa.map((forma) => (
                  <option key={forma} value={forma}>
                    {forma}
                  </option>
                ))}
              </select>
            </label>

            <label className="form-field">
              Status
              <select value={values.status} onChange={handleChange('status')}>
                <option value="Pendente">Pendente</option>
                <option value="Pago">Pago</option>
                <option value="Cancelado">Cancelado</option>
              </select>
            </label>

            <label className="form-field form-field--full">
              Comprovante
              <input
                type="file"
                accept="application/pdf,image/png,image/jpeg,image/webp"
                onChange={(event) => setComprovante(event.target.files?.[0] ?? null)}
              />
            </label>
          </div>

          <div className="modal__footer">
            <button type="button" className="btn btn--ghost" onClick={onClose}>
              Cancelar
            </button>
            <button type="button" className="btn btn--primary" disabled={isSubmitting} onClick={() => void handleSubmit()}>
              {isSubmitting ? 'Salvando...' : 'Salvar'}
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
