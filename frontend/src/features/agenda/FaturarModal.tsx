import { X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { PacienteAvatar } from '../../components/PacienteAvatar'
import { useEscapeKey } from '../../hooks/useEscapeKey'
import { ApiError } from '../../lib/api'
import { formasPagamentoCobranca } from '../faturamento/types'
import { fetchPacotesDisponiveis } from '../pacotes/api'
import type { Pacote } from '../pacotes/types'
import { fetchFaturamento } from './api'
import { cobrancaToFaturarForm, emptyFaturarForm } from './types'
import type { Agendamento, FaturarFormValues, FaturarStatus, ServicoOption } from './types'

interface FaturarModalProps {
  agendamento: Agendamento
  servicosOptions: ServicoOption[]
  onClose: () => void
  onSubmit: (values: FaturarFormValues) => Promise<void>
}

const jaFaturado = (agendamento: Agendamento) => Number(agendamento.age_faturado) === 1

export function FaturarModal({ agendamento, servicosOptions, onClose, onSubmit }: FaturarModalProps) {
  const isEdit = jaFaturado(agendamento)
  const [values, setValues] = useState<FaturarFormValues>(() => emptyFaturarForm(agendamento, servicosOptions))
  const [isLoadingExisting, setIsLoadingExisting] = useState(isEdit)
  const [pacotesDisponiveis, setPacotesDisponiveis] = useState<Pacote[]>([])
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  useEffect(() => {
    if (!isEdit) return
    fetchFaturamento(agendamento.age_id)
      .then((res) => {
        if (res.data) setValues(cobrancaToFaturarForm(res.data))
      })
      .catch(() => undefined)
      .finally(() => setIsLoadingExisting(false))
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [agendamento.age_id])

  useEffect(() => {
    if (values.forma_pagamento === 'Pacote') {
      fetchPacotesDisponiveis(agendamento.paciente_id)
        .then((res) => setPacotesDisponiveis(res.data ?? []))
        .catch(() => setPacotesDisponiveis([]))
    }
  }, [values.forma_pagamento, agendamento.paciente_id])

  async function handleSubmit() {
    setError(null)

    if (!values.valor || Number(values.valor) <= 0) {
      setError('Informe um valor válido.')
      return
    }

    if (values.forma_pagamento === 'Pacote' && !values.pacote_id) {
      setError('Selecione o pacote a ser utilizado.')
      return
    }

    setIsSubmitting(true)
    try {
      await onSubmit(values)
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.body?.errors ? Object.values(err.body.errors).join(' ') : err.message)
      } else {
        setError('Erro ao faturar agendamento.')
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
          <h2>{isEdit ? 'Editar Faturamento' : 'Faturar Agendamento'}</h2>
          <button type="button" onClick={onClose} aria-label="Fechar">
            <X size={18} />
          </button>
        </div>

        <div className="modal__form">
          {isLoadingExisting && <p className="empty-state">Carregando faturamento...</p>}

          {error && (
            <p className="error-message" role="alert">
              {error}
            </p>
          )}

          <div className="faturar-modal__resumo">
            <PacienteAvatar
              avatar={agendamento.paciente_avatar}
              pacienteId={agendamento.paciente_id}
              alt={agendamento.paciente_nome}
            />
            <div>
              <strong>{agendamento.paciente_nome}</strong>
              {agendamento.tutor_nome && <span> ({agendamento.tutor_nome})</span>}
              <p>{agendamento.age_servico || 'Serviços Diversos'}</p>
            </div>
          </div>

          <div className="form-grid">
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

            <label className="form-field">
              Desconto (R$)
              <input
                type="number"
                min="0"
                step="0.01"
                value={values.desconto}
                onChange={(event) => setValues((prev) => ({ ...prev, desconto: event.target.value }))}
              />
            </label>

            <label className="form-field">
              Forma de pagamento
              <select
                value={values.forma_pagamento}
                onChange={(event) => setValues((prev) => ({ ...prev, forma_pagamento: event.target.value }))}
              >
                {formasPagamentoCobranca.map((forma) => (
                  <option key={forma} value={forma}>
                    {forma}
                  </option>
                ))}
              </select>
            </label>

            {values.forma_pagamento === 'Pacote' && (
              <label className="form-field">
                Pacote *
                <select
                  value={values.pacote_id}
                  onChange={(event) => setValues((prev) => ({ ...prev, pacote_id: event.target.value }))}
                >
                  <option value="">Selecione...</option>
                  {pacotesDisponiveis.map((pacote) => (
                    <option key={pacote.id} value={pacote.id}>
                      {pacote.nome}
                    </option>
                  ))}
                </select>
              </label>
            )}

            <label className="form-field">
              Parcelas
              <select
                value={values.parcelas}
                onChange={(event) => setValues((prev) => ({ ...prev, parcelas: event.target.value }))}
              >
                {['1', '2', '3', '4', '6'].map((n) => (
                  <option key={n} value={n}>
                    {n}x
                  </option>
                ))}
              </select>
            </label>

            <label className="form-field">
              Vencimento *
              <input
                type="date"
                value={values.vencimento}
                onChange={(event) => setValues((prev) => ({ ...prev, vencimento: event.target.value }))}
                required
              />
            </label>

            <label className="form-field">
              Status
              <select
                value={values.status}
                onChange={(event) =>
                  setValues((prev) => ({ ...prev, status: event.target.value as FaturarStatus }))
                }
              >
                <option value="Pendente">Pendente</option>
                <option value="Pago">Pago</option>
                <option value="Cancelado">Cancelado</option>
              </select>
            </label>

            <label className="form-field form-field--full">
              Observações
              <textarea
                value={values.observacoes}
                onChange={(event) => setValues((prev) => ({ ...prev, observacoes: event.target.value }))}
                rows={2}
              />
            </label>
          </div>

          <div className="modal__footer">
            <button type="button" className="btn btn--ghost" onClick={onClose}>
              Cancelar
            </button>
            <button
              type="button"
              className="btn btn--primary"
              disabled={isSubmitting || isLoadingExisting}
              onClick={() => void handleSubmit()}
            >
              {isSubmitting ? 'Salvando...' : isEdit ? 'Salvar' : 'Faturar'}
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
