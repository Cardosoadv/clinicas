import { Mail, MapPin, Pencil, Phone, Plus, Store, Trash2 } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { ApiError } from '../../lib/api'
import { createLoja, deleteLoja, fetchLojas, lojaLogoUrl, updateLoja } from './api'
import { LojaFormModal } from './LojaFormModal'
import { emptyLojaForm, lojaToFormValues, type Loja, type LojaFormValues } from './types'

type ModalState = { mode: 'create' } | { mode: 'edit'; loja: Loja } | null

export function LojasListPage() {
  const [lojas, setLojas] = useState<Loja[] | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [modal, setModal] = useState<ModalState>(null)

  function load() {
    setIsLoading(true)
    setError(null)
    fetchLojas()
      .then((res) => setLojas(res.data ?? []))
      .catch((err) => setError(err instanceof ApiError ? err.message : 'Erro ao carregar lojas.'))
      .finally(() => setIsLoading(false))
  }

  useEffect(() => {
    load()
  }, [])

  async function handleCreate(values: LojaFormValues, logoFile: File | null) {
    await createLoja(values, logoFile)
    setModal(null)
    load()
  }

  async function handleUpdate(id: number, values: LojaFormValues, logoFile: File | null) {
    await updateLoja(id, values, logoFile)
    setModal(null)
    load()
  }

  async function handleDelete(loja: Loja) {
    if (!window.confirm(`Excluir a loja "${loja.nome}"?`)) return
    await deleteLoja(loja.id)
    load()
  }

  const modalTitle = useMemo(() => (modal?.mode === 'edit' ? 'Editar Loja' : 'Nova Loja'), [modal])
  const modalInitialValues = modal?.mode === 'edit' ? lojaToFormValues(modal.loja) : emptyLojaForm

  return (
    <div className="list-page">
      <div className="page-header">
        <div>
          <h2>Lojas</h2>
          <p className="page-subtitle">Unidades e filiais da clínica</p>
        </div>
        <button type="button" className="btn btn--primary" onClick={() => setModal({ mode: 'create' })}>
          <Plus size={16} />
          Nova Loja
        </button>
      </div>

      <div className="record-list">
        {isLoading && <p className="empty-state">Carregando...</p>}
        {!isLoading && error && <p className="empty-state empty-state--error">{error}</p>}
        {!isLoading && !error && lojas?.length === 0 && <p className="empty-state">Nenhuma loja cadastrada.</p>}

        {!isLoading &&
          !error &&
          lojas?.map((loja) => (
            <div key={loja.id} className={`record-card${loja.status !== 'Ativo' ? ' record-card--inactive' : ''}`}>
              <div className="record-card__main">
                <div className="record-card__title-row">
                  {loja.logo ? (
                    <img
                      src={lojaLogoUrl(loja.id, loja.logo)}
                      alt=""
                      crossOrigin="use-credentials"
                      style={{ width: 28, height: 28, borderRadius: 8, objectFit: 'cover' }}
                    />
                  ) : (
                    <Store size={18} />
                  )}
                  <h3>{loja.nome}</h3>
                  <span className={`badge ${loja.status === 'Ativo' ? 'badge--success' : 'badge--danger'}`}>
                    {loja.status}
                  </span>
                </div>
                <div className="record-card__meta">
                  {loja.cnpj && <span>CNPJ: {loja.cnpj}</span>}
                  {loja.email && (
                    <span>
                      <Mail size={14} /> {loja.email}
                    </span>
                  )}
                  {loja.telefone && (
                    <span>
                      <Phone size={14} /> {loja.telefone}
                    </span>
                  )}
                  {(loja.cidade || loja.estado) && (
                    <span>
                      <MapPin size={14} /> {[loja.cidade, loja.estado].filter(Boolean).join(' - ')}
                    </span>
                  )}
                </div>
              </div>

              <div className="record-card__actions">
                <button
                  type="button"
                  className="icon-btn"
                  aria-label="Editar loja"
                  onClick={() => setModal({ mode: 'edit', loja })}
                >
                  <Pencil size={16} />
                </button>
                <button
                  type="button"
                  className="icon-btn icon-btn--danger"
                  aria-label="Excluir loja"
                  onClick={() => void handleDelete(loja)}
                >
                  <Trash2 size={16} />
                </button>
              </div>
            </div>
          ))}
      </div>

      {modal && (
        <LojaFormModal
          title={modalTitle}
          initialValues={modalInitialValues}
          editingLoja={modal.mode === 'edit' ? modal.loja : undefined}
          onClose={() => setModal(null)}
          onSubmit={(values, logoFile) =>
            modal.mode === 'edit' ? handleUpdate(modal.loja.id, values, logoFile) : handleCreate(values, logoFile)
          }
        />
      )}
    </div>
  )
}
