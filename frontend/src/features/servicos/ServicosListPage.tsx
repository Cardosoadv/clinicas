import { Clock, DollarSign, Package, Pencil, Plus, Trash2 } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { ApiError } from '../../lib/api'
import { createServico, deleteServico, fetchServicos, updateServico } from './api'
import { BomModal } from './BomModal'
import { ServicoFormModal } from './ServicoFormModal'
import './servicos.css'
import { emptyServicoForm, servicoToFormValues, type Servico, type ServicoFormValues } from './types'

type ModalState = { mode: 'create' } | { mode: 'edit'; servico: Servico } | { mode: 'bom'; servico: Servico } | null

function formatCurrency(value: number): string {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

export function ServicosListPage() {
  const [servicos, setServicos] = useState<Servico[] | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [modal, setModal] = useState<ModalState>(null)

  function load() {
    setIsLoading(true)
    setError(null)
    fetchServicos()
      .then((res) => setServicos(res.data ?? []))
      .catch((err) => setError(err instanceof ApiError ? err.message : 'Erro ao carregar serviços.'))
      .finally(() => setIsLoading(false))
  }

  useEffect(() => {
    load()
  }, [])

  async function handleCreate(values: ServicoFormValues) {
    await createServico(values)
    setModal(null)
    load()
  }

  async function handleUpdate(id: number, values: ServicoFormValues) {
    await updateServico(id, values)
    setModal(null)
    load()
  }

  async function handleDelete(servico: Servico) {
    if (!window.confirm(`Excluir o serviço "${servico.ser_nome}"?`)) return
    await deleteServico(servico.ser_id)
    load()
  }

  const modalTitle = useMemo(() => (modal?.mode === 'edit' ? 'Editar Serviço' : 'Novo Serviço'), [modal])
  const modalInitialValues = modal?.mode === 'edit' ? servicoToFormValues(modal.servico) : emptyServicoForm

  return (
    <div className="list-page">
      <div className="page-header">
        <div>
          <h2>Serviços</h2>
          <p className="page-subtitle">Procedimentos e serviços oferecidos pela clínica</p>
        </div>
        <button type="button" className="btn btn--primary" onClick={() => setModal({ mode: 'create' })}>
          <Plus size={16} />
          Novo Serviço
        </button>
      </div>

      <div className="record-list">
        {isLoading && <p className="empty-state">Carregando...</p>}
        {!isLoading && error && <p className="empty-state empty-state--error">{error}</p>}
        {!isLoading && !error && servicos?.length === 0 && <p className="empty-state">Nenhum serviço cadastrado.</p>}

        {!isLoading &&
          !error &&
          servicos?.map((servico) => (
            <div
              key={servico.ser_id}
              className={`record-card${servico.ser_status !== 'Ativo' ? ' record-card--inactive' : ''}`}
            >
              <div className="record-card__main">
                <div className="record-card__title-row">
                  <span className="servico-icon">{servico.ser_icone || '🐾'}</span>
                  <h3>{servico.ser_nome}</h3>
                  <span className={`badge ${servico.ser_status === 'Ativo' ? 'badge--success' : 'badge--danger'}`}>
                    {servico.ser_status}
                  </span>
                </div>
                <div className="record-card__meta">
                  <span>
                    <DollarSign size={14} /> {formatCurrency(servico.ser_valor)}
                  </span>
                  {servico.ser_tempo_estimado && (
                    <span>
                      <Clock size={14} /> {servico.ser_tempo_estimado} min
                    </span>
                  )}
                  {servico.ser_descricao && <span>{servico.ser_descricao}</span>}
                </div>
              </div>

              <div className="record-card__actions">
                <button
                  type="button"
                  className="icon-btn"
                  aria-label="Produtos vinculados"
                  title="Produtos vinculados (BOM)"
                  onClick={() => setModal({ mode: 'bom', servico })}
                >
                  <Package size={16} />
                </button>
                <button
                  type="button"
                  className="icon-btn"
                  aria-label="Editar serviço"
                  onClick={() => setModal({ mode: 'edit', servico })}
                >
                  <Pencil size={16} />
                </button>
                <button
                  type="button"
                  className="icon-btn icon-btn--danger"
                  aria-label="Excluir serviço"
                  onClick={() => void handleDelete(servico)}
                >
                  <Trash2 size={16} />
                </button>
              </div>
            </div>
          ))}
      </div>

      {modal && (modal.mode === 'create' || modal.mode === 'edit') && (
        <ServicoFormModal
          title={modalTitle}
          initialValues={modalInitialValues}
          onClose={() => setModal(null)}
          onSubmit={(values) =>
            modal.mode === 'edit' ? handleUpdate(modal.servico.ser_id, values) : handleCreate(values)
          }
        />
      )}

      {modal?.mode === 'bom' && (
        <BomModal servico={modal.servico} onClose={() => setModal(null)} onSaved={() => setModal(null)} />
      )}
    </div>
  )
}
