import { Award, Pencil, Plus, Stethoscope, Trash2 } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { ApiError } from '../../lib/api'
import { createMembro, deleteMembro, fetchEquipe, updateMembro } from './api'
import { EquipeFormModal } from './EquipeFormModal'
import { emptyEquipeForm, formatNomeComPronome, membroToFormValues, type EquipeFormValues, type MembroEquipe } from './types'

type ModalState = { mode: 'create' } | { mode: 'edit'; membro: MembroEquipe } | null

export function EquipeListPage() {
  const [equipe, setEquipe] = useState<MembroEquipe[] | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [modal, setModal] = useState<ModalState>(null)

  function load() {
    setIsLoading(true)
    setError(null)
    fetchEquipe()
      .then((res) => setEquipe(res.data ?? []))
      .catch((err) => setError(err instanceof ApiError ? err.message : 'Erro ao carregar equipe.'))
      .finally(() => setIsLoading(false))
  }

  useEffect(() => {
    load()
  }, [])

  async function handleCreate(values: EquipeFormValues) {
    await createMembro(values)
    setModal(null)
    load()
  }

  async function handleUpdate(id: number, values: EquipeFormValues) {
    await updateMembro(id, values)
    setModal(null)
    load()
  }

  async function handleDelete(membro: MembroEquipe) {
    if (!window.confirm(`Excluir "${membro.equ_nome}" da equipe?`)) return
    await deleteMembro(membro.equ_id)
    load()
  }

  const modalTitle = useMemo(() => (modal?.mode === 'edit' ? 'Editar Membro' : 'Novo Membro'), [modal])
  const modalInitialValues = modal?.mode === 'edit' ? membroToFormValues(modal.membro) : emptyEquipeForm

  return (
    <div className="list-page">
      <div className="page-header">
        <div>
          <h2>Equipe</h2>
          <p className="page-subtitle">Profissionais e colaboradores da clínica</p>
        </div>
        <button type="button" className="btn btn--primary" onClick={() => setModal({ mode: 'create' })}>
          <Plus size={16} />
          Novo Membro
        </button>
      </div>

      <div className="record-list">
        {isLoading && <p className="empty-state">Carregando...</p>}
        {!isLoading && error && <p className="empty-state empty-state--error">{error}</p>}
        {!isLoading && !error && equipe?.length === 0 && <p className="empty-state">Nenhum membro cadastrado.</p>}

        {!isLoading &&
          !error &&
          equipe?.map((membro) => (
            <div
              key={membro.equ_id}
              className={`record-card${membro.equ_status !== 'Ativo' ? ' record-card--inactive' : ''}`}
            >
              <div className="record-card__main">
                <div className="record-card__title-row">
                  <h3>{formatNomeComPronome(membro)}</h3>
                  <span className={`badge ${membro.equ_status === 'Ativo' ? 'badge--success' : 'badge--danger'}`}>
                    {membro.equ_status}
                  </span>
                  {Boolean(membro.equ_is_veterinario) && (
                    <span className="badge badge--info">
                      <Stethoscope size={12} /> Veterinário
                    </span>
                  )}
                </div>
                <div className="record-card__meta">
                  {membro.equ_especialidade && <span>Especialidade: {membro.equ_especialidade}</span>}
                  {membro.equ_crmv && (
                    <span>
                      <Award size={14} /> CRMV: {membro.equ_crmv}
                    </span>
                  )}
                </div>
              </div>

              <div className="record-card__actions">
                <button
                  type="button"
                  className="icon-btn"
                  aria-label="Editar membro"
                  onClick={() => setModal({ mode: 'edit', membro })}
                >
                  <Pencil size={16} />
                </button>
                <button
                  type="button"
                  className="icon-btn icon-btn--danger"
                  aria-label="Excluir membro"
                  onClick={() => void handleDelete(membro)}
                >
                  <Trash2 size={16} />
                </button>
              </div>
            </div>
          ))}
      </div>

      {modal && (
        <EquipeFormModal
          title={modalTitle}
          initialValues={modalInitialValues}
          editingMembro={modal.mode === 'edit' ? modal.membro : undefined}
          onClose={() => setModal(null)}
          onSubmit={(values) =>
            modal.mode === 'edit' ? handleUpdate(modal.membro.equ_id, values) : handleCreate(values)
          }
        />
      )}
    </div>
  )
}
