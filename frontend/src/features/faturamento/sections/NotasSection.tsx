import { FileText, Plus } from 'lucide-react'
import { useState } from 'react'
import { createNota } from '../api'
import { useFaturamento } from '../FaturamentoContext'
import { NotaFormModal } from '../NotaFormModal'

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

export function NotasSection() {
  const { notas, error, reloadNotas } = useFaturamento()
  const [showModal, setShowModal] = useState(false)

  return (
    <>
      <div className="list-toolbar" style={{ justifyContent: 'flex-end' }}>
        <button type="button" className="btn btn--primary" onClick={() => setShowModal(true)}>
          <Plus size={16} /> Nova Nota
        </button>
      </div>

      {error && <p className="empty-state empty-state--error">{error}</p>}
      {!error && !notas && <p className="empty-state">Carregando...</p>}

      {!error && notas && (
        <div className="record-list">
          {notas.length === 0 && <p className="empty-state">Nenhuma nota emitida ainda.</p>}
          {notas.map((nota) => (
            <div key={nota.id} className="record-card">
              <div className="record-card__main">
                <div className="record-card__title-row">
                  <FileText size={16} />
                  <h3>
                    {nota.tipo}-{String(nota.id).padStart(3, '0')}
                  </h3>
                  <span className="badge badge--info">{nota.tipo}</span>
                </div>
                <div className="record-card__meta">
                  <span>{nota.descricao}</span>
                  <span>Valor: {formatCurrency(nota.valor)}</span>
                  <span>Emissão: {formatDate(nota.data_emissao)}</span>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {showModal && (
        <NotaFormModal
          onClose={() => setShowModal(false)}
          onSubmit={async (values) => {
            await createNota(values)
            setShowModal(false)
            reloadNotas()
          }}
        />
      )}
    </>
  )
}
