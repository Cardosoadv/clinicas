import { Paperclip, Plus } from 'lucide-react'
import { useState } from 'react'
import { comprovanteUrl, createDespesa, updateDespesa } from '../api'
import { DespesaFormModal } from '../DespesaFormModal'
import { useFaturamento } from '../FaturamentoContext'
import { despesaToFormValues, emptyDespesaForm, type Despesa, type DespesaFormValues } from '../types'

type ModalState = { mode: 'create' } | { mode: 'edit'; despesa: Despesa } | null

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

export function DespesasSection() {
  const { lancamentos, error, reloadDashboard, reloadLancamentos } = useFaturamento()
  const [modal, setModal] = useState<ModalState>(null)

  function refresh() {
    reloadDashboard()
    reloadLancamentos()
  }

  async function handleCreate(values: DespesaFormValues): Promise<number | void> {
    const result = await createDespesa(values)
    setModal(null)
    refresh()
    return typeof result.id === 'number' ? result.id : undefined
  }

  async function handleUpdate(id: number, values: DespesaFormValues) {
    await updateDespesa(id, values)
    setModal(null)
    refresh()
  }

  return (
    <>
      <div className="list-toolbar" style={{ justifyContent: 'flex-end' }}>
        <button type="button" className="btn btn--primary" onClick={() => setModal({ mode: 'create' })}>
          <Plus size={16} /> Nova Despesa
        </button>
      </div>

      {error && <p className="empty-state empty-state--error">{error}</p>}
      {!error && !lancamentos && <p className="empty-state">Carregando...</p>}

      {!error && lancamentos && (
        <div className="record-list">
          {lancamentos.despesas.length === 0 && <p className="empty-state">Nenhuma despesa registrada.</p>}
          {lancamentos.despesas.map((despesa) => (
            <div key={despesa.id} className="record-card">
              <div
                className="record-card__main"
                style={{ cursor: 'pointer' }}
                onClick={() => setModal({ mode: 'edit', despesa })}
              >
                <div className="record-card__title-row">
                  <h3>{despesa.descricao}</h3>
                  <span className={`badge ${statusBadgeClass[despesa.status]}`}>{despesa.status}</span>
                </div>
                <div className="record-card__meta">
                  <span>{despesa.categoria}</span>
                  <span>Valor: {formatCurrency(despesa.valor)}</span>
                  <span>Vencimento: {formatDate(despesa.data_vencimento)}</span>
                  {despesa.fornecedor && <span>Fornecedor: {despesa.fornecedor}</span>}
                </div>
              </div>
              {despesa.comprovante && (
                <div className="record-card__actions">
                  <a
                    className="icon-btn"
                    href={comprovanteUrl(despesa.id, despesa.comprovante.split('/').pop() ?? '')}
                    target="_blank"
                    rel="noreferrer"
                    aria-label="Ver comprovante"
                  >
                    <Paperclip size={16} />
                  </a>
                </div>
              )}
            </div>
          ))}
        </div>
      )}

      {modal && (
        <DespesaFormModal
          title={modal.mode === 'edit' ? 'Editar Despesa' : 'Nova Despesa'}
          initialValues={modal.mode === 'edit' ? despesaToFormValues(modal.despesa) : emptyDespesaForm()}
          onClose={() => setModal(null)}
          onSubmit={(values) => (modal.mode === 'edit' ? handleUpdate(modal.despesa.id, values) : handleCreate(values))}
        />
      )}
    </>
  )
}
