import { Pill, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { fetchPrescricao } from './api'
import type { PrescricaoDetalhada } from './types'

interface PrescricaoDetalhesModalProps {
  prescricaoId: number
  onClose: () => void
}

function formatDate(value: string): string {
  try {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(value))
  } catch {
    return value
  }
}

export function PrescricaoDetalhesModal({ prescricaoId, onClose }: PrescricaoDetalhesModalProps) {
  const [prescricao, setPrescricao] = useState<PrescricaoDetalhada | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    fetchPrescricao(prescricaoId)
      .then((res) => setPrescricao(res.data ?? null))
      .finally(() => setIsLoading(false))
  }, [prescricaoId])

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal" onClick={(event) => event.stopPropagation()}>
        <div className="modal__header">
          <h2>
            <Pill size={18} style={{ verticalAlign: 'text-bottom', marginRight: 6 }} />
            Prescrição #{prescricaoId}
          </h2>
          <button type="button" onClick={onClose} aria-label="Fechar">
            <X size={18} />
          </button>
        </div>

        <div className="modal__form">
          {isLoading && <p className="empty-state">Carregando...</p>}

          {!isLoading && prescricao && (
            <>
              <p className="page-subtitle">
                {formatDate(prescricao.data_prescricao)} · {prescricao.veterinario_nome || 'Clínico'}
              </p>

              <div className="prontuario-prescricao__itens">
                {prescricao.itens.map((item) => (
                  <div className="prontuario-prescricao__item-card" key={item.id}>
                    <strong>{item.medicamento}</strong>
                    <span>
                      {[item.dosagem, item.frequencia, item.duracao, item.via_administracao, item.particao]
                        .filter(Boolean)
                        .join(' · ')}
                    </span>
                  </div>
                ))}
              </div>

              {prescricao.observacoes && (
                <div className="cliente-detail__observacoes">
                  <span className="cliente-detail__field-label">Observações</span>
                  <p>{prescricao.observacoes}</p>
                </div>
              )}
            </>
          )}

          <div className="modal__footer">
            <button type="button" className="btn btn--ghost" onClick={onClose}>
              Fechar
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
