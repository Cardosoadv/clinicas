import { Images, Upload } from 'lucide-react'
import { useState } from 'react'
import { ApiError } from '../../../lib/api'
import { addImagem, imagemUrl } from '../api'
import { useProntuario } from '../ProntuarioContext'

export function ImagensSection() {
  const { pacienteId, record, reload } = useProntuario()
  const [titulo, setTitulo] = useState('')
  const [dataExame, setDataExame] = useState(new Date().toISOString().slice(0, 10))
  const [arquivo, setArquivo] = useState<File | null>(null)
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)

  async function handleSubmit() {
    if (!titulo.trim() || !arquivo) {
      setError('Informe um título e selecione um arquivo de imagem.')
      return
    }
    setError(null)
    setIsSaving(true)
    try {
      await addImagem(pacienteId, titulo.trim(), dataExame, arquivo)
      setTitulo('')
      setArquivo(null)
      reload()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Erro ao enviar imagem.')
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <div className="prontuario-section">
      <div className="side-card">
        <h3>
          <Images size={16} />
          Nova Imagem / Exame
        </h3>
        {error && (
          <p className="error-message" role="alert">
            {error}
          </p>
        )}

        <div className="form-grid">
          <label className="form-field">
            Título *
            <input
              value={titulo}
              onChange={(event) => setTitulo(event.target.value)}
              placeholder="Ex: Raio-X Pata Direita"
            />
          </label>
          <label className="form-field">
            Data do Exame
            <input type="date" value={dataExame} onChange={(event) => setDataExame(event.target.value)} />
          </label>
          <label className="form-field form-field--full">
            Arquivo (PNG/JPG/WEBP, até 10MB) *
            <input
              type="file"
              accept="image/png,image/jpeg,image/jpg,image/webp"
              onChange={(event) => setArquivo(event.target.files?.[0] ?? null)}
            />
          </label>
        </div>

        <div className="modal__footer">
          <button type="button" className="btn btn--primary" disabled={isSaving} onClick={() => void handleSubmit()}>
            <Upload size={16} />
            {isSaving ? 'Enviando...' : 'Fazer Upload'}
          </button>
        </div>
      </div>

      <div className="side-card">
        <h3>Galeria</h3>
        {record.imagens.length === 0 && <p className="empty-state">Nenhuma imagem registrada.</p>}
        <div className="prontuario-imagens__grid">
          {record.imagens.map((imagem) => {
            const url = imagemUrl(pacienteId, imagem.arquivo_url)
            return (
              <a
                key={imagem.id}
                href={url}
                target="_blank"
                rel="noopener noreferrer"
                className="prontuario-imagens__thumb"
              >
                <img src={url} alt={imagem.titulo} crossOrigin="use-credentials" />
                <span>{imagem.titulo}</span>
              </a>
            )
          })}
        </div>
      </div>
    </div>
  )
}
