import { FileHeart } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { ApiError } from '../../lib/api'
import type { Paciente } from '../pacientes/types'
import { fetchProntuarioPacientes } from './api'
import './prontuarios.css'

const statusBadgeClass: Record<string, string> = {
  Ativo: 'badge--success',
  Inativo: 'badge--danger',
  Suspenso: 'badge--warning',
}

export function ProntuarioListPage() {
  const navigate = useNavigate()
  const [search, setSearch] = useState('')
  const [pacientes, setPacientes] = useState<Paciente[] | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    const timeout = setTimeout(() => {
      setIsLoading(true)
      setError(null)
      fetchProntuarioPacientes(search)
        .then((res) => setPacientes(res.data ?? []))
        .catch((err) => setError(err instanceof ApiError ? err.message : 'Erro ao carregar pacientes.'))
        .finally(() => setIsLoading(false))
    }, 300)

    return () => clearTimeout(timeout)
  }, [search])

  return (
    <div className="list-page">
      <div className="page-header">
        <div>
          <h2>Prontuários</h2>
          <p className="page-subtitle">Selecione um paciente para abrir a ficha clínica completa</p>
        </div>
      </div>

      <div className="list-toolbar">
        <input
          type="search"
          placeholder="Buscar por paciente, tutor ou CPF..."
          value={search}
          onChange={(event) => setSearch(event.target.value)}
        />
      </div>

      {isLoading && <p className="empty-state">Carregando...</p>}
      {!isLoading && error && <p className="empty-state empty-state--error">{error}</p>}
      {!isLoading && !error && pacientes?.length === 0 && <p className="empty-state">Nenhum paciente encontrado.</p>}

      <div className="prontuario-picker-grid">
        {!isLoading &&
          !error &&
          pacientes?.map((paciente) => (
            <button
              type="button"
              key={paciente.paciente_id}
              className="prontuario-picker-card"
              onClick={() => navigate(`/prontuarios/${paciente.paciente_id}`)}
            >
              <span className="paciente-avatar">{paciente.paciente_avatar || '🐾'}</span>
              <div className="prontuario-picker-card__info">
                <strong>{paciente.paciente_nome}</strong>
                <span>{paciente.pet_resp_nome || 'Tutor não informado'}</span>
                <span>{paciente.paciente_especie || 'Espécie não informada'}</span>
              </div>
              <span className={`badge ${statusBadgeClass[paciente.paciente_status] ?? 'badge--info'}`}>
                {paciente.paciente_status}
              </span>
              <FileHeart size={18} className="prontuario-picker-card__icon" />
            </button>
          ))}
      </div>
    </div>
  )
}
