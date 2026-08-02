import { AlertTriangle, Save } from 'lucide-react'
import { useEffect, useState } from 'react'
import { ApiError } from '../../../lib/api'
import { saveAnamnese } from '../api'
import { useProntuario } from '../ProntuarioContext'
import { pacienteToAnamneseForm, type AnamneseFormValues } from '../types'

const emojiOptions = ['🐾', '🐶', '🐱', '🐹', '🐰', '🐦', '🐢', '🦎', '🐍', '🐸', '🐎', '🐟']

export function AnamneseSection() {
  const { pacienteId, record, reload } = useProntuario()
  const [values, setValues] = useState<AnamneseFormValues>(() => pacienteToAnamneseForm(record.pet))
  const [avatarFile, setAvatarFile] = useState<File | null>(null)
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [success, setSuccess] = useState(false)

  useEffect(() => {
    setValues(pacienteToAnamneseForm(record.pet))
    setAvatarFile(null)
  }, [record.pet])

  async function handleSubmit() {
    setError(null)
    setSuccess(false)
    setIsSaving(true)
    try {
      await saveAnamnese(pacienteId, values, avatarFile)
      setSuccess(true)
      reload()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Erro ao salvar anamnese.')
    } finally {
      setIsSaving(false)
    }
  }

  const { pet } = record

  return (
    <div className="prontuario-section">
      <div className="side-card">
        <h3>Foto / Avatar</h3>
        <div className="prontuario-anamnese__avatar-picker">
          {emojiOptions.map((emoji) => (
            <button
              type="button"
              key={emoji}
              className={`prontuario-anamnese__emoji${values.paciente_avatar === emoji ? ' prontuario-anamnese__emoji--active' : ''}`}
              onClick={() => {
                setValues((prev) => ({ ...prev, paciente_avatar: emoji }))
                setAvatarFile(null)
              }}
            >
              {emoji}
            </button>
          ))}
        </div>
        <label className="form-field form-field--full">
          Ou envie uma foto (PNG/JPG/WEBP, até 2MB)
          <input
            type="file"
            accept="image/png,image/jpeg,image/jpg,image/webp"
            onChange={(event) => setAvatarFile(event.target.files?.[0] ?? null)}
          />
        </label>
      </div>

      <div className="side-card">
        <h3>Dados do Paciente</h3>
        {error && (
          <p className="error-message" role="alert">
            {error}
          </p>
        )}
        {success && <p className="prontuario-success">Anamnese salva com sucesso.</p>}

        <div className="form-grid">
          <label className="form-field">
            Nome *
            <input
              value={values.paciente_nome}
              onChange={(event) => setValues((prev) => ({ ...prev, paciente_nome: event.target.value }))}
            />
          </label>
          <label className="form-field">
            Nascimento *
            <input
              type="date"
              value={values.paciente_nascimento}
              onChange={(event) => setValues((prev) => ({ ...prev, paciente_nascimento: event.target.value }))}
            />
          </label>
          <label className="form-field">
            Sexo *
            <select
              value={values.paciente_sexo}
              onChange={(event) => setValues((prev) => ({ ...prev, paciente_sexo: event.target.value }))}
            >
              <option value="">Selecione...</option>
              <option value="Macho">Macho</option>
              <option value="Fêmea">Fêmea</option>
            </select>
          </label>
          <label className="form-field">
            Espécie *
            <select
              value={values.paciente_especie}
              onChange={(event) => setValues((prev) => ({ ...prev, paciente_especie: event.target.value }))}
            >
              <option value="">Selecione...</option>
              <option value="Canina">Canina</option>
              <option value="Felina">Felina</option>
              <option value="Exóticos">Exóticos</option>
            </select>
          </label>
          <label className="form-field">
            Status
            <select
              value={values.paciente_status}
              onChange={(event) => setValues((prev) => ({ ...prev, paciente_status: event.target.value }))}
            >
              <option value="Ativo">Ativo</option>
              <option value="Inativo">Inativo</option>
              <option value="Suspenso">Suspenso</option>
            </select>
          </label>
          <label className="form-field">
            Tutor
            <input value={pet.pet_resp_nome ?? '—'} disabled />
          </label>
          <label className="form-field">
            Telefone do Tutor
            <input value={pet.pet_resp_tel ?? '—'} disabled />
          </label>
          <label className="form-field form-field--full">
            Endereço
            <input
              value={values.paciente_endereco}
              onChange={(event) => setValues((prev) => ({ ...prev, paciente_endereco: event.target.value }))}
            />
          </label>
          <label className="form-field form-field--full">
            Queixa Principal / Motivo da Consulta
            <textarea
              rows={3}
              value={values.paciente_queixa}
              onChange={(event) => setValues((prev) => ({ ...prev, paciente_queixa: event.target.value }))}
            />
          </label>
          <label className="form-field form-field--full">
            Histórico de Saúde / Doenças / Condições / Alergias
            <textarea
              rows={4}
              value={values.paciente_saude_obs}
              onChange={(event) => setValues((prev) => ({ ...prev, paciente_saude_obs: event.target.value }))}
            />
          </label>
        </div>

        <div className="modal__footer">
          <button type="button" className="btn btn--primary" disabled={isSaving} onClick={() => void handleSubmit()}>
            <Save size={16} />
            {isSaving ? 'Salvando...' : 'Salvar Alterações'}
          </button>
        </div>
      </div>

      {pet.paciente_doenca_sist === 'Sim' && (
        <div className="side-card prontuario-alert">
          <h3>
            <AlertTriangle size={16} />
            Alertas Clínicos
          </h3>
          <p>{pet.paciente_condicoes || 'Paciente possui doença sistêmica registrada.'}</p>
        </div>
      )}
    </div>
  )
}
