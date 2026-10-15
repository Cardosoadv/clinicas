import { X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useEscapeKey } from '../../hooks/useEscapeKey'
import { fetchGrupoDespesas } from './api'
import { hojeISO, statusExibido } from './despesas'
import type { Despesa } from './types'

const statusBadgeClass: Record<string, string> = {
  Pendente: 'badge--warning',
  Pago: 'badge--success',
  Atrasado: 'badge--danger',
  Cancelado: 'badge--danger',
}

interface ParcelasGrupoModalProps {
  despesa: Despesa
  onClose: () => void
}

function formatCurrency(value: number): string {
  return Number(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(value: string): string {
  const [ano, mes, dia] = value.split('-')
  return `${dia}/${mes}/${ano}`
}

export function ParcelasGrupoModal({ despesa, onClose }: ParcelasGrupoModalProps) {
  const [parcelas, setParcelas] = useState<Despesa[] | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEscapeKey(onClose)

  useEffect(() => {
    if (despesa.des_grupo_id === null) return
    let cancelado = false
    fetchGrupoDespesas(despesa.des_grupo_id)
      .then((lista) => !cancelado && setParcelas(lista))
      .catch(() => !cancelado && setError('Não foi possível carregar as parcelas.'))
    return () => {
      cancelado = true
    }
  }, [despesa.des_grupo_id])

  const hoje = hojeISO()
  const ativas = (parcelas ?? []).filter((p) => p.status !== 'Cancelado')
  const total = ativas.reduce((soma, p) => soma + Number(p.valor), 0)

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal" onClick={(event) => event.stopPropagation()} role="dialog" aria-modal="true">
        <div className="modal__header">
          <h2>Parcelas — {despesa.descricao}</h2>
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
          {!error && !parcelas && <p className="empty-state">Carregando...</p>}

          {parcelas && (
            <>
              <table className="parcelamento__tabela">
                <thead>
                  <tr>
                    <th>Parcela</th>
                    <th>Vencimento</th>
                    <th>Valor</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  {parcelas.map((p) => {
                    const status = statusExibido(p.status, p.data_vencimento, hoje)
                    return (
                      <tr key={p.id} className={p.id === despesa.id ? 'parcelamento__atual' : undefined}>
                        <td>{p.des_parcela_num !== null ? `${p.des_parcela_num}/${p.des_parcela_total}` : '—'}</td>
                        <td>{formatDate(p.data_vencimento)}</td>
                        <td>{formatCurrency(p.valor)}</td>
                        <td>
                          <span className={`badge ${statusBadgeClass[status]}`}>{status}</span>
                        </td>
                      </tr>
                    )
                  })}
                </tbody>
              </table>
              <p className="parcelamento__resumo">Total das parcelas não canceladas: {formatCurrency(total)}</p>
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
