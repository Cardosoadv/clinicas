import { Check, MessageCircle } from 'lucide-react'
import { useEffect, useState } from 'react'
import { ApiError } from '../../lib/api'
import { fetchTemplates, updateTemplate } from './api'
import './configuracoes.css'
import { templateDefinitions } from './types'

export function ConfiguracoesPage() {
  const [values, setValues] = useState<Record<string, string>>({})
  const [isLoading, setIsLoading] = useState(true)
  const [savingKey, setSavingKey] = useState<string | null>(null)
  const [savedKey, setSavedKey] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    load()
  }, [])

  async function load() {
    setIsLoading(true)
    try {
      const res = await fetchTemplates()
      const list = res.data ?? []

      const initial: Record<string, string> = {}
      for (const def of templateDefinitions) {
        const found = list.find((item) => item.meta_key === def.key)
        initial[def.key] = found?.meta_value ?? def.padrao
      }
      setValues(initial)
    } catch {
      setError('Não foi possível carregar os templates.')
    } finally {
      setIsLoading(false)
    }
  }

  async function handleSave(key: string) {
    setError(null)
    setSavingKey(key)
    try {
      await updateTemplate(key, values[key] ?? '')
      setSavedKey(key)
      setTimeout(() => setSavedKey((current) => (current === key ? null : current)), 2000)
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Erro ao salvar template.')
    } finally {
      setSavingKey(null)
    }
  }

  return (
    <div className="list-page">
      <div className="page-header">
        <div>
          <h2>Configurações</h2>
          <p className="page-subtitle">Modelos de mensagens automáticas enviadas via WhatsApp</p>
        </div>
      </div>

      {error && (
        <p className="error-message" role="alert">
          {error}
        </p>
      )}

      {isLoading && <p className="empty-state">Carregando...</p>}

      {!isLoading && (
        <div className="template-grid">
          {templateDefinitions.map((def) => (
            <div className="template-card" key={def.key}>
              <div className="template-card__header">
                <MessageCircle size={18} />
                <div>
                  <h3>{def.titulo}</h3>
                  <p>{def.descricao}</p>
                </div>
              </div>

              <textarea
                rows={4}
                value={values[def.key] ?? ''}
                onChange={(event) => setValues((prev) => ({ ...prev, [def.key]: event.target.value }))}
              />

              <div className="template-card__placeholders">
                {def.placeholders.map((ph) => (
                  <button
                    key={ph}
                    type="button"
                    className="template-card__tag"
                    onClick={() =>
                      setValues((prev) => ({ ...prev, [def.key]: `${prev[def.key] ?? ''}${ph}` }))
                    }
                  >
                    {ph}
                  </button>
                ))}
              </div>

              <div className="template-card__footer">
                <button
                  type="button"
                  className="btn btn--ghost"
                  onClick={() => setValues((prev) => ({ ...prev, [def.key]: def.padrao }))}
                >
                  Restaurar padrão
                </button>
                <button
                  type="button"
                  className="btn btn--primary"
                  disabled={savingKey === def.key}
                  onClick={() => void handleSave(def.key)}
                >
                  {savingKey === def.key ? 'Salvando...' : savedKey === def.key ? (
                    <>
                      <Check size={16} /> Salvo
                    </>
                  ) : (
                    'Salvar'
                  )}
                </button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
