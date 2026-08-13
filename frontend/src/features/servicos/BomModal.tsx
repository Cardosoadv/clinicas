import { Package, Plus, Trash2, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { ApiError } from '../../lib/api'
import { fetchProdutoOptions, fetchServicoComProdutos, syncServicoProdutos } from './api'
import type { BomItem, ProdutoOption, Servico } from './types'
import { useEscapeKey } from '../../hooks/useEscapeKey'

interface BomModalProps {
  servico: Servico
  onClose: () => void
  onSaved: () => void
}

export function BomModal({ servico, onClose, onSaved }: BomModalProps) {
  const [produtos, setProdutos] = useState<ProdutoOption[]>([])
  const [itens, setItens] = useState<BomItem[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  useEffect(() => {
    Promise.all([fetchProdutoOptions(), fetchServicoComProdutos(servico.ser_id)])
      .then(([produtosRes, servicoRes]) => {
        setProdutos(produtosRes.data ?? [])
        setItens(
          (servicoRes.data?.produtos ?? []).map((item) => ({
            produto_id: String(item.produto_id),
            quantidade: String(item.quantidade),
          })),
        )
      })
      .catch(() => setError('Erro ao carregar produtos vinculados.'))
      .finally(() => setIsLoading(false))
  }, [servico.ser_id])

  function addRow() {
    setItens((prev) => [...prev, { produto_id: '', quantidade: '1' }])
  }

  function removeRow(index: number) {
    setItens((prev) => prev.filter((_, i) => i !== index))
  }

  function updateRow(index: number, field: keyof BomItem, value: string) {
    setItens((prev) => prev.map((item, i) => (i === index ? { ...item, [field]: value } : item)))
  }

  async function handleSave() {
    setError(null)
    setIsSubmitting(true)
    try {
      await syncServicoProdutos(servico.ser_id, itens)
      onSaved()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Erro ao salvar produtos vinculados.')
    } finally {
      setIsSubmitting(false)
    }
  }

  useEscapeKey(onClose)

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal" onClick={(event) => event.stopPropagation()} role="dialog" aria-modal="true">
        <div className="modal__header">
          <h2>
            <Package size={18} style={{ verticalAlign: 'middle', marginRight: 8 }} />
            Produtos vinculados — {servico.ser_nome}
          </h2>
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

          {isLoading && <p className="empty-state">Carregando...</p>}

          {!isLoading && (
            <>
              <p className="page-subtitle">
                Produtos do estoque consumidos automaticamente sempre que este serviço é faturado.
              </p>

              <div className="bom-rows">
                {itens.length === 0 && <p className="empty-state">Nenhum produto vinculado ainda.</p>}
                {itens.map((item, index) => (
                  <div className="bom-row" key={index}>
                    <select
                      value={item.produto_id}
                      onChange={(event) => updateRow(index, 'produto_id', event.target.value)}
                    >
                      <option value="">Selecione um produto...</option>
                      {produtos.map((produto) => (
                        <option key={produto.id} value={produto.id}>
                          {produto.nome} {produto.unidade_medida ? `(${produto.unidade_medida})` : ''}
                        </option>
                      ))}
                    </select>
                    <input
                      type="number"
                      min="0"
                      step="0.01"
                      value={item.quantidade}
                      onChange={(event) => updateRow(index, 'quantidade', event.target.value)}
                    />
                    <button
                      type="button"
                      className="icon-btn icon-btn--danger"
                      aria-label="Remover produto"
                      title="Remover produto"
                      onClick={() => removeRow(index)}
                    >
                      <Trash2 size={14} />
                    </button>
                  </div>
                ))}
              </div>

              <button type="button" className="btn btn--ghost" onClick={addRow}>
                <Plus size={14} /> Adicionar produto
              </button>
            </>
          )}

          <div className="modal__footer">
            <button type="button" className="btn btn--ghost" onClick={onClose}>
              Cancelar
            </button>
            <button type="button" className="btn btn--primary" disabled={isSubmitting || isLoading} onClick={() => void handleSave()}>
              {isSubmitting ? 'Salvando...' : 'Salvar vínculos'}
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
