import { X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { PacientePicker } from '../../components/PacientePicker'
import { ApiError } from '../../lib/api'
import { fetchPacotesDisponiveis } from '../pacotes/api'
import type { Pacote } from '../pacotes/types'
import { fetchServicos } from '../servicos/api'
import type { Servico } from '../servicos/types'
import type { CobrancaFormValues } from './types'
import { formasPagamentoCobranca } from './types'

interface CobrancaFormModalProps {
  title: string
  initialValues: CobrancaFormValues
  initialPacienteNome?: string
  onClose: () => void
  onSubmit: (values: CobrancaFormValues) => Promise<void>
}

export function CobrancaFormModal({
  title,
  initialValues,
  initialPacienteNome,
  onClose,
  onSubmit,
}: CobrancaFormModalProps) {
  const [values, setValues] = useState<CobrancaFormValues>(initialValues)
  const [servicos, setServicos] = useState<Servico[]>([])
  const [pacotesDisponiveis, setPacotesDisponiveis] = useState<Pacote[]>([])
  const [pacienteError, setPacienteError] = useState<string | undefined>(undefined)
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  useEffect(() => {
    fetchServicos()
      .then((res) => setServicos(res.data ?? []))
      .catch(() => setServicos([]))
  }, [])

  useEffect(() => {
    if (values.forma_pagamento === 'Pacote' && values.paciente_id) {
      fetchPacotesDisponiveis(Number(values.paciente_id))
        .then((res) => setPacotesDisponiveis(res.data ?? []))
        .catch(() => setPacotesDisponiveis([]))
    }
  }, [values.forma_pagamento, values.paciente_id])

  function handleServicoChange(servicoId: string) {
    const servico = servicos.find((item) => String(item.ser_id) === servicoId)
    setValues((prev) => ({
      ...prev,
      servico_id: servicoId,
      servico: servico ? servico.ser_nome : prev.servico,
      valor: servico ? String(servico.ser_valor) : prev.valor,
    }))
  }

  async function handleSubmit() {
    setError(null)

    if (!values.paciente_id) {
      setPacienteError('Selecione o paciente.')
      return
    }
    setPacienteError(undefined)

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
        setError('Erro ao salvar cobrança.')
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

        <div className="modal__form">
          {error && (
            <p className="error-message" role="alert">
              {error}
            </p>
          )}

          <div className="form-grid">
            <label className="form-field form-field--full">
              Paciente *
              <PacientePicker
                value={values.paciente_id}
                displayName={initialPacienteNome}
                onChange={(pacienteId) => setValues((prev) => ({ ...prev, paciente_id: pacienteId }))}
                error={pacienteError}
              />
            </label>

            <label className="form-field form-field--full">
              Serviço *
              <select value={values.servico_id} onChange={(event) => handleServicoChange(event.target.value)}>
                <option value="">Selecione um serviço cadastrado (opcional)...</option>
                {servicos.map((servico) => (
                  <option key={servico.ser_id} value={servico.ser_id}>
                    {servico.ser_icone} {servico.ser_nome}
                  </option>
                ))}
              </select>
              <input
                style={{ marginTop: 6 }}
                value={values.servico}
                onChange={(event) => setValues((prev) => ({ ...prev, servico: event.target.value }))}
                placeholder="Ou descreva o serviço livremente"
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
              Data do serviço *
              <input
                type="date"
                value={values.data_servico}
                onChange={(event) => setValues((prev) => ({ ...prev, data_servico: event.target.value }))}
                required
              />
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
              Status
              <select
                value={values.status}
                onChange={(event) =>
                  setValues((prev) => ({ ...prev, status: event.target.value as CobrancaFormValues['status'] }))
                }
              >
                <option value="Pendente">Pendente</option>
                <option value="Pago">Pago</option>
                <option value="Atrasado">Atrasado</option>
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
            <button type="button" className="btn btn--primary" disabled={isSubmitting} onClick={() => void handleSubmit()}>
              {isSubmitting ? 'Salvando...' : 'Salvar'}
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
