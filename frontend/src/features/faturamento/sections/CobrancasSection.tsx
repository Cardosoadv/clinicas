import { Plus } from 'lucide-react'
import { useState } from 'react'
import { createCobranca, updateCobranca } from '../api'
import { CobrancaFormModal } from '../CobrancaFormModal'
import { useFaturamento } from '../FaturamentoContext'
import { cobrancaToFormValues, emptyCobrancaForm, type Cobranca, type CobrancaFormValues } from '../types'

type ModalState = { mode: 'create' } | { mode: 'edit'; cobranca: Cobranca } | null

const statusBadgeClass: Record<string, string> = {
  Pendente: 'badge--warning',
  Pago: 'badge--success',
  Atrasado: 'badge--danger',
  Cancelado: 'badge--danger',
}

function formatCurrency(value: number): string {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(value: string): string {
  try {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(value))
  } catch {
    return value
  }
}

export function CobrancasSection() {
  const { lancamentos, error, reloadDashboard, reloadLancamentos } = useFaturamento()
  const [modal, setModal] = useState<ModalState>(null)

  function refresh() {
    reloadDashboard()
    reloadLancamentos()
  }

  async function handleCreate(values: CobrancaFormValues) {
    await createCobranca(values)
    setModal(null)
    refresh()
  }

  async function handleUpdate(id: number, values: CobrancaFormValues) {
    await updateCobranca(id, values)
    setModal(null)
    refresh()
  }

  return (
    <>
      <div className="list-toolbar" style={{ justifyContent: 'flex-end' }}>
        <button type="button" className="btn btn--primary" onClick={() => setModal({ mode: 'create' })}>
          <Plus size={16} /> Nova Cobrança
        </button>
      </div>

      {error && <p className="empty-state empty-state--error">{error}</p>}
      {!error && !lancamentos && <p className="empty-state">Carregando...</p>}

      {!error && lancamentos && (
        <div className="record-list">
          {lancamentos.cobrancas.length === 0 && <p className="empty-state">Nenhuma cobrança registrada.</p>}
          {lancamentos.cobrancas.map((cobranca) => (
            <div
              key={cobranca.id}
              className="record-card"
              style={{ cursor: 'pointer' }}
              onClick={() => setModal({ mode: 'edit', cobranca })}
            >
              <div className="record-card__main">
                <div className="record-card__title-row">
                  <h3>{cobranca.servico}</h3>
                  <span className={`badge ${statusBadgeClass[cobranca.status]}`}>{cobranca.status}</span>
                </div>
                <div className="record-card__meta">
                  <span>Paciente: {cobranca.paciente_nome ?? '—'}</span>
                  <span>Valor: {formatCurrency(cobranca.valor)}</span>
                  <span>Vencimento: {formatDate(cobranca.vencimento)}</span>
                  <span>{cobranca.forma_pagamento}</span>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {modal && (
        <CobrancaFormModal
          title={modal.mode === 'edit' ? 'Editar Cobrança' : 'Nova Cobrança'}
          initialValues={modal.mode === 'edit' ? cobrancaToFormValues(modal.cobranca) : emptyCobrancaForm()}
          initialPacienteNome={modal.mode === 'edit' ? modal.cobranca.paciente_nome : undefined}
          onClose={() => setModal(null)}
          onSubmit={(values) => (modal.mode === 'edit' ? handleUpdate(modal.cobranca.id, values) : handleCreate(values))}
        />
      )}
    </>
  )
}
