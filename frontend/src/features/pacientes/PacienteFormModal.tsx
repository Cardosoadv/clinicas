import { X } from 'lucide-react'
import { useState, type ChangeEvent, type FormEvent } from 'react'
import { ApiError } from '../../lib/api'
import { ClientePicker } from './ClientePicker'
import type { PacienteFormValues } from './types'

interface PacienteFormModalProps {
  title: string
  initialValues: PacienteFormValues
  especies: string[]
  onClose: () => void
  onSubmit: (values: PacienteFormValues) => Promise<void>
}

export function PacienteFormModal({ title, initialValues, especies, onClose, onSubmit }: PacienteFormModalProps) {
  const [values, setValues] = useState<PacienteFormValues>(initialValues)
  const [error, setError] = useState<string | null>(null)
  const [clienteError, setClienteError] = useState<string | undefined>(undefined)
  const [isSubmitting, setIsSubmitting] = useState(false)

  function handleChange(field: keyof PacienteFormValues) {
    return (event: ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
      setValues((prev) => ({ ...prev, [field]: event.target.value }))
    }
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setError(null)

    if (!values.cliente_id) {
      setClienteError('Selecione o tutor responsável pelo paciente.')
      return
    }
    setClienteError(undefined)

    setIsSubmitting(true)
    try {
      await onSubmit(values)
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.body?.errors ? Object.values(err.body.errors).join(' ') : err.message)
      } else {
        setError('Erro ao salvar paciente.')
      }
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal" onClick={(event) => event.stopPropagation()}>
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
              Tutor (Cliente) *
              <ClientePicker
                value={values.cliente_id}
                onChange={(clienteId) => setValues((prev) => ({ ...prev, cliente_id: clienteId }))}
                error={clienteError}
              />
            </label>

            <label className="form-field form-field--full">
              Nome do paciente *
              <input value={values.paciente_nome} onChange={handleChange('paciente_nome')} required minLength={2} />
            </label>

            <label className="form-field">
              Espécie
              <input
                value={values.paciente_especie}
                onChange={handleChange('paciente_especie')}
                list="especies-sugeridas"
                placeholder="Canina, Felina..."
              />
              <datalist id="especies-sugeridas">
                {especies.map((especie) => (
                  <option key={especie} value={especie} />
                ))}
              </datalist>
            </label>

            <label className="form-field">
              Raça
              <input value={values.paciente_raca} onChange={handleChange('paciente_raca')} />
            </label>

            <label className="form-field">
              Sexo
              <select value={values.paciente_sexo} onChange={handleChange('paciente_sexo')}>
                <option value="">Não informado</option>
                <option value="Macho">Macho</option>
                <option value="Fêmea">Fêmea</option>
                <option value="Outro">Outro</option>
              </select>
            </label>

            <label className="form-field">
              Data de nascimento
              <input
                type="date"
                value={values.paciente_nascimento}
                onChange={handleChange('paciente_nascimento')}
              />
            </label>

            <label className="form-field">
              Porte
              <input value={values.paciente_porte} onChange={handleChange('paciente_porte')} placeholder="Pequeno, médio, grande..." />
            </label>

            <label className="form-field">
              Pelagem
              <select value={values.paciente_pelagem} onChange={handleChange('paciente_pelagem')}>
                <option value="">Não informado</option>
                <option value="Curto">Curto</option>
                <option value="Médio">Médio</option>
                <option value="Longo">Longo</option>
                <option value="Sem pelo">Sem pelo</option>
              </select>
            </label>

            <label className="form-field">
              Tipo sanguíneo
              <select value={values.paciente_sangue} onChange={handleChange('paciente_sangue')}>
                <option value="">Não informado</option>
                <option value="DEA 1.1+">DEA 1.1+</option>
                <option value="DEA 1.1-">DEA 1.1-</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="AB">AB</option>
              </select>
            </label>

            <label className="form-field">
              Status
              <select value={values.paciente_status} onChange={handleChange('paciente_status')}>
                <option value="Ativo">Ativo</option>
                <option value="Inativo">Inativo</option>
                <option value="Suspenso">Suspenso</option>
              </select>
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
