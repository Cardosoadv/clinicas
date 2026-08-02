import { Eye, Pill, Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { ApiError } from '../../../lib/api'
import { addPrescricao } from '../api'
import { PrescricaoDetalhesModal } from '../PrescricaoDetalhesModal'
import { useProntuario } from '../ProntuarioContext'
import { emptyPrescricaoForm, emptyPrescricaoItem, type PrescricaoFormValues } from '../types'

function formatDate(value: string): string {
  try {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(value))
  } catch {
    return value
  }
}

const particaoOptions = ['1 Inteiro', '1/2 Metade', '1/4 Um quarto', '3/4 Três quartos']

export function PrescricoesSection() {
  const { pacienteId, record, reload } = useProntuario()
  const [values, setValues] = useState<PrescricaoFormValues>({ ...emptyPrescricaoForm, itens: [{ ...emptyPrescricaoItem }] })
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [detalhesId, setDetalhesId] = useState<number | null>(null)

  function updateItem(index: number, field: keyof typeof emptyPrescricaoItem, value: string) {
    setValues((prev) => ({
      ...prev,
      itens: prev.itens.map((item, i) => (i === index ? { ...item, [field]: value } : item)),
    }))
  }

  function addItem() {
    setValues((prev) => ({ ...prev, itens: [...prev.itens, { ...emptyPrescricaoItem }] }))
  }

  function removeItem(index: number) {
    setValues((prev) => ({ ...prev, itens: prev.itens.filter((_, i) => i !== index) }))
  }

  async function handleSubmit() {
    const validItens = values.itens.filter((item) => item.medicamento.trim())
    if (validItens.length === 0) {
      setError('Adicione ao menos um item com medicamento preenchido.')
      return
    }
    setError(null)
    setIsSaving(true)
    try {
      await addPrescricao(pacienteId, values)
      setValues({ ...emptyPrescricaoForm, itens: [{ ...emptyPrescricaoItem }] })
      reload()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Erro ao registrar prescrição.')
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <div className="prontuario-section">
      <div className="side-card">
        <h3>
          <Pill size={16} />
          Nova Prescrição
        </h3>
        {error && (
          <p className="error-message" role="alert">
            {error}
          </p>
        )}

        <div className="form-grid">
          <label className="form-field">
            Data *
            <input
              type="date"
              value={values.data_prescricao}
              onChange={(event) => setValues((prev) => ({ ...prev, data_prescricao: event.target.value }))}
            />
          </label>
        </div>

        <div className="prontuario-prescricao__itens-form">
          {values.itens.map((item, index) => (
            <div className="prontuario-prescricao__item-row" key={index}>
              <div className="form-grid">
                <label className="form-field">
                  Medicamento
                  <input value={item.medicamento} onChange={(event) => updateItem(index, 'medicamento', event.target.value)} />
                </label>
                <label className="form-field">
                  Dosagem
                  <input value={item.dosagem} onChange={(event) => updateItem(index, 'dosagem', event.target.value)} />
                </label>
                <label className="form-field">
                  Frequência
                  <input value={item.frequencia} onChange={(event) => updateItem(index, 'frequencia', event.target.value)} />
                </label>
                <label className="form-field">
                  Duração
                  <input value={item.duracao} onChange={(event) => updateItem(index, 'duracao', event.target.value)} />
                </label>
                <label className="form-field">
                  Via
                  <input
                    value={item.via_administracao}
                    onChange={(event) => updateItem(index, 'via_administracao', event.target.value)}
                  />
                </label>
                <label className="form-field">
                  Partição
                  <select value={item.particao} onChange={(event) => updateItem(index, 'particao', event.target.value)}>
                    {particaoOptions.map((opt) => (
                      <option key={opt} value={opt}>
                        {opt}
                      </option>
                    ))}
                  </select>
                </label>
              </div>
              {values.itens.length > 1 && (
                <button type="button" className="btn btn--ghost" onClick={() => removeItem(index)}>
                  <Trash2 size={14} />
                  Remover Item
                </button>
              )}
            </div>
          ))}
        </div>

        <button type="button" className="btn btn--ghost" onClick={addItem}>
          <Plus size={14} />
          Adicionar Item
        </button>

        <label className="form-field form-field--full" style={{ marginTop: 12 }}>
          Observações
          <textarea
            rows={2}
            value={values.observacoes}
            onChange={(event) => setValues((prev) => ({ ...prev, observacoes: event.target.value }))}
          />
        </label>

        <div className="modal__footer">
          <button type="button" className="btn btn--primary" disabled={isSaving} onClick={() => void handleSubmit()}>
            <Plus size={16} />
            {isSaving ? 'Salvando...' : 'Registrar Prescrição'}
          </button>
        </div>
      </div>

      <div className="side-card">
        <h3>Histórico de Prescrições</h3>
        {record.prescricoes.length === 0 && <p className="empty-state">Nenhuma prescrição registrada.</p>}
        <div className="record-list">
          {record.prescricoes.map((prescricao) => (
            <div className="record-card" key={prescricao.id}>
              <div className="record-card__main">
                <div className="record-card__title-row">
                  <h3>Prescrição #{prescricao.id}</h3>
                </div>
                <div className="record-card__meta">
                  <span>{formatDate(prescricao.data_prescricao)}</span>
                  <span>{prescricao.veterinario_nome || 'Clínico'}</span>
                </div>
              </div>
              <div className="record-card__actions">
                <button
                  type="button"
                  className="icon-btn"
                  aria-label="Ver itens da prescrição"
                  onClick={() => setDetalhesId(prescricao.id)}
                >
                  <Eye size={16} />
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>

      {detalhesId !== null && (
        <PrescricaoDetalhesModal prescricaoId={detalhesId} onClose={() => setDetalhesId(null)} />
      )}
    </div>
  )
}
