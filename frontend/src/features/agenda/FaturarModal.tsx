import { Plus, Trash2, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { PacienteAvatar } from '../../components/PacienteAvatar'
import { useEscapeKey } from '../../hooks/useEscapeKey'
import { ApiError } from '../../lib/api'
import { fetchProdutos } from '../estoque/api'
import type { Produto } from '../estoque/types'
import { formasPagamentoCobranca } from '../faturamento/types'
import { fetchPacotesDisponiveis } from '../pacotes/api'
import type { Pacote } from '../pacotes/types'
import { fetchFaturamento } from './api'
import { cobrancaToFaturarForm, emptyFaturarForm, totalMateriais } from './types'
import type { Agendamento, FaturarFormValues, FaturarMaterial, FaturarStatus, ServicoOption } from './types'

interface FaturarModalProps {
  agendamento: Agendamento
  servicosOptions: ServicoOption[]
  onClose: () => void
  onSubmit: (values: FaturarFormValues) => Promise<void>
}

const jaFaturado = (agendamento: Agendamento) => Number(agendamento.age_faturado) === 1

function formatCurrency(value: number): string {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

export function FaturarModal({ agendamento, servicosOptions, onClose, onSubmit }: FaturarModalProps) {
  const isEdit = jaFaturado(agendamento)
  const [values, setValues] = useState<FaturarFormValues>(() => emptyFaturarForm(agendamento, servicosOptions))
  const [isLoadingExisting, setIsLoadingExisting] = useState(isEdit)
  const [pacotesDisponiveis, setPacotesDisponiveis] = useState<Pacote[]>([])
  const [produtos, setProdutos] = useState<Produto[]>([])
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
    fetchProdutos()
      .then((res) => setProdutos(res.data ?? []))
      .catch(() => setProdutos([]))
  }, [])

  useEffect(() => {
    if (values.forma_pagamento === 'Pacote') {
      fetchPacotesDisponiveis(agendamento.paciente_id)
        .then((res) => {
          const pacotes = res.data ?? []
          setPacotesDisponiveis(pacotes)
          // O pacote sugerido (sessão pré-agendada) pode ainda não estar ativo
          setValues((prev) =>
            prev.pacote_id && !pacotes.some((pacote) => String(pacote.id) === prev.pacote_id)
              ? { ...prev, pacote_id: '' }
              : prev,
          )
        })
        .catch(() => setPacotesDisponiveis([]))
    }
  }, [values.forma_pagamento, agendamento.paciente_id])

  function addMaterial() {
    setValues((prev) => ({
      ...prev,
      materiais: [...prev.materiais, { produto_id: '', quantidade: '1', valor_unitario: '0' }],
    }))
  }

  function updateMaterial(index: number, field: keyof FaturarMaterial, value: string) {
    setValues((prev) => ({
      ...prev,
      materiais: prev.materiais.map((item, i) => {
        if (i !== index) return item
        if (field !== 'produto_id') return { ...item, [field]: value }
        // Ao escolher o produto, sugere o valor de venda cadastrado no estoque
        const produto = produtos.find((p) => String(p.id) === value)
        return { ...item, produto_id: value, valor_unitario: produto ? String(Number(produto.valor_venda)) : '0' }
      }),
    }))
  }

  function removeMaterial(index: number) {
    setValues((prev) => ({ ...prev, materiais: prev.materiais.filter((_, i) => i !== index) }))
  }

  const valorMateriais = totalMateriais(values.materiais)
  const valorTotal = Number(values.valor || 0) + valorMateriais

  async function handleSubmit() {
    setError(null)

    if (values.materiais.some((item) => !item.produto_id || Number(item.quantidade) <= 0)) {
      setError('Selecione o produto e informe a quantidade de cada material, ou remova a linha.')
      return
    }

    if (valorTotal <= 0) {
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
              Valor dos serviços (R$) *
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

            <div className="form-field form-field--full faturar-materiais">
              <span>Materiais utilizados</span>
              {values.materiais.length === 0 && (
                <p className="faturar-materiais__vazio">
                  Medicamentos, seringas, luvas e outros itens do estoque usados no atendimento.
                </p>
              )}
              {values.materiais.length > 0 && (
                <div className="faturar-materiais__row faturar-materiais__row--head" aria-hidden="true">
                  <span>Produto</span>
                  <span>Qtd.</span>
                  <span>Valor unit. (R$)</span>
                  <span>Subtotal</span>
                  <span />
                </div>
              )}
              {values.materiais.map((item, index) => {
                const produto = produtos.find((p) => String(p.id) === item.produto_id)
                return (
                  <div className="faturar-materiais__row" key={index}>
                    <select
                      aria-label="Produto"
                      value={item.produto_id}
                      onChange={(event) => updateMaterial(index, 'produto_id', event.target.value)}
                    >
                      <option value="">Selecione...</option>
                      {produtos.map((p) => (
                        <option key={p.id} value={p.id}>
                          {p.nome} — {Number(p.quantidade_atual)} {p.unidade_medida ?? ''} em estoque
                        </option>
                      ))}
                    </select>
                    <input
                      type="number"
                      min="0"
                      step="0.01"
                      aria-label={`Quantidade${produto?.unidade_medida ? ` (${produto.unidade_medida})` : ''}`}
                      value={item.quantidade}
                      onChange={(event) => updateMaterial(index, 'quantidade', event.target.value)}
                    />
                    <input
                      type="number"
                      min="0"
                      step="0.01"
                      aria-label="Valor unitário"
                      value={item.valor_unitario}
                      onChange={(event) => updateMaterial(index, 'valor_unitario', event.target.value)}
                    />
                    <span className="faturar-materiais__subtotal">
                      {formatCurrency(Number(item.quantidade || 0) * Number(item.valor_unitario || 0))}
                    </span>
                    <button
                      type="button"
                      className="icon-btn icon-btn--danger"
                      aria-label="Remover material"
                      title="Remover material"
                      onClick={() => removeMaterial(index)}
                    >
                      <Trash2 size={14} />
                    </button>
                  </div>
                )
              })}
              <button type="button" className="btn btn--ghost faturar-materiais__add" onClick={addMaterial}>
                <Plus size={14} /> Adicionar material
              </button>
            </div>

            <div className="form-field form-field--full faturar-totais">
              <span>Serviços: {formatCurrency(Number(values.valor || 0))}</span>
              <span>Materiais: {formatCurrency(valorMateriais)}</span>
              <strong>Total da cobrança: {formatCurrency(valorTotal)}</strong>
            </div>

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
