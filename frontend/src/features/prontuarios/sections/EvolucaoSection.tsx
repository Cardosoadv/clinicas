import { Activity, Pencil, Plus, Trash2, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { ApiError } from '../../../lib/api'
import { addEvolucao, deleteEvolucao, editEvolucao } from '../api'
import { useProntuario } from '../ProntuarioContext'
import { emptyEvolucaoForm, evolucaoTemplates, type Evolucao, type EvolucaoFormValues } from '../types'

function formatDateTime(value: string): string {
  try {
    return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value))
  } catch {
    return value
  }
}

export function EvolucaoSection() {
  const { pacienteId, record, reload, prefillEvolucao, setPrefillEvolucao } = useProntuario()
  const [values, setValues] = useState<EvolucaoFormValues>({ ...emptyEvolucaoForm })
  const [editingId, setEditingId] = useState<number | null>(null)
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (prefillEvolucao) {
      setValues((prev) => ({
        titulo: prev.titulo || 'Cálculo de Dosagem',
        descricao: prev.descricao ? `${prev.descricao}\n${prefillEvolucao}` : prefillEvolucao,
      }))
      setPrefillEvolucao(null)
    }
  }, [prefillEvolucao, setPrefillEvolucao])

  function applyTemplate(template: (typeof evolucaoTemplates)[number]) {
    setValues({ titulo: template.titulo, descricao: template.descricao })
  }

  function startEdit(evolucao: Evolucao) {
    setEditingId(evolucao.id)
    setValues({ titulo: evolucao.titulo, descricao: evolucao.descricao })
  }

  function cancelEdit() {
    setEditingId(null)
    setValues({ ...emptyEvolucaoForm })
  }

  async function handleSubmit() {
    if (values.titulo.trim().length < 3 || values.descricao.trim().length < 5) {
      setError('Preencha um título (mín. 3 caracteres) e uma descrição (mín. 5 caracteres).')
      return
    }
    setError(null)
    setIsSaving(true)
    try {
      if (editingId) {
        await editEvolucao(editingId, values)
      } else {
        await addEvolucao(pacienteId, values)
      }
      cancelEdit()
      reload()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Erro ao salvar evolução.')
    } finally {
      setIsSaving(false)
    }
  }

  async function handleDelete(evolucao: Evolucao) {
    if (!window.confirm('Excluir esta evolução?')) return
    await deleteEvolucao(evolucao.id)
    reload()
  }

  return (
    <div className="prontuario-section">
      <div className="side-card">
        <h3>
          <Activity size={16} />
          {editingId ? 'Editar Evolução' : 'Nova Evolução'}
        </h3>
        {error && (
          <p className="error-message" role="alert">
            {error}
          </p>
        )}

        <div className="prontuario-evolucao__templates">
          {evolucaoTemplates.map((template) => (
            <button type="button" key={template.label} className="btn btn--ghost" onClick={() => applyTemplate(template)}>
              {template.label}
            </button>
          ))}
        </div>

        <div className="form-grid">
          <label className="form-field form-field--full">
            Título *
            <input value={values.titulo} onChange={(event) => setValues((prev) => ({ ...prev, titulo: event.target.value }))} />
          </label>
          <label className="form-field form-field--full">
            Descrição *
            <textarea
              rows={6}
              value={values.descricao}
              onChange={(event) => setValues((prev) => ({ ...prev, descricao: event.target.value }))}
            />
          </label>
        </div>

        <div className="modal__footer">
          {editingId && (
            <button type="button" className="btn btn--ghost" onClick={cancelEdit}>
              <X size={16} />
              Cancelar
            </button>
          )}
          <button type="button" className="btn btn--primary" disabled={isSaving} onClick={() => void handleSubmit()}>
            <Plus size={16} />
            {isSaving ? 'Salvando...' : editingId ? 'Salvar Alterações' : 'Adicionar Evolução'}
          </button>
        </div>
      </div>

      <div className="side-card">
        <h3>Histórico de Evoluções</h3>
        {record.evolucoes.length === 0 && <p className="empty-state">Nenhuma evolução registrada.</p>}
        <div className="prontuario-evolucao__list">
          {record.evolucoes.map((evolucao) => (
            <div className="prontuario-evolucao__item" key={evolucao.id}>
              <div className="prontuario-evolucao__item-header">
                <strong>{evolucao.titulo}</strong>
                <span>{formatDateTime(evolucao.data_registro)}</span>
              </div>
              <p>{evolucao.descricao}</p>
              <div className="prontuario-evolucao__item-footer">
                <span>Por: {evolucao.veterinario_nome || 'Clínico'}</span>
                <div className="record-card__actions">
                  <button type="button" className="icon-btn" aria-label="Editar evolução" onClick={() => startEdit(evolucao)}>
                    <Pencil size={14} />
                  </button>
                  <button
                    type="button"
                    className="icon-btn icon-btn--danger"
                    aria-label="Excluir evolução"
                    onClick={() => void handleDelete(evolucao)}
                  >
                    <Trash2 size={14} />
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}
