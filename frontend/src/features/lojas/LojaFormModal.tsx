import { X } from 'lucide-react'
import { useState, type ChangeEvent, type FormEvent } from 'react'
import { ApiError } from '../../lib/api'
import { lojaLogoUrl } from './api'
import type { Loja, LojaFormValues } from './types'
import { useEscapeKey } from '../../hooks/useEscapeKey'

interface LojaFormModalProps {
  title: string
  initialValues: LojaFormValues
  editingLoja?: Loja
  onClose: () => void
  onSubmit: (values: LojaFormValues, logoFile: File | null) => Promise<void>
}

export function LojaFormModal({ title, initialValues, editingLoja, onClose, onSubmit }: LojaFormModalProps) {
  const [values, setValues] = useState<LojaFormValues>(initialValues)
  const [logoFile, setLogoFile] = useState<File | null>(null)
  const [preview, setPreview] = useState<string | null>(
    editingLoja?.logo ? lojaLogoUrl(editingLoja.id, editingLoja.logo) : null,
  )
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  function handleChange(field: keyof LojaFormValues) {
    return (event: ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
      setValues((prev) => ({ ...prev, [field]: event.target.value }))
    }
  }

  function handleFileChange(event: ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0] ?? null
    setLogoFile(file)
    setPreview(file ? URL.createObjectURL(file) : null)
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setError(null)
    setIsSubmitting(true)
    try {
      await onSubmit(values, logoFile)
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.body?.errors ? Object.values(err.body.errors).join(' ') : err.message)
      } else {
        setError('Erro ao salvar loja.')
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
              Logo
              <div className="logo-upload">
                {preview ? (
                  <img src={preview} alt="Pré-visualização da logo" crossOrigin="use-credentials" />
                ) : (
                  <span className="logo-upload__placeholder">🏪</span>
                )}
                <input type="file" accept="image/png,image/jpeg,image/webp" onChange={handleFileChange} />
              </div>
            </label>

            <label className="form-field form-field--full">
              Nome *
              <input value={values.nome} onChange={handleChange('nome')} required minLength={3} />
            </label>

            <label className="form-field">
              CNPJ
              <input value={values.cnpj} onChange={handleChange('cnpj')} placeholder="00.000.000/0000-00" />
            </label>

            <label className="form-field">
              Telefone
              <input value={values.telefone} onChange={handleChange('telefone')} />
            </label>

            <label className="form-field">
              E-mail
              <input type="email" value={values.email} onChange={handleChange('email')} />
            </label>

            <label className="form-field">
              Status
              <select value={values.status} onChange={handleChange('status')}>
                <option value="Ativo">Ativo</option>
                <option value="Inativo">Inativo</option>
              </select>
            </label>

            <label className="form-field form-field--full">
              Endereço
              <input value={values.endereco} onChange={handleChange('endereco')} />
            </label>

            <label className="form-field">
              Cidade
              <input value={values.cidade} onChange={handleChange('cidade')} />
            </label>

            <label className="form-field">
              Estado
              <input value={values.estado} onChange={handleChange('estado')} maxLength={2} placeholder="UF" />
            </label>

            <label className="form-field">
              CEP
              <input value={values.cep} onChange={handleChange('cep')} />
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
