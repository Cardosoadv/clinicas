import { Eye, Package2, Plus } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { ApiError } from '../../lib/api'
import { fetchPacotes } from './api'
import { PacoteDetailsModal } from './PacoteDetailsModal'
import { PacoteFormModal } from './PacoteFormModal'
import './pacotes.css'
import type { Pacote, PacoteStatus } from './types'

function formatCurrency(value: number): string {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

const statusBadgeClass: Record<PacoteStatus, string> = {
  Pendente: 'badge--warning',
  Ativo: 'badge--success',
  Esgotado: 'badge--info',
  Cancelado: 'badge--danger',
}

export function PacotesListPage() {
  const [pacotes, setPacotes] = useState<Pacote[] | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [statusFiltro, setStatusFiltro] = useState<PacoteStatus | ''>('')
  const [search, setSearch] = useState('')
  const [showForm, setShowForm] = useState(false)
  const [detailsId, setDetailsId] = useState<number | null>(null)

  function load() {
    setIsLoading(true)
    setError(null)
    fetchPacotes()
      .then((res) => setPacotes(res.data ?? []))
      .catch((err) => setError(err instanceof ApiError ? err.message : 'Erro ao carregar pacotes.'))
      .finally(() => setIsLoading(false))
  }

  useEffect(() => {
    load()
  }, [])

  const stats = useMemo(() => {
    const list = pacotes ?? []
    return {
      ativos: list.filter((p) => p.status === 'Ativo').length,
      pendentes: list.filter((p) => p.status === 'Pendente').length,
      esgotados: list.filter((p) => p.status === 'Esgotado').length,
    }
  }, [pacotes])

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase()
    return (pacotes ?? []).filter((pacote) => {
      if (statusFiltro && pacote.status !== statusFiltro) return false
      if (term) {
        const haystack = `${pacote.nome} ${pacote.paciente_nome ?? ''}`.toLowerCase()
        if (!haystack.includes(term)) return false
      }
      return true
    })
  }, [pacotes, statusFiltro, search])

  return (
    <div className="list-page">
      <div className="page-header">
        <div>
          <h2>Pacotes</h2>
          <p className="page-subtitle">Pacotes de serviços e créditos pré-pagos</p>
        </div>
        <button type="button" className="btn btn--primary" onClick={() => setShowForm(true)}>
          <Plus size={16} />
          Novo Pacote
        </button>
      </div>

      <div className="stat-pills">
        <div className="stat-pill stat-pill--success">
          <Package2 size={16} />
          Ativos: <strong>{stats.ativos}</strong>
        </div>
        <div className="stat-pill">
          <Package2 size={16} />
          Pendentes: <strong>{stats.pendentes}</strong>
        </div>
        <div className="stat-pill">
          <Package2 size={16} />
          Esgotados: <strong>{stats.esgotados}</strong>
        </div>
      </div>

      <div className="list-toolbar">
        <input
          type="search"
          placeholder="Buscar por paciente ou nome do pacote..."
          value={search}
          onChange={(event) => setSearch(event.target.value)}
          style={{
            flex: 1,
            padding: '9px 14px',
            borderRadius: 8,
            border: '1px solid #e7e3da',
            font: 'inherit',
          }}
        />
        <select
          className="filter-select"
          value={statusFiltro}
          onChange={(event) => setStatusFiltro(event.target.value as PacoteStatus | '')}
        >
          <option value="">Todos os status</option>
          <option value="Pendente">Pendente</option>
          <option value="Ativo">Ativo</option>
          <option value="Esgotado">Esgotado</option>
          <option value="Cancelado">Cancelado</option>
        </select>
      </div>

      <div className="record-list">
        {isLoading && <p className="empty-state">Carregando...</p>}
        {!isLoading && error && <p className="empty-state empty-state--error">{error}</p>}
        {!isLoading && !error && filtered.length === 0 && <p className="empty-state">Nenhum pacote encontrado.</p>}

        {!isLoading &&
          !error &&
          filtered.map((pacote) => (
            <div key={pacote.id} className="record-card">
              <div className="record-card__main">
                <div className="record-card__title-row">
                  <h3>
                    {pacote.paciente_avatar || '🐾'} {pacote.nome}
                  </h3>
                  <span className="badge badge--info">{pacote.tipo}</span>
                  <span className={`badge ${statusBadgeClass[pacote.status]}`}>{pacote.status}</span>
                </div>
                <div className="record-card__meta">
                  <span>Paciente: {pacote.paciente_nome ?? '—'}</span>
                  <span>
                    Validade:{' '}
                    {pacote.data_validade
                      ? new Intl.DateTimeFormat('pt-BR').format(new Date(pacote.data_validade))
                      : 'Indeterminado'}
                  </span>
                  {pacote.tipo === 'Crédito' && <span>Saldo: {formatCurrency(pacote.saldo_valor)}</span>}
                </div>
              </div>

              <div className="record-card__actions">
                <button
                  type="button"
                  className="icon-btn"
                  aria-label="Ver detalhes"
                  onClick={() => setDetailsId(pacote.id)}
                >
                  <Eye size={16} />
                </button>
              </div>
            </div>
          ))}
      </div>

      {showForm && (
        <PacoteFormModal
          onClose={() => setShowForm(false)}
          onSaved={() => {
            setShowForm(false)
            load()
          }}
        />
      )}

      {detailsId !== null && <PacoteDetailsModal pacoteId={detailsId} onClose={() => setDetailsId(null)} />}
    </div>
  )
}
