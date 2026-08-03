import { Plus, Scale, Trash2 } from 'lucide-react'
import { useMemo, useState } from 'react'
import { ApiError } from '../../../lib/api'
import { addPeso, deletePeso } from '../api'
import { useProntuario } from '../ProntuarioContext'
import { emptyPesoForm, type Peso, type PesoFormValues } from '../types'

function formatDate(value: string): string {
  try {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(value))
  } catch {
    return value
  }
}

const CHART_WIDTH = 500
const CHART_HEIGHT = 200
const PADDING = 30

function GrowthChart({ pesos }: { pesos: Peso[] }) {
  const ascending = useMemo(() => [...pesos].reverse(), [pesos])

  if (ascending.length < 2) {
    return <p className="empty-state">Registre pelo menos 2 pesagens para visualizar o gráfico de evolução.</p>
  }

  const values = ascending.map((p) => p.peso)
  const min = Math.min(...values)
  const max = Math.max(...values)
  const range = max - min || 1

  const points = ascending.map((peso, index) => {
    const x = PADDING + (index / (ascending.length - 1)) * (CHART_WIDTH - PADDING * 2)
    const y = CHART_HEIGHT - PADDING - ((peso.peso - min) / range) * (CHART_HEIGHT - PADDING * 2)
    return { x, y, peso }
  })

  const linePath = points.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x},${p.y}`).join(' ')
  const areaPath = `${linePath} L${points[points.length - 1].x},${CHART_HEIGHT - PADDING} L${points[0].x},${CHART_HEIGHT - PADDING} Z`

  return (
    <svg viewBox={`0 0 ${CHART_WIDTH} ${CHART_HEIGHT}`} className="prontuario-peso-chart">
      <defs>
        <linearGradient id="pesoGradient" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stopColor="#e05f8a" stopOpacity="0.35" />
          <stop offset="100%" stopColor="#e05f8a" stopOpacity="0" />
        </linearGradient>
      </defs>
      <path d={areaPath} fill="url(#pesoGradient)" stroke="none" />
      <path d={linePath} fill="none" stroke="#e05f8a" strokeWidth="2" />
      {points.map((p, i) => (
        <circle key={i} cx={p.x} cy={p.y} r={4} fill="#e05f8a">
          <title>
            {formatDate(p.peso.data_pesagem)}: {p.peso.peso.toFixed(3)} kg
          </title>
        </circle>
      ))}
    </svg>
  )
}

export function PesoSection() {
  const { pacienteId, record, reload } = useProntuario()
  const [values, setValues] = useState<PesoFormValues>({ ...emptyPesoForm })
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)

  async function handleSubmit() {
    if (!values.peso || Number(values.peso) <= 0) {
      setError('Informe um peso válido.')
      return
    }
    setError(null)
    setIsSaving(true)
    try {
      await addPeso(pacienteId, values)
      setValues({ ...emptyPesoForm })
      reload()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Erro ao registrar peso.')
    } finally {
      setIsSaving(false)
    }
  }

  async function handleDelete(peso: Peso) {
    if (!window.confirm('Excluir este registro de peso?')) return
    await deletePeso(peso.id)
    reload()
  }

  return (
    <div className="prontuario-section">
      <div className="side-card">
        <h3>
          <Scale size={16} />
          Nova Pesagem
        </h3>
        {error && (
          <p className="error-message" role="alert">
            {error}
          </p>
        )}
        <div className="form-grid">
          <label className="form-field">
            Peso (kg) *
            <input
              type="number"
              min="0"
              step="0.001"
              value={values.peso}
              onChange={(event) => setValues((prev) => ({ ...prev, peso: event.target.value }))}
            />
          </label>
          <label className="form-field">
            Data da Pesagem *
            <input
              type="date"
              value={values.data_pesagem}
              onChange={(event) => setValues((prev) => ({ ...prev, data_pesagem: event.target.value }))}
            />
          </label>
          <label className="form-field form-field--full">
            Observações
            <textarea
              rows={2}
              placeholder="Ex: em jejum"
              value={values.observacoes}
              onChange={(event) => setValues((prev) => ({ ...prev, observacoes: event.target.value }))}
            />
          </label>
        </div>
        <div className="modal__footer">
          <button type="button" className="btn btn--primary" disabled={isSaving} onClick={() => void handleSubmit()}>
            <Plus size={16} />
            {isSaving ? 'Salvando...' : 'Registrar Peso'}
          </button>
        </div>
      </div>

      <div className="side-card">
        <h3>Evolução do Peso</h3>
        <GrowthChart pesos={record.pesos} />
      </div>

      <div className="side-card">
        <h3>Histórico de Pesagens</h3>
        {record.pesos.length === 0 && <p className="empty-state">Nenhum registro de peso.</p>}
        <table className="relatorio-table">
          <thead>
            <tr>
              <th>Data</th>
              <th>Peso</th>
              <th>Observações</th>
              <th />
            </tr>
          </thead>
          <tbody>
            {record.pesos.map((peso) => (
              <tr key={peso.id}>
                <td>{formatDate(peso.data_pesagem)}</td>
                <td>{peso.peso.toFixed(3)} kg</td>
                <td>{peso.observacoes || '—'}</td>
                <td>
                  <button
                    type="button"
                    className="icon-btn icon-btn--danger"
                    aria-label="Excluir pesagem"
                    onClick={() => void handleDelete(peso)}
                  >
                    <Trash2 size={14} />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
