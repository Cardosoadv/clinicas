import { AlertTriangle, ArrowLeftRight, Pencil, Plus, Trash2 } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { ApiError } from '../../lib/api'
import { createProduto, deleteProduto, fetchProdutos, updateProduto } from './api'
import { MovimentacaoModal } from './MovimentacaoModal'
import { ProdutoFormModal } from './ProdutoFormModal'
import { emptyProdutoForm, produtoToFormValues, type Produto, type ProdutoFormValues } from './types'

type ModalState = { mode: 'create' } | { mode: 'edit'; produto: Produto } | { mode: 'movimentacao' } | null

function formatCurrency(value: number): string {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

export function EstoqueListPage() {
  const [produtos, setProdutos] = useState<Produto[] | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [modal, setModal] = useState<ModalState>(null)

  function load() {
    setIsLoading(true)
    setError(null)
    fetchProdutos()
      .then((res) => setProdutos(res.data ?? []))
      .catch((err) => setError(err instanceof ApiError ? err.message : 'Erro ao carregar estoque.'))
      .finally(() => setIsLoading(false))
  }

  useEffect(() => {
    load()
  }, [])

  async function handleCreate(values: ProdutoFormValues) {
    await createProduto(values)
    setModal(null)
    load()
  }

  async function handleUpdate(id: number, values: ProdutoFormValues) {
    await updateProduto(id, values)
    setModal(null)
    load()
  }

  async function handleDelete(produto: Produto) {
    if (!window.confirm(`Excluir o produto "${produto.nome}"?`)) return
    await deleteProduto(produto.id)
    load()
  }

  const alertas = useMemo(() => produtos?.filter((produto) => produto.quantidade_atual <= produto.estoque_minimo) ?? [], [produtos])

  const modalTitle = useMemo(() => (modal?.mode === 'edit' ? 'Editar Produto' : 'Novo Produto'), [modal])
  const modalInitialValues = modal?.mode === 'edit' ? produtoToFormValues(modal.produto) : emptyProdutoForm

  return (
    <div className="list-page">
      <div className="page-header">
        <div>
          <h2>Estoque</h2>
          <p className="page-subtitle">Produtos e insumos utilizados na clínica</p>
        </div>
        <div style={{ display: 'flex', gap: 8 }}>
          <button type="button" className="btn btn--ghost" onClick={() => setModal({ mode: 'movimentacao' })}>
            <ArrowLeftRight size={16} />
            Movimentação
          </button>
          <button type="button" className="btn btn--primary" onClick={() => setModal({ mode: 'create' })}>
            <Plus size={16} />
            Novo Produto
          </button>
        </div>
      </div>

      {alertas.length > 0 && (
        <div className="alert-banner">
          <AlertTriangle size={18} />
          <span>
            {alertas.length} produto{alertas.length === 1 ? '' : 's'} com estoque no mínimo ou abaixo:{' '}
            {alertas.map((produto) => produto.nome).join(', ')}
          </span>
        </div>
      )}

      <div className="record-list">
        {isLoading && <p className="empty-state">Carregando...</p>}
        {!isLoading && error && <p className="empty-state empty-state--error">{error}</p>}
        {!isLoading && !error && produtos?.length === 0 && <p className="empty-state">Nenhum produto cadastrado.</p>}

        {!isLoading &&
          !error &&
          produtos?.map((produto) => {
            const isLow = produto.quantidade_atual <= produto.estoque_minimo
            return (
              <div key={produto.id} className={`record-card${isLow ? ' record-card--inactive' : ''}`}>
                <div className="record-card__main">
                  <div className="record-card__title-row">
                    <h3>{produto.nome}</h3>
                    <span className={`badge ${isLow ? 'badge--danger' : 'badge--success'}`}>
                      Saldo: {produto.quantidade_atual} {produto.unidade_medida || ''}
                    </span>
                  </div>
                  <div className="record-card__meta">
                    <span>Mínimo: {produto.estoque_minimo}</span>
                    <span>Valor revenda: {formatCurrency(produto.valor_venda)}</span>
                    {produto.descricao && <span>{produto.descricao}</span>}
                  </div>
                </div>

                <div className="record-card__actions">
                  <button
                    type="button"
                    className="icon-btn"
                    aria-label="Editar produto"
                    onClick={() => setModal({ mode: 'edit', produto })}
                  >
                    <Pencil size={16} />
                  </button>
                  <button
                    type="button"
                    className="icon-btn icon-btn--danger"
                    aria-label="Excluir produto"
                    onClick={() => void handleDelete(produto)}
                  >
                    <Trash2 size={16} />
                  </button>
                </div>
              </div>
            )
          })}
      </div>

      {modal && (modal.mode === 'create' || modal.mode === 'edit') && (
        <ProdutoFormModal
          title={modalTitle}
          initialValues={modalInitialValues}
          onClose={() => setModal(null)}
          onSubmit={(values) =>
            modal.mode === 'edit' ? handleUpdate(modal.produto.id, values) : handleCreate(values)
          }
        />
      )}

      {modal?.mode === 'movimentacao' && produtos && (
        <MovimentacaoModal
          produtos={produtos}
          onClose={() => setModal(null)}
          onSaved={() => {
            setModal(null)
            load()
          }}
        />
      )}
    </div>
  )
}
