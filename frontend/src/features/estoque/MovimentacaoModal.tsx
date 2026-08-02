import { X } from 'lucide-react'
import { useState, type FormEvent } from 'react'
import { ApiError } from '../../lib/api'
import { registrarMovimentacao } from './api'
import { emptyMovimentacaoForm, type MovimentacaoTipo, type Produto } from './types'

interface MovimentacaoModalProps {
  produtos: Produto[]
  initialProdutoId?: number
  onClose: () => void
  onSaved: () => void
}

const formasPagamento = ['Pix', 'Dinheiro', 'Cartão de Crédito', 'Cartão de Débito', 'Boleto', 'Transferência']

export function MovimentacaoModal({ produtos, initialProdutoId, onClose, onSaved }: MovimentacaoModalProps) {
  const [tipo, setTipo] = useState<MovimentacaoTipo>('Entrada')
  const [values, setValues] = useState(() => ({
    ...emptyMovimentacaoForm(),
    produto_id: initialProdutoId ? String(initialProdutoId) : '',
  }))
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  const produtoSelecionado = produtos.find((produto) => String(produto.id) === values.produto_id)

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setError(null)
    setIsSubmitting(true)
    try {
      await registrarMovimentacao(tipo, values, produtoSelecionado?.nome ?? '')
      onSaved()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Erro ao registrar movimentação.')
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal" onClick={(event) => event.stopPropagation()}>
        <div className="modal__header">
          <h2>Movimentação de Estoque</h2>
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

          <div className="tabs" style={{ alignSelf: 'flex-start' }}>
            <button
              type="button"
              className={`tab${tipo === 'Entrada' ? ' tab--active' : ''}`}
              onClick={() => setTipo('Entrada')}
            >
              Entrada
            </button>
            <button
              type="button"
              className={`tab${tipo === 'Saída' ? ' tab--active' : ''}`}
              onClick={() => setTipo('Saída')}
            >
              Saída
            </button>
          </div>

          <div className="form-grid">
            <label className="form-field form-field--full">
              Produto *
              <select
                value={values.produto_id}
                onChange={(event) => setValues((prev) => ({ ...prev, produto_id: event.target.value }))}
                required
              >
                <option value="">Selecione...</option>
                {produtos.map((produto) => (
                  <option key={produto.id} value={produto.id}>
                    {produto.nome} (saldo atual: {produto.quantidade_atual})
                  </option>
                ))}
              </select>
            </label>

            <label className="form-field">
              Quantidade *
              <input
                type="number"
                min="0"
                step="0.01"
                value={values.quantidade}
                onChange={(event) => setValues((prev) => ({ ...prev, quantidade: event.target.value }))}
                required
              />
            </label>

            {tipo === 'Entrada' && (
              <label className="form-field">
                Valor total (R$)
                <input
                  type="number"
                  min="0"
                  step="0.01"
                  value={values.valor_total}
                  onChange={(event) => setValues((prev) => ({ ...prev, valor_total: event.target.value }))}
                />
              </label>
            )}

            <label className="form-field form-field--full">
              Observação
              <input
                value={values.observacao}
                onChange={(event) => setValues((prev) => ({ ...prev, observacao: event.target.value }))}
              />
            </label>

            {tipo === 'Entrada' && (
              <>
                <label className="form-field form-field--full">
                  <span style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    <input
                      type="checkbox"
                      checked={values.lancar_financeiro}
                      onChange={(event) =>
                        setValues((prev) => ({ ...prev, lancar_financeiro: event.target.checked }))
                      }
                    />
                    Lançar no Financeiro (Despesa)
                  </span>
                </label>

                {values.lancar_financeiro && (
                  <>
                    <label className="form-field">
                      Fornecedor
                      <input
                        value={values.fornecedor}
                        onChange={(event) => setValues((prev) => ({ ...prev, fornecedor: event.target.value }))}
                      />
                    </label>
                    <label className="form-field">
                      Forma de pagamento
                      <select
                        value={values.forma_pagamento}
                        onChange={(event) =>
                          setValues((prev) => ({ ...prev, forma_pagamento: event.target.value }))
                        }
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
                        value={values.data_vencimento}
                        onChange={(event) =>
                          setValues((prev) => ({ ...prev, data_vencimento: event.target.value }))
                        }
                      />
                    </label>
                    <label className="form-field">
                      Status do pagamento
                      <select
                        value={values.status_pagamento}
                        onChange={(event) =>
                          setValues((prev) => ({
                            ...prev,
                            status_pagamento: event.target.value as 'Pago' | 'Pendente',
                          }))
                        }
                      >
                        <option value="Pago">Pago</option>
                        <option value="Pendente">Pendente</option>
                      </select>
                    </label>
                  </>
                )}
              </>
            )}
          </div>

          <div className="modal__footer">
            <button type="button" className="btn btn--ghost" onClick={onClose}>
              Cancelar
            </button>
            <button type="submit" className="btn btn--primary" disabled={isSubmitting}>
              {isSubmitting ? 'Salvando...' : 'Registrar'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
