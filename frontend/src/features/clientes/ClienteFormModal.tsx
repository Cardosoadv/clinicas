import { X } from 'lucide-react'
import { useState, type ChangeEvent, type FormEvent } from 'react'
import { ApiError } from '../../lib/api'
import { formatCNPJ, formatCPF, isValidCNPJ, isValidCPF } from '../../lib/document'
import type { ClienteFormValues } from './types'
import { useEscapeKey } from '../../hooks/useEscapeKey'

interface ClienteFormModalProps {
  title: string
  initialValues: ClienteFormValues
  onClose: () => void
  onSubmit: (values: ClienteFormValues) => Promise<void>
}

export function ClienteFormModal({ title, initialValues, onClose, onSubmit }: ClienteFormModalProps) {
  const [values, setValues] = useState<ClienteFormValues>(initialValues)
  const [error, setError] = useState<string | null>(null)
  const [fieldErrors, setFieldErrors] = useState<{ cpf?: string; cnpj?: string }>({})
  const [isSubmitting, setIsSubmitting] = useState(false)

  function handleChange(field: keyof ClienteFormValues) {
    return (event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
      setValues((prev) => ({ ...prev, [field]: event.target.value }))
    }
  }

  function handleCpfChange(event: ChangeEvent<HTMLInputElement>) {
    setValues((prev) => ({ ...prev, cpf: formatCPF(event.target.value) }))
    setFieldErrors((prev) => ({ ...prev, cpf: undefined }))
  }

  function handleCnpjChange(event: ChangeEvent<HTMLInputElement>) {
    setValues((prev) => ({ ...prev, cnpj: formatCNPJ(event.target.value) }))
    setFieldErrors((prev) => ({ ...prev, cnpj: undefined }))
  }

  function validateDocuments(): boolean {
    const errors: { cpf?: string; cnpj?: string } = {}

    if (values.cpf.trim() && !isValidCPF(values.cpf)) {
      errors.cpf = 'CPF inválido.'
    }
    if (values.cnpj.trim() && !isValidCNPJ(values.cnpj)) {
      errors.cnpj = 'CNPJ inválido.'
    }

    setFieldErrors(errors)
    return Object.keys(errors).length === 0
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setError(null)

    if (!validateDocuments()) {
      return
    }

    setIsSubmitting(true)
    try {
      await onSubmit(values)
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.body?.errors ? Object.values(err.body.errors).join(' ') : err.message)
      } else {
        setError('Erro ao salvar cliente.')
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
              Nome completo *
              <input value={values.nome} onChange={handleChange('nome')} required minLength={3} />
            </label>

            <label className="form-field form-field--full">
              Razão social
              <input value={values.razao_social} onChange={handleChange('razao_social')} />
            </label>

            <label className="form-field">
              Apelido
              <input value={values.apelido} onChange={handleChange('apelido')} />
            </label>

            <label className="form-field">
              CPF
              <input
                value={values.cpf}
                onChange={handleCpfChange}
                placeholder="000.000.000-00"
                inputMode="numeric"
                maxLength={14}
                aria-invalid={Boolean(fieldErrors.cpf)}
              />
              {fieldErrors.cpf && <span className="field-error">{fieldErrors.cpf}</span>}
            </label>

            <label className="form-field">
              CNPJ
              <input
                value={values.cnpj}
                onChange={handleCnpjChange}
                placeholder="00.000.000/0000-00"
                maxLength={18}
                aria-invalid={Boolean(fieldErrors.cnpj)}
              />
              {fieldErrors.cnpj && <span className="field-error">{fieldErrors.cnpj}</span>}
            </label>

            <label className="form-field">
              Telefone
              <input value={values.telefones} onChange={handleChange('telefones')} />
            </label>

            <label className="form-field">
              E-mail
              <input type="email" value={values.emails} onChange={handleChange('emails')} />
            </label>

            <label className="form-field">
              Cidade
              <input value={values.cidade} onChange={handleChange('cidade')} />
            </label>

            <label className="form-field">
              Estado
              <input value={values.estado} onChange={handleChange('estado')} maxLength={2} placeholder="UF" />
            </label>

            <label className="form-field form-field--full">
              Observações
              <textarea value={values.observacoes} onChange={handleChange('observacoes')} rows={3} />
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
