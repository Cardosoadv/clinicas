import { Pill, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { fetchPrescricao } from './api'
import type { PrescricaoDetalhada } from './types'
import { ReportTemplate } from '../../components/ReportTemplate'

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
            <button
              type="button"
              className="btn btn--primary"
              onClick={() => window.print()}
              disabled={isLoading || !prescricao}
            >
              Imprimir
            </button>
            <button type="button" className="btn btn--ghost" onClick={onClose}>
              Fechar
            </button>
          </div>
        </div>
      </div>

      {!isLoading && prescricao && (
        <ReportTemplate className="print-only" showPrintButton={false}>
          <div style={{ textAlign: 'center', marginBottom: 30 }}>
            <h2 style={{ margin: 0 }}>Receita Simples</h2>
            <p style={{ margin: '5px 0 0', color: '#666' }}>
              Data: {formatDate(prescricao.data_prescricao)}
            </p>
          </div>

          <div style={{ marginBottom: 40, border: '1px solid #ccc', padding: 20, borderRadius: 8, display: 'flex', gap: 20 }}>
            <div style={{ flex: 1 }}>
              <h3 style={{ margin: '0 0 10px', fontSize: '1rem' }}>Animal</h3>
              <p style={{ margin: 0, fontSize: '0.9rem' }}>
                <strong>ID:</strong> {prescricao.pet_id}<br/>
                <strong>Nome:</strong> {prescricao.pet_nome}
              </p>
            </div>
            <div style={{ flex: 1 }}>
              <h3 style={{ margin: '0 0 10px', fontSize: '1rem' }}>Responsável</h3>
              <p style={{ margin: 0, fontSize: '0.9rem' }}>
                <strong>Nome:</strong> {prescricao.tutor_nome}<br/>
                <strong>Endereço:</strong> Não informado
              </p>
            </div>
          </div>

          <h3 style={{ marginBottom: 20 }}>USO ORAL / EXTERNO</h3>

          {prescricao.itens.map((item) => (
            <div key={item.id} style={{ marginBottom: 25 }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', borderBottom: '1px dashed #ccc', paddingBottom: 5, marginBottom: 10 }}>
                <strong>{item.medicamento}</strong>
                <span style={{ border: '1px solid #333', padding: '2px 8px', borderRadius: 4, fontSize: '0.8rem' }}>
                  A DEFINIR QTD
                </span>
              </div>
              <p style={{ margin: 0, fontSize: '0.9rem' }}>
                Dar {[item.dosagem, item.frequencia, item.duracao, item.via_administracao, item.particao]
                  .filter(Boolean)
                  .join(' · ')}
              </p>
            </div>
          ))}

          {prescricao.observacoes && (
            <div style={{ marginTop: 40, padding: 20, backgroundColor: '#f9f9f9', borderRadius: 8 }}>
              <strong>Observações:</strong>
              <p style={{ margin: '5px 0 0', fontSize: '0.9rem' }}>{prescricao.observacoes}</p>
            </div>
          )}
        </ReportTemplate>
      )}
    </div>
  )
}
