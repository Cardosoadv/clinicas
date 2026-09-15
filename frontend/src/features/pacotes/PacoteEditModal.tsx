import { Plus, Trash2, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { ApiError } from '../../lib/api'
import { fetchServicos } from '../servicos/api'
import type { Servico } from '../servicos/types'
import { fetchPacoteDetalhes, updatePacote } from './api'
import { useEscapeKey } from '../../hooks/useEscapeKey'
import type { ItemFormRow, PacoteDetalhes, PacoteEditFormValues, PacoteStatus } from './types'
import { pacoteToEditFormValues } from './types'

interface PacoteEditModalProps {
  pacoteId: number
  onClose: () => void
  onSaved: () => void
}

const statusOptions: PacoteStatus[] = ['Pendente', 'Ativo', 'Esgotado', 'Cancelado']

export function PacoteEditModal({ pacoteId, onClose, onSaved }: PacoteEditModalProps) {
  const [pacote, setPacote] = useState<PacoteDetalhes | null>(null)
  const [values, setValues] = useState<PacoteEditFormValues | null>(null)
  const [servicos, setServicos] = useState<Servico[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  useEffect(() => {
    fetchPacoteDetalhes(pacoteId)
      .then((res) => {
        if (res.data) {
          setPacote(res.data)
          setValues(pacoteToEditFormValues(res.data))
        }
      })
      .catch((err) => setError(err instanceof ApiError ? err.message : 'Erro ao carregar pacote.'))
      .finally(() => setIsLoading(false))

    fetchServicos()
      .then((res) => setServicos(res.data ?? []))
      .catch(() => setServicos([]))
  }, [pacoteId])

  const podeEditarItens = pacote?.tipo === 'Serviços' && values?.status === 'Pendente'

  function updateItem(index: number, field: keyof ItemFormRow, value: string) {
    setValues((prev) =>
      prev ? { ...prev, itens: prev.itens.map((item, i) => (i === index ? { ...item, [field]: value } : item)) } : prev,
    )
  }

  function addItem() {
    setValues((prev) =>
      prev ? { ...prev, itens: [...prev.itens, { servico_id: '', item_nome: '', quantidade: '1', valor_unitario: '' }] } : prev,
    )
  }

  function removeItem(index: number) {
    setValues((prev) => (prev ? { ...prev, itens: prev.itens.filter((_, i) => i !== index) } : prev))
  }

  async function handleSubmit() {
    if (!pacote || !values) return

    if (!values.nome.trim()) {
      setError('Informe o nome do pacote.')
      return
    }

    setError(null)
    setIsSubmitting(true)
    try {
      await updatePacote(pacote.id, values, pacote.tipo)
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

  useEscapeKey(onClose)

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal" onClick={(event) => event.stopPropagation()} role="dialog" aria-modal="true">
        <div className="modal__header">
          <h2>Editar Pacote</h2>
          <button type="button" onClick={onClose} aria-label="Fechar">
            <X size={18} />
          </button>
        </div>

        <div className="modal__form">
          {isLoading && <p className="empty-state">Carregando...</p>}
          {error && (
            <p className="error-message" role="alert">
              {error}
            </p>
          )}

          {!isLoading && pacote && values && (
            <>
              <div className="form-grid">
                <label className="form-field form-field--full">
                  Nome do pacote *
                  <input
                    value={values.nome}
                    onChange={(event) => setValues((prev) => (prev ? { ...prev, nome: event.target.value } : prev))}
                    required
                  />
                </label>

                <label className="form-field">
                  Status
                  <select
                    value={values.status}
                    onChange={(event) =>
                      setValues((prev) => (prev ? { ...prev, status: event.target.value as PacoteStatus } : prev))
                    }
                  >
                    {statusOptions.map((status) => (
                      <option key={status} value={status}>
                        {status}
                      </option>
                    ))}
                  </select>
                </label>

                <label className="form-field">
                  Validade
                  <input
                    type="date"
                    value={values.data_validade}
                    onChange={(event) =>
                      setValues((prev) => (prev ? { ...prev, data_validade: event.target.value } : prev))
                    }
                  />
                </label>

                {pacote.tipo === 'Crédito' && (
                  <label className="form-field">
                    Saldo disponível (R$)
                    <input
                      type="number"
                      min="0"
                      step="0.01"
                      value={values.saldo_valor}
                      onChange={(event) =>
                        setValues((prev) => (prev ? { ...prev, saldo_valor: event.target.value } : prev))
                      }
                    />
                  </label>
                )}

                <label className="form-field form-field--full">
                  Observações
                  <textarea
                    value={values.observacoes}
                    onChange={(event) =>
                      setValues((prev) => (prev ? { ...prev, observacoes: event.target.value } : prev))
                    }
                    rows={2}
                  />
                </label>
              </div>

              {pacote.tipo === 'Serviços' && (
                <div>
                  <p className="page-subtitle">🦴 Itens do pacote</p>
                  {!podeEditarItens && (
                    <p className="empty-state">
                      Os itens só podem ser alterados enquanto o pacote está "Pendente". Este pacote já possui uso
                      registrado.
                    </p>
                  )}
                  {podeEditarItens && (
                    <>
                      <div className="bom-rows">
                        {values.itens.map((item, index) => (
                          <div className="pacote-item-row" key={index}>
                            <select
                              value={item.servico_id}
                              onChange={(event) => updateItem(index, 'servico_id', event.target.value)}
                            >
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
                            <button
                              type="button"
                              className="icon-btn icon-btn--danger"
                              aria-label="Remover item"
                              title="Remover item"
                              onClick={() => removeItem(index)}
                            >
                              <Trash2 size={14} />
                            </button>
                          </div>
                        ))}
                      </div>
                      <button type="button" className="btn btn--ghost" onClick={addItem}>
                        <Plus size={14} /> Adicionar item
                      </button>
                    </>
                  )}
                </div>
              )}

              <div className="modal__footer">
                <button type="button" className="btn btn--ghost" onClick={onClose}>
                  Cancelar
                </button>
                <button type="button" className="btn btn--primary" disabled={isSubmitting} onClick={() => void handleSubmit()}>
                  {isSubmitting ? 'Salvando...' : 'Salvar alterações'}
                </button>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  )
}
