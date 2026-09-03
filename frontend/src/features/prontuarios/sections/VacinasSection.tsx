import { Plus, Syringe, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { ApiError } from '../../../lib/api'
import { addVacina, deleteVacina } from '../api'
import { useProntuario } from '../ProntuarioContext'
import { emptyVacinaForm, type Vacina, type VacinaFormValues } from '../types'

function formatDate(value: string | null): string {
  if (!value) return '—'
  try {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(value))
  } catch {
    return value
  }
}

function vacinaStatus(vacina: Vacina): { label: string; className: string } | null {
  if (!vacina.data_proxima_dose) return null

  const proxima = new Date(vacina.data_proxima_dose)
  const hoje = new Date()
  hoje.setHours(0, 0, 0, 0)
  const emSeteDias = new Date(hoje)
  emSeteDias.setDate(emSeteDias.getDate() + 7)

  if (proxima < hoje) return { label: 'Atrasada', className: 'badge--danger' }
  if (proxima <= emSeteDias) return { label: 'Próxima', className: 'badge--warning' }
  return null
}

export function VacinasSection() {
  const { pacienteId, record, reload } = useProntuario()
  const [values, setValues] = useState<VacinaFormValues>({ ...emptyVacinaForm })
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)

  async function handleSubmit() {
    if (!values.vacina_nome.trim() || !values.data_aplicacao) {
      setError('Preencha o nome da vacina e a data de aplicação.')
      return
    }
    setError(null)
    setIsSaving(true)
    try {
      await addVacina(pacienteId, values)
      setValues({ ...emptyVacinaForm })
      reload()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Erro ao registrar vacina.')
    } finally {
      setIsSaving(false)
    }
  }

  async function handleDelete(vacina: Vacina) {
    if (!window.confirm(`Excluir o registro da vacina "${vacina.vacina_nome}"?`)) return
    await deleteVacina(vacina.id)
    reload()
  }

  return (
    <div className="prontuario-section">
      <div className="side-card">
        <h3>
          <Syringe size={16} />
          Nova Vacina
        </h3>
        {error && (
          <p className="error-message" role="alert">
            {error}
          </p>
        )}
        <div className="form-grid">
          <label className="form-field">
            Vacina *
            <input
              value={values.vacina_nome}
              onChange={(event) => setValues((prev) => ({ ...prev, vacina_nome: event.target.value }))}
            />
          </label>
          <label className="form-field">
            Data de Aplicação *
            <input
              type="date"
              value={values.data_aplicacao}
              onChange={(event) => setValues((prev) => ({ ...prev, data_aplicacao: event.target.value }))}
            />
          </label>
          <label className="form-field">
            Próxima Dose (reforço)
            <input
              type="date"
              value={values.data_proxima_dose}
              onChange={(event) => setValues((prev) => ({ ...prev, data_proxima_dose: event.target.value }))}
            />
          </label>
          <label className="form-field">
            Lote
            <input value={values.lote} onChange={(event) => setValues((prev) => ({ ...prev, lote: event.target.value }))} />
          </label>
          <label className="form-field">
            Fabricante
            <input
              value={values.fabricante}
              onChange={(event) => setValues((prev) => ({ ...prev, fabricante: event.target.value }))}
            />
          </label>
          <label className="form-field form-field--full">
            Observações
            <textarea
              rows={2}
              value={values.observacoes}
              onChange={(event) => setValues((prev) => ({ ...prev, observacoes: event.target.value }))}
            />
          </label>
        </div>
        <div className="modal__footer">
          <button type="button" className="btn btn--primary" disabled={isSaving} onClick={() => void handleSubmit()}>
            <Plus size={16} />
            {isSaving ? 'Salvando...' : 'Registrar Vacina'}
          </button>
        </div>
      </div>

      <div className="side-card">
        <h3>Histórico de Vacinas</h3>
        {record.vacinas.length === 0 && <p className="empty-state">Nenhuma vacina registrada.</p>}
        <table className="relatorio-table">
          <thead>
            <tr>
              <th>Vacina</th>
              <th>Aplicação</th>
              <th>Próxima Dose</th>
              <th>Lote/Fabricante</th>
              <th>Status</th>
              <th />
            </tr>
          </thead>
          <tbody>
            {record.vacinas.map((vacina) => {
              const status = vacinaStatus(vacina)
              return (
                <tr key={vacina.id}>
                  <td>{vacina.vacina_nome}</td>
                  <td>{formatDate(vacina.data_aplicacao)}</td>
                  <td>{formatDate(vacina.data_proxima_dose)}</td>
                  <td>{[vacina.lote, vacina.fabricante].filter(Boolean).join(' / ') || '—'}</td>
                  <td>{status && <span className={`badge ${status.className}`}>{status.label}</span>}</td>
                  <td>
                    <button
                      type="button"
                      className="icon-btn icon-btn--danger"
                      aria-label={`Excluir vacina ${vacina.vacina_nome}`}
                      title={`Excluir vacina ${vacina.vacina_nome}`}
                      onClick={() => void handleDelete(vacina)}
                    >
                      <Trash2 size={14} />
                    </button>
                  </td>
                </tr>
              )
            })}
          </tbody>
        </table>
      </div>
    </div>
  )
}
