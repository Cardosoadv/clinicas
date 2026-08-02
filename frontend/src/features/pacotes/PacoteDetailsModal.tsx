import { X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { fetchPacoteDetalhes } from './api'
import type { PacoteDetalhes } from './types'

interface PacoteDetailsModalProps {
  pacoteId: number
  onClose: () => void
}

function formatCurrency(value: number): string {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

export function PacoteDetailsModal({ pacoteId, onClose }: PacoteDetailsModalProps) {
  const [detalhes, setDetalhes] = useState<PacoteDetalhes | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    fetchPacoteDetalhes(pacoteId)
      .then((res) => setDetalhes(res.data ?? null))
      .finally(() => setIsLoading(false))
  }, [pacoteId])

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal" onClick={(event) => event.stopPropagation()}>
        <div className="modal__header">
          <h2>Detalhes do Pacote</h2>
          <button type="button" onClick={onClose} aria-label="Fechar">
            <X size={18} />
          </button>
        </div>

        <div className="modal__form">
          {isLoading && <p className="empty-state">Carregando...</p>}

          {!isLoading && detalhes && (
            <>
              <div className="form-grid">
                <div className="form-field">
                  Paciente
                  <strong>{detalhes.paciente_nome}</strong>
                </div>
                <div className="form-field">
                  Pacote
                  <strong>{detalhes.nome}</strong>
                </div>
                <div className="form-field">
                  Tipo
                  <span className="badge badge--info">{detalhes.tipo}</span>
                </div>
                <div className="form-field">
                  Status
                  <span className="badge badge--success">{detalhes.status}</span>
                </div>
                {detalhes.tipo === 'Crédito' && (
                  <div className="form-field">
                    Saldo disponível
                    <strong>{formatCurrency(detalhes.saldo_valor)}</strong>
                  </div>
                )}
                {detalhes.data_validade && (
                  <div className="form-field">
                    Validade
                    <span>{new Intl.DateTimeFormat('pt-BR').format(new Date(detalhes.data_validade))}</span>
                  </div>
                )}
              </div>

              {detalhes.tipo === 'Serviços' && (
                <div className="pacote-detalhes-section">
                  <h4>Itens</h4>
                  <table className="pacote-detalhes-table">
                    <thead>
                      <tr>
                        <th>Item</th>
                        <th>Usado / Total</th>
                        <th>Valor unit.</th>
                      </tr>
                    </thead>
                    <tbody>
                      {detalhes.itens.map((item) => (
                        <tr key={item.id}>
                          <td>{item.servico_nome ?? item.item_nome ?? '—'}</td>
                          <td>
                            {item.quantidade_usada} / {item.quantidade_total}
                          </td>
                          <td>{formatCurrency(item.valor_unitario)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}

              <div className="pacote-detalhes-section">
                <h4>Histórico de uso</h4>
                {detalhes.historico.length === 0 && <p className="empty-state">Nenhum uso registrado ainda.</p>}
                {detalhes.historico.length > 0 && (
                  <table className="pacote-detalhes-table">
                    <thead>
                      <tr>
                        <th>Data</th>
                        <th>Item</th>
                        <th>Valor</th>
                      </tr>
                    </thead>
                    <tbody>
                      {detalhes.historico.map((uso) => (
                        <tr key={uso.id}>
                          <td>{new Intl.DateTimeFormat('pt-BR').format(new Date(uso.data_uso))}</td>
                          <td>{uso.servico_nome ?? uso.observacao ?? '—'}</td>
                          <td>{formatCurrency(uso.valor_descontado)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                )}
              </div>
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
