import { Plus, Trash2, X } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { PacientePicker } from '../../components/PacientePicker'
import { ApiError } from '../../lib/api'
import { fetchServicos } from '../servicos/api'
import type { Servico } from '../servicos/types'
import { createPacote } from './api'
import type { ItemFormRow, PacoteFormValues } from './types'
import { emptyPacoteForm } from './types'

interface PacoteFormModalProps {
  onClose: () => void
  onSaved: () => void
}

const formasPagamento = ['Pix', 'Dinheiro', 'Cartão de Crédito', 'Cartão de Débito', 'Boleto']

export function PacoteFormModal({ onClose, onSaved }: PacoteFormModalProps) {
  const [values, setValues] = useState<PacoteFormValues>(emptyPacoteForm)
  const [pacienteError, setPacienteError] = useState<string | undefined>(undefined)
  const [servicos, setServicos] = useState<Servico[]>([])
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  useEffect(() => {
    fetchServicos()
      .then((res) => setServicos(res.data ?? []))
      .catch(() => setServicos([]))
  }, [])

  const totalItens = useMemo(
    () =>
      values.itens.reduce(
        (sum, item) => sum + (Number(item.quantidade) || 0) * (Number(item.valor_unitario) || 0),
        0,
      ),
    [values.itens],
  )

  function updateItem(index: number, field: keyof ItemFormRow, value: string) {
    setValues((prev) => ({
      ...prev,
      itens: prev.itens.map((item, i) => (i === index ? { ...item, [field]: value } : item)),
    }))
  }

  function addItem() {
    setValues((prev) => ({
      ...prev,
      itens: [...prev.itens, { servico_id: '', item_nome: '', quantidade: '1', valor_unitario: '' }],
    }))
  }

  function removeItem(index: number) {
    setValues((prev) => ({ ...prev, itens: prev.itens.filter((_, i) => i !== index) }))
  }

  async function handleSubmit() {
    setError(null)

    if (!values.paciente_id) {
      setPacienteError('Selecione o paciente para o pacote.')
      return
    }
    setPacienteError(undefined)

    setIsSubmitting(true)
    try {
      await createPacote(values)
      onSaved()
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.body?.errors ? Object.values(err.body.errors).join(' ') : err.message)
      } else {
        setError('Erro ao salvar pacote.')
      }
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal" onClick={(event) => event.stopPropagation()}>
        <div className="modal__header">
          <h2>Novo Pacote</h2>
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
              🐾 Paciente *
              <PacientePicker
                value={values.paciente_id}
                onChange={(pacienteId) => setValues((prev) => ({ ...prev, paciente_id: pacienteId }))}
                error={pacienteError}
              />
            </label>

            <label className="form-field form-field--full">
              Nome do pacote *
              <input
                value={values.nome}
                onChange={(event) => setValues((prev) => ({ ...prev, nome: event.target.value }))}
                required
                placeholder="Ex.: Pacote Banho e Tosa Mensal"
              />
            </label>

            <div className="tabs" style={{ alignSelf: 'flex-start' }}>
              <button
                type="button"
                className={`tab${values.tipo === 'Serviços' ? ' tab--active' : ''}`}
                onClick={() => setValues((prev) => ({ ...prev, tipo: 'Serviços' }))}
              >
                Serviços
              </button>
              <button
                type="button"
                className={`tab${values.tipo === 'Crédito' ? ' tab--active' : ''}`}
                onClick={() => setValues((prev) => ({ ...prev, tipo: 'Crédito' }))}
              >
                Crédito
              </button>
            </div>

            <label className="form-field">
              Validade
              <input
                type="date"
                value={values.data_validade}
                onChange={(event) => setValues((prev) => ({ ...prev, data_validade: event.target.value }))}
              />
            </label>
          </div>

          {values.tipo === 'Serviços' ? (
            <div>
              <p className="page-subtitle">🦴 Itens do pacote</p>
              <div className="bom-rows">
                {values.itens.map((item, index) => (
                  <div className="pacote-item-row" key={index}>
                    <select value={item.servico_id} onChange={(event) => updateItem(index, 'servico_id', event.target.value)}>
                      <option value="">Serviço vinculado (opcional)...</option>
                      {servicos.map((servico) => (
                        <option key={servico.ser_id} value={servico.ser_id}>
                          {servico.ser_icone} {servico.ser_nome}
                        </option>
                      ))}
                    </select>
                    <input
                      placeholder="Ou nome livre"
                      value={item.item_nome}
                      onChange={(event) => updateItem(index, 'item_nome', event.target.value)}
                    />
                    <input
                      type="number"
                      min="1"
                      placeholder="Qtd."
                      value={item.quantidade}
                      onChange={(event) => updateItem(index, 'quantidade', event.target.value)}
                    />
                    <input
                      type="number"
                      min="0"
                      step="0.01"
                      placeholder="Valor unit."
                      value={item.valor_unitario}
                      onChange={(event) => updateItem(index, 'valor_unitario', event.target.value)}
                    />
                    <button type="button" className="icon-btn icon-btn--danger" onClick={() => removeItem(index)}>
                      <Trash2 size={14} />
                    </button>
                  </div>
                ))}
              </div>
              <button type="button" className="btn btn--ghost" onClick={addItem}>
                <Plus size={14} /> Adicionar item
              </button>
              {totalItens > 0 && (
                <p className="page-subtitle" style={{ marginTop: 8 }}>
                  Total dos itens: {totalItens.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                </p>
              )}
            </div>
          ) : (
            <label className="form-field">
              💰 Valor do crédito (R$) *
              <input
                type="number"
                min="0"
                step="0.01"
                value={values.valor_total}
                onChange={(event) => setValues((prev) => ({ ...prev, valor_total: event.target.value }))}
              />
            </label>
          )}

          <p className="page-subtitle">💳 Pagamento e cobrança</p>
          <div className="form-grid">
            {values.tipo === 'Serviços' && (
              <label className="form-field">
                Valor total cobrado (R$) *
                <input
                  type="number"
                  min="0"
                  step="0.01"
                  value={values.valor_total}
                  onChange={(event) => setValues((prev) => ({ ...prev, valor_total: event.target.value }))}
                  required
                />
              </label>
            )}
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
                {formasPagamento.map((forma) => (
                  <option key={forma} value={forma}>
                    {forma}
                  </option>
                ))}
              </select>
            </label>
            <label className="form-field">
              Vencimento
              <input
                type="date"
                value={values.vencimento}
                onChange={(event) => setValues((prev) => ({ ...prev, vencimento: event.target.value }))}
              />
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
              {isSubmitting ? 'Salvando...' : 'Criar pacote'}
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
