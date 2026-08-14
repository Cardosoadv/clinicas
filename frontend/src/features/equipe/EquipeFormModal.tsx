import { X } from 'lucide-react'
import { useEffect, useState, type FormEvent } from 'react'
import { ApiError } from '../../lib/api'
import { fetchUnlinkedUsers } from './api'
import { honorificos, type EquipeFormValues, type MembroEquipe, type UnlinkedUser } from './types'
import { useEscapeKey } from '../../hooks/useEscapeKey'

interface EquipeFormModalProps {
  title: string
  initialValues: EquipeFormValues
  editingMembro?: MembroEquipe
  onClose: () => void
  onSubmit: (values: EquipeFormValues) => Promise<void>
}

export function EquipeFormModal({ title, initialValues, editingMembro, onClose, onSubmit }: EquipeFormModalProps) {
  const [values, setValues] = useState<EquipeFormValues>(initialValues)
  const [users, setUsers] = useState<UnlinkedUser[]>([])
  const [wantsNewUser, setWantsNewUser] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  useEffect(() => {
    fetchUnlinkedUsers()
      .then((res) => setUsers(res.data ?? []))
      .catch(() => setUsers([]))
  }, [])

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setError(null)
    setIsSubmitting(true)
    try {
      await onSubmit(values)
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.body?.errors ? Object.values(err.body.errors).join(' ') : err.message)
      } else {
        setError('Erro ao salvar membro da equipe.')
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
          <h2>{title}</h2>
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

          <div className="form-grid">
            <label className="form-field">
              Tratamento
              <select
                value={values.equ_pronome}
                onChange={(event) => setValues((prev) => ({ ...prev, equ_pronome: event.target.value }))}
              >
                {honorificos.map((item) => (
                  <option key={item} value={item}>
                    {item || 'Nenhum'}
                  </option>
                ))}
              </select>
            </label>

            <label className="form-field">
              Nome completo *
              <input
                value={values.equ_nome}
                onChange={(event) => setValues((prev) => ({ ...prev, equ_nome: event.target.value }))}
                required
                minLength={3}
              />
            </label>

            <label className="form-field">
              Especialidade
              <input
                value={values.equ_especialidade}
                onChange={(event) => setValues((prev) => ({ ...prev, equ_especialidade: event.target.value }))}
              />
            </label>

            <label className="form-field">
              CRMV
              <input
                value={values.equ_crmv}
                onChange={(event) => setValues((prev) => ({ ...prev, equ_crmv: event.target.value }))}
              />
            </label>

            <label className="form-field">
              <span style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                <input
                  type="checkbox"
                  checked={values.equ_is_veterinario}
                  onChange={(event) =>
                    setValues((prev) => ({ ...prev, equ_is_veterinario: event.target.checked }))
                  }
                />
                É veterinário?
              </span>
            </label>

            <label className="form-field">
              Status
              <select
                value={values.equ_status}
                onChange={(event) =>
                  setValues((prev) => ({ ...prev, equ_status: event.target.value as 'Ativo' | 'Inativo' }))
                }
              >
                <option value="Ativo">Ativo</option>
                <option value="Inativo">Inativo</option>
              </select>
            </label>

            <div className="form-field form-field--full">
              Acesso ao sistema
              {!wantsNewUser ? (
                <>
                  <select
                    value={values.user_id}
                    onChange={(event) => setValues((prev) => ({ ...prev, user_id: event.target.value }))}
                  >
                    <option value="">Sem acesso ao sistema</option>
                    {editingMembro?.user_id && (
                      <option value={editingMembro.user_id}>Usuário atual (mantido)</option>
                    )}
                    {users.map((user) => (
                      <option key={user.id} value={user.id}>
                        {user.username ?? user.email}
                      </option>
                    ))}
                  </select>
                  {!editingMembro && (
                    <button
                      type="button"
                      className="btn btn--ghost"
                      style={{ marginTop: 8 }}
                      onClick={() => setWantsNewUser(true)}
                    >
                      Criar novo usuário
                    </button>
                  )}
                </>
              ) : (
                <div className="form-grid" style={{ marginTop: 8 }}>
                  <label className="form-field">
                    E-mail
                    <input
                      type="email"
                      value={values.novoUsuarioEmail}
                      onChange={(event) =>
                        setValues((prev) => ({ ...prev, novoUsuarioEmail: event.target.value }))
                      }
                    />
                  </label>
                  <label className="form-field">
                    Senha provisória
                    <input
                      type="text"
                      value={values.novoUsuarioSenha}
                      onChange={(event) =>
                        setValues((prev) => ({ ...prev, novoUsuarioSenha: event.target.value }))
                      }
                      placeholder="Padrão: Petys@2026"
                    />
                  </label>
                  <button
                    type="button"
                    className="btn btn--ghost"
                    onClick={() => {
                      setWantsNewUser(false)
                      setValues((prev) => ({ ...prev, novoUsuarioEmail: '', novoUsuarioSenha: '' }))
                    }}
                  >
                    Cancelar
                  </button>
                </div>
              )}
            </div>
          </div>

          <div className="modal__footer">
            <button type="button" className="btn btn--ghost" onClick={onClose}>
              Cancelar
            </button>
            <button type="submit" className="btn btn--primary" disabled={isSubmitting}>
              {isSubmitting ? 'Salvando...' : 'Salvar'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
