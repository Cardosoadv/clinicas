import { FileText, Loader2, MapPin, Phone, Search, User, X } from 'lucide-react'
import { useRef, useState, type ChangeEvent, type FormEvent } from 'react'
import { useEscapeKey } from '../../hooks/useEscapeKey'
import { ApiError } from '../../lib/api'
import { formatCNPJ, formatCPF, isValidCNPJ, isValidCPF } from '../../lib/document'
import type { ClienteFormValues } from './types'

interface ClienteFormModalProps {
  title: string
  initialValues: ClienteFormValues
  onClose: () => void
  onSubmit: (values: ClienteFormValues) => Promise<void>
}

function formatCEP(value: string): string {
  const digits = value.replace(/\D/g, '').slice(0, 8)
  if (digits.length > 5) {
    return `${digits.slice(0, 5)}-${digits.slice(5)}`
  }
  return digits
}

export function ClienteFormModal({ title, initialValues, onClose, onSubmit }: ClienteFormModalProps) {
  const [values, setValues] = useState<ClienteFormValues>(initialValues)
  const [error, setError] = useState<string | null>(null)
  const [fieldErrors, setFieldErrors] = useState<{ cpf?: string; cnpj?: string }>({})
  const [isSubmitting, setIsSubmitting] = useState(false)

  const [isFetchingCep, setIsFetchingCep] = useState(false)
  const [cepError, setCepError] = useState<string | null>(null)

  const numeroInputRef = useRef<HTMLInputElement>(null)

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

  function handleCepChange(event: ChangeEvent<HTMLInputElement>) {
    const formatted = formatCEP(event.target.value)
    setValues((prev) => ({ ...prev, cep: formatted }))
    setCepError(null)

    const raw = formatted.replace(/\D/g, '')
    if (raw.length === 8) {
      void handleSearchViaCep(raw)
    }
  }

  async function handleSearchViaCep(cepToSearch?: string) {
    const targetCep = (cepToSearch ?? values.cep).replace(/\D/g, '')
    if (targetCep.length !== 8) {
      setCepError('Digite um CEP válido com 8 dígitos.')
      return
    }

    setIsFetchingCep(true)
    setCepError(null)

    try {
      const response = await fetch(`https://viacep.com.br/ws/${targetCep}/json/`)
      if (!response.ok) {
        throw new Error('Falha na requisição')
      }
      const data = (await response.json()) as {
        erro?: boolean
        logradouro?: string
        bairro?: string
        localidade?: string
        uf?: string
        complemento?: string
      }

      if (data.erro) {
        setCepError('CEP não encontrado.')
        return
      }

      setValues((prev) => ({
        ...prev,
        rua: data.logradouro || prev.rua,
        bairro: data.bairro || prev.bairro,
        cidade: data.localidade || prev.cidade,
        estado: data.uf || prev.estado,
        complemento: data.complemento ? data.complemento : prev.complemento,
      }))

      setTimeout(() => {
        numeroInputRef.current?.focus()
      }, 100)
    } catch {
      setCepError('Erro ao consultar o CEP. Verifique a conexão.')
    } finally {
      setIsFetchingCep(false)
    }
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
      <div className="modal modal--wide" onClick={(event) => event.stopPropagation()} role="dialog" aria-modal="true">
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

          {/* Seção 1: Dados Pessoais / Identificação */}
          <div className="form-section">
            <h3 className="form-section-title">
              <User size={16} />
              Dados Pessoais / Identificação
            </h3>
            <div className="form-grid">
              <label className="form-field form-field--full">
                Nome completo *
                <input value={values.nome} onChange={handleChange('nome')} required minLength={3} placeholder="Nome do cliente" />
              </label>

              <label className="form-field">
                Razão social
                <input value={values.razao_social} onChange={handleChange('razao_social')} placeholder="Razão social (se PJ)" />
              </label>

              <label className="form-field">
                Apelido
                <input value={values.apelido} onChange={handleChange('apelido')} placeholder="Como gosta de ser chamado" />
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
                RG
                <input value={values.rg} onChange={handleChange('rg')} placeholder="Número do RG" />
              </label>

              <label className="form-field">
                Data de nascimento
                <input type="date" value={values.nascimento} onChange={handleChange('nascimento')} />
              </label>
            </div>
          </div>

          {/* Seção 2: Contato */}
          <div className="form-section">
            <h3 className="form-section-title">
              <Phone size={16} />
              Contato
            </h3>
            <div className="form-grid">
              <label className="form-field">
                Telefone / Celular
                <input value={values.telefones} onChange={handleChange('telefones')} placeholder="(00) 00000-0000" />
              </label>

              <label className="form-field">
                E-mail
                <input type="email" value={values.emails} onChange={handleChange('emails')} placeholder="exemplo@email.com" />
              </label>
            </div>
          </div>

          {/* Seção 3: Endereço (com ViaCEP) */}
          <div className="form-section">
            <h3 className="form-section-title">
              <MapPin size={16} />
              Endereço
            </h3>
            <div className="form-grid">
              <label className="form-field">
                CEP
                <div className="input-with-button">
                  <input
                    value={values.cep}
                    onChange={handleCepChange}
                    placeholder="00000-000"
                    maxLength={9}
                    inputMode="numeric"
                  />
                  <button
                    type="button"
                    className="btn-cep-search"
                    onClick={() => void handleSearchViaCep()}
                    disabled={isFetchingCep}
                    title="Buscar endereço pelo CEP"
                  >
                    {isFetchingCep ? <Loader2 size={16} className="spin" /> : <Search size={16} />}
                    ViaCEP
                  </button>
                </div>
                {cepError && <span className="field-error">{cepError}</span>}
              </label>

              <label className="form-field" style={{ gridColumn: 'span 2' }}>
                Rua / Logradouro
                <input value={values.rua} onChange={handleChange('rua')} placeholder="Nome da rua, avenida..." />
              </label>

              <label className="form-field">
                Número
                <input ref={numeroInputRef} value={values.numero} onChange={handleChange('numero')} placeholder="Ex: 123" />
              </label>

              <label className="form-field">
                Complemento
                <input value={values.complemento} onChange={handleChange('complemento')} placeholder="Apto, Bloco, Sala..." />
              </label>

              <label className="form-field">
                Bairro
                <input value={values.bairro} onChange={handleChange('bairro')} placeholder="Nome do bairro" />
              </label>

              <label className="form-field">
                Cidade
                <input value={values.cidade} onChange={handleChange('cidade')} placeholder="Cidade" />
              </label>

              <label className="form-field">
                Estado (UF)
                <input value={values.estado} onChange={handleChange('estado')} maxLength={2} placeholder="UF" />
              </label>
            </div>
          </div>

          {/* Seção 4: Observações */}
          <div className="form-section">
            <h3 className="form-section-title">
              <FileText size={16} />
              Observações
            </h3>
            <label className="form-field form-field--full">
              Observações gerais
              <textarea value={values.observacoes} onChange={handleChange('observacoes')} rows={2} placeholder="Anotações internas..." />
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

