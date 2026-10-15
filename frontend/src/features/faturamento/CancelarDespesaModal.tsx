import { X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { ApiError } from '../../lib/api'
import { useEscapeKey } from '../../hooks/useEscapeKey'
import { cancelarDespesa, fetchGrupoDespesas } from './api'
import { escopoLabels, pluralParcelas, resumoEscopo, type Escopo } from './despesas'
import type { Despesa } from './types'

interface CancelarDespesaModalProps {
  despesa: Despesa
  onClose: () => void
  onDone: () => void
}

export function CancelarDespesaModal({ despesa, onClose, onDone }: CancelarDespesaModalProps) {
  const emGrupo = despesa.des_grupo_id !== null
  const [grupo, setGrupo] = useState<Despesa[] | null>(emGrupo ? null : [])
  const [escopo, setEscopo] = useState<Escopo>('apenas_esta')
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  useEscapeKey(onClose)

  useEffect(() => {
    if (despesa.des_grupo_id === null) return
    let cancelado = false
    fetchGrupoDespesas(despesa.des_grupo_id)
      .then((parcelas) => !cancelado && setGrupo(parcelas))
      .catch(() => !cancelado && setError('Não foi possível carregar as parcelas do grupo.'))
    return () => {
      cancelado = true
    }
  }, [despesa.des_grupo_id])

  const resumo = grupo ? resumoEscopo(grupo, despesa, escopo) : null
  const semPendentes = resumo !== null && resumo.pendentes === 0

  async function confirmar() {
    setError(null)
    setIsSubmitting(true)
    try {
      await cancelarDespesa(despesa.id, escopo)
      onDone()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Erro ao cancelar a despesa.')
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal" onClick={(event) => event.stopPropagation()} role="dialog" aria-modal="true">
        <div className="modal__header">
          <h2>Cancelar despesa</h2>
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

          <p>
            <strong>{despesa.descricao}</strong>
          </p>

          {emGrupo && !grupo && !error && <p className="empty-state">Carregando parcelas...</p>}

          {emGrupo && grupo && (
            <fieldset className="escopo-opcoes">
              <legend>Cancelar</legend>
              {(Object.keys(escopoLabels) as Escopo[]).map((opcao) => {
                const r = resumoEscopo(grupo, despesa, opcao)
                return (
                  <label key={opcao}>
                    <input type="radio" name="escopo" checked={escopo === opcao} onChange={() => setEscopo(opcao)} />
                    {escopoLabels[opcao]} — {pluralParcelas(r.pendentes)} pendente{r.pendentes === 1 ? '' : 's'}
                  </label>
                )
              })}
            </fieldset>
          )}

          {resumo && (
            <p role="status">
              {semPendentes
                ? 'Nenhuma parcela pendente neste escopo: nada será cancelado.'
                : `${pluralParcelas(resumo.pendentes)} será${resumo.pendentes === 1 ? '' : 'o'} cancelada${resumo.pendentes === 1 ? '' : 's'}.`}
              {resumo.pagas > 0 && ` ${pluralParcelas(resumo.pagas)} já paga${resumo.pagas === 1 ? '' : 's'} não será${resumo.pagas === 1 ? '' : 'o'} alterada${resumo.pagas === 1 ? '' : 's'}.`}
            </p>
          )}

          {!emGrupo && despesa.status !== 'Pendente' && <p role="status">Só despesas pendentes podem ser canceladas.</p>}

          <div className="modal__footer">
            <button type="button" className="btn btn--ghost" onClick={onClose}>
              Voltar
            </button>
            <button
              type="button"
              className="btn btn--primary"
              disabled={isSubmitting || (emGrupo ? !grupo || semPendentes : despesa.status !== 'Pendente')}
              onClick={() => void confirmar()}
            >
              {isSubmitting ? 'Cancelando...' : 'Confirmar cancelamento'}
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
