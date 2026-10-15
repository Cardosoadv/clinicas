import { X } from 'lucide-react'
import { useMemo, useState, type ChangeEvent } from 'react'
import { ApiError } from '../../lib/api'
import { uploadComprovante } from './api'
import { gerarParcelas, MAX_PARCELAS, paraCentavos, validarParcelamento, type Periodicidade } from './parcelas'
import { categoriasDespesa, formasPagamentoDespesa, type DespesaFormValues } from './types'
import { useEscapeKey } from '../../hooks/useEscapeKey'

/** Campos que, ao mudar, recalculam a prévia das parcelas. */
const camposDaPrevia = ['valor', 'parcelas_qtd', 'data_vencimento', 'periodicidade', 'parcelar'] as const

interface DespesaFormModalProps {
  title: string
  /** Exibe a opção "Parcelar" (apenas na criação). */
  allowParcelar?: boolean
  initialValues: DespesaFormValues
  onClose: () => void
  onSubmit: (values: DespesaFormValues) => Promise<number | void>
}

export function DespesaFormModal({ title, allowParcelar = false, initialValues, onClose, onSubmit }: DespesaFormModalProps) {
  const [values, setValues] = useState<DespesaFormValues>(initialValues)
  const [comprovante, setComprovante] = useState<File | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  function update(patch: Partial<DespesaFormValues>) {
    setValues((prev) => {
      const next = { ...prev, ...patch }
      if (!camposDaPrevia.some((campo) => campo in patch)) return next
      // mudou algo que define as parcelas: recalcula a prévia (descarta ajustes manuais)
      return {
        ...next,
        parcelas: next.parcelar ? gerarParcelas(next.valor, next.parcelas_qtd, next.data_vencimento, next.periodicidade) : [],
      }
    })
  }

  function handleChange(field: keyof DespesaFormValues) {
    return (event: ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
      update({ [field]: event.target.value } as Partial<DespesaFormValues>)
    }
  }

  function handleParcelaChange(index: number, campo: 'vencimento' | 'valor', valor: string) {
    setValues((prev) => ({
      ...prev,
      parcelas: prev.parcelas.map((parcela, i) => (i === index ? { ...parcela, [campo]: valor } : parcela)),
    }))
  }

  const erroParcelamento = useMemo(
    () => (values.parcelar ? validarParcelamento(values.valor, values.parcelas) : null),
    [values.parcelar, values.valor, values.parcelas],
  )
  const totalParcelas = values.parcelas.reduce((soma, p) => soma + paraCentavos(p.valor), 0) / 100

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
              {values.parcelar ? 'Valor total (R$) *' : 'Valor (R$) *'}
              <input type="number" min="0" step="0.01" value={values.valor} onChange={handleChange('valor')} required />
            </label>

            <label className="form-field">
              {values.parcelar ? 'Vencimento da 1ª parcela *' : 'Vencimento *'}
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
              {values.parcelar ? 'Status da 1ª parcela' : 'Status'}
              <select value={values.status} onChange={handleChange('status')}>
                <option value="Pendente">Pendente</option>
                <option value="Pago">Pago</option>
                <option value="Cancelado">Cancelado</option>
              </select>
            </label>

            {allowParcelar && (
              <div className="form-field form-field--full parcelamento">
                <label className="parcelamento__toggle">
                  <input
                    type="checkbox"
                    checked={values.parcelar}
                    onChange={(event) => update({ parcelar: event.target.checked })}
                  />
                  Parcelar esta despesa
                </label>

                {values.parcelar && (
                  <>
                    <div className="form-grid">
                      <label className="form-field">
                        Nº de parcelas *
                        <input
                          type="number"
                          min="2"
                          max={MAX_PARCELAS}
                          step="1"
                          value={values.parcelas_qtd}
                          onChange={handleChange('parcelas_qtd')}
                        />
                      </label>
                      <label className="form-field">
                        Periodicidade
                        <select
                          value={values.periodicidade}
                          onChange={(event) => update({ periodicidade: event.target.value as Periodicidade })}
                        >
                          <option value="mensal">Mensal</option>
                          <option value="quinzenal">Quinzenal</option>
                          <option value="semanal">Semanal</option>
                        </select>
                      </label>
                    </div>

                    {values.parcelas.length > 0 && (
                      <table className="parcelamento__tabela">
                        <thead>
                          <tr>
                            <th>Parcela</th>
                            <th>Vencimento</th>
                            <th>Valor (R$)</th>
                          </tr>
                        </thead>
                        <tbody>
                          {values.parcelas.map((parcela, index) => (
                            <tr key={index}>
                              <td>
                                {index + 1}/{values.parcelas.length}
                              </td>
                              <td>
                                <input
                                  type="date"
                                  aria-label={`Vencimento da parcela ${index + 1}`}
                                  value={parcela.vencimento}
                                  onChange={(event) => handleParcelaChange(index, 'vencimento', event.target.value)}
                                />
                              </td>
                              <td>
                                <input
                                  type="number"
                                  min="0"
                                  step="0.01"
                                  aria-label={`Valor da parcela ${index + 1}`}
                                  value={parcela.valor}
                                  onChange={(event) => handleParcelaChange(index, 'valor', event.target.value)}
                                />
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    )}

                    <p
                      className={erroParcelamento ? 'parcelamento__resumo parcelamento__resumo--erro' : 'parcelamento__resumo'}
                      role="status"
                    >
                      {erroParcelamento ??
                        `${values.parcelas.length} parcelas somando ${totalParcelas.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}. Alterar valor, quantidade, periodicidade ou 1º vencimento recalcula a prévia.`}
                    </p>
                  </>
                )}
              </div>
            )}

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
            <button type="button" className="btn btn--primary" disabled={isSubmitting || erroParcelamento !== null} onClick={() => void handleSubmit()}>
              {isSubmitting ? 'Salvando...' : 'Salvar'}
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
