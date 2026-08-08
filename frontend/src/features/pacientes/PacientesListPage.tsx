import { Cake, Calendar, ClipboardList, Clock, PawPrint, Pencil, Plus, Trash2, Users } from 'lucide-react'
import { useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { ApiError } from '../../lib/api'
import { createPaciente, deletePaciente, fetchPacientes, fetchPacientesPainel, updatePaciente } from './api'
import { PacienteFormModal } from './PacienteFormModal'
import { PacienteAvatar } from '../../components/PacienteAvatar'
import './pacientes.css'
import {
  emptyPacienteForm,
  pacienteToFormValues,
  type Paciente,
  type PacienteFormValues,
  type PacienteStatusFiltro,
  type PacientesPainel,
} from './types'

type ModalState = { mode: 'create' } | { mode: 'edit'; paciente: Paciente } | null

const statusTabs: { value: PacienteStatusFiltro; label: string }[] = [
  { value: 'Ativo', label: 'Ativos' },
  { value: 'Inativo', label: 'Inativos' },
  { value: 'Suspenso', label: 'Suspensos' },
  { value: '', label: 'Todos' },
]

const statusBadgeClass: Record<Paciente['paciente_status'], string> = {
  Ativo: 'badge--success',
  Inativo: 'badge--danger',
  Suspenso: 'badge--warning',
}

function formatDate(value: string): string {
  try {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(value))
  } catch {
    return value
  }
}

export function PacientesListPage() {
  const navigate = useNavigate()
  const [searchParams, setSearchParams] = useSearchParams()

  const [status, setStatus] = useState<PacienteStatusFiltro>(
    (searchParams.get('status') as PacienteStatusFiltro | null) ?? 'Ativo',
  )
  const [especie, setEspecie] = useState('')
  const [search, setSearch] = useState(searchParams.get('search') ?? '')

  const [pacientes, setPacientes] = useState<Paciente[] | null>(null)
  const [painel, setPainel] = useState<PacientesPainel | null>(null)
  const [isLoadingList, setIsLoadingList] = useState(true)
  const [listError, setListError] = useState<string | null>(null)

  const [modal, setModal] = useState<ModalState>(null)

  const lastPushed = useRef<{ search: string; status: PacienteStatusFiltro } | null>(null)

  useEffect(() => {
    const urlSearch = searchParams.get('search') ?? ''
    const urlStatus = (searchParams.get('status') as PacienteStatusFiltro | null) ?? 'Ativo'

    if (lastPushed.current?.search === urlSearch && lastPushed.current?.status === urlStatus) {
      return
    }

    setSearch(urlSearch)
    setStatus(urlStatus)
  }, [searchParams])

  function loadPainel() {
    fetchPacientesPainel()
      .then((res) => setPainel(res.data ?? null))
      .catch(() => setPainel(null))
  }

  useEffect(() => {
    loadPainel()
  }, [])

  useEffect(() => {
    const timeout = setTimeout(() => {
      setIsLoadingList(true)
      setListError(null)

      fetchPacientes(status, especie, search)
        .then((res) => setPacientes(res.data ?? []))
        .catch((err) => setListError(err instanceof ApiError ? err.message : 'Erro ao carregar pacientes.'))
        .finally(() => setIsLoadingList(false))

      lastPushed.current = { search, status }
      const next = new URLSearchParams()
      if (search.trim()) next.set('search', search.trim())
      if (status !== 'Ativo') next.set('status', status)
      setSearchParams(next, { replace: true })
    }, 300)

    return () => clearTimeout(timeout)
  }, [status, especie, search])

  const stats = painel?.stats

  function refreshAfterMutation() {
    loadPainel()
    fetchPacientes(status, especie, search)
      .then((res) => setPacientes(res.data ?? []))
      .catch(() => undefined)
  }

  async function handleCreate(values: PacienteFormValues) {
    await createPaciente(values)
    setModal(null)
    refreshAfterMutation()
  }

  async function handleUpdate(id: number, values: PacienteFormValues) {
    await updatePaciente(id, values)
    setModal(null)
    refreshAfterMutation()
  }

  async function handleDelete(paciente: Paciente) {
    if (!window.confirm(`Excluir o paciente "${paciente.paciente_nome}"?`)) {
      return
    }
    await deletePaciente(paciente.paciente_id)
    refreshAfterMutation()
  }

  const modalTitle = useMemo(() => (modal?.mode === 'edit' ? 'Editar Paciente' : 'Novo Paciente'), [modal])
  const modalInitialValues = modal?.mode === 'edit' ? pacienteToFormValues(modal.paciente) : emptyPacienteForm

  return (
    <div className="list-page">
      <div className="page-header">
        <div>
          <h2>Pacientes</h2>
          <p className="page-subtitle">Cadastro de pacientes vinculados aos tutores da clínica</p>
        </div>
        <button type="button" className="btn btn--primary" onClick={() => setModal({ mode: 'create' })}>
          <Plus size={16} />
          Novo Paciente
        </button>
      </div>

      <div className="stat-pills">
        <div className="stat-pill">
          <PawPrint size={16} />
          Total: <strong>{stats?.total ?? '—'}</strong>
        </div>
        <div className="stat-pill stat-pill--success">
          <PawPrint size={16} />
          Ativos: <strong>{stats?.ativos ?? '—'}</strong>
        </div>
        <div className="stat-pill">
          <PawPrint size={16} />
          Inativos: <strong>{stats?.inativos ?? '—'}</strong>
        </div>
        <div className="stat-pill">
          <PawPrint size={16} />
          Suspensos: <strong>{stats?.suspensos ?? '—'}</strong>
        </div>
      </div>

      <div className="list-layout">
        <div className="list-main">
          <div className="list-toolbar">
            <div className="tabs">
              {statusTabs.map((tab) => (
                <button
                  key={tab.label}
                  type="button"
                  className={`tab${status === tab.value ? ' tab--active' : ''}`}
                  onClick={() => setStatus(tab.value)}
                >
                  {tab.label}
                </button>
              ))}
            </div>

            <select className="filter-select" value={especie} onChange={(event) => setEspecie(event.target.value)}>
              <option value="">Todas as Espécies</option>
              {painel?.especies.map((item) => (
                <option key={item} value={item}>
                  {item}
                </option>
              ))}
            </select>
          </div>

          <div className="record-list">
            {isLoadingList && <p className="empty-state">Carregando...</p>}

            {!isLoadingList && listError && <p className="empty-state empty-state--error">{listError}</p>}

            {!isLoadingList && !listError && pacientes?.length === 0 && (
              <p className="empty-state">Nenhum paciente encontrado.</p>
            )}

            {!isLoadingList &&
              !listError &&
              pacientes?.map((paciente) => (
                <div
                  key={paciente.paciente_id}
                  className={`record-card${paciente.paciente_status !== 'Ativo' ? ' record-card--inactive' : ''}`}
                >
                  <div className="record-card__main">
                    <div className="record-card__title-row">
                      <PacienteAvatar avatar={paciente.paciente_avatar} pacienteId={paciente.paciente_id} alt={paciente.paciente_nome} />
                      <h3>{paciente.paciente_nome}</h3>
                      <span className={`badge ${statusBadgeClass[paciente.paciente_status]}`}>
                        {paciente.paciente_status}
                      </span>
                    </div>
                    <div className="record-card__meta">
                      <span>
                        <Users size={14} />
                        Tutor: {paciente.pet_resp_nome || '—'}
                      </span>
                      <span>
                        <PawPrint size={14} />
                        Espécie: {paciente.paciente_especie || '—'}
                        {paciente.paciente_raca ? ` (${paciente.paciente_raca})` : ''}
                      </span>
                      <span>
                        <Calendar size={14} />
                        Nascimento: {paciente.paciente_nascimento ? formatDate(paciente.paciente_nascimento) : '—'}
                      </span>
                    </div>
                  </div>

                  <div className="record-card__actions">
                    <button
                      type="button"
                      className="icon-btn"
                      aria-label="Ver prontuário"
                      onClick={() => navigate(`/prontuarios/${paciente.paciente_id}`)}
                    >
                      <ClipboardList size={16} />
                    </button>
                    <button
                      type="button"
                      className="icon-btn"
                      aria-label="Editar paciente"
                      onClick={() => setModal({ mode: 'edit', paciente })}
                    >
                      <Pencil size={16} />
                    </button>
                    <button
                      type="button"
                      className="icon-btn icon-btn--danger"
                      aria-label="Excluir paciente"
                      onClick={() => void handleDelete(paciente)}
                    >
                      <Trash2 size={16} />
                    </button>
                  </div>
                </div>
              ))}
          </div>
        </div>

        <aside className="list-sidebar">
          <div className="side-card">
            <h3>
              <Clock size={16} />
              Adições Recentes
            </h3>
            {painel && painel.recentes.length === 0 && <p className="empty-state">Nenhum paciente cadastrado.</p>}
            {painel?.recentes.map((paciente) => (
              <div className="side-list-item" key={paciente.paciente_id}>
                <strong>{paciente.paciente_nome}</strong>
                <span>Cadastrado em {formatDate(paciente.created_at)}</span>
              </div>
            ))}
          </div>

          <div className="side-card">
            <h3>
              <Cake size={16} />
              Aniversariantes do Mês
            </h3>
            {painel && painel.aniversariantes.length === 0 && (
              <p className="empty-state">Nenhum aniversariante neste mês.</p>
            )}
            {painel?.aniversariantes.map((paciente) => (
              <div className="side-list-item" key={paciente.paciente_id}>
                <strong>{paciente.paciente_nome}</strong>
                <span>{paciente.paciente_nascimento ? formatDate(paciente.paciente_nascimento) : ''}</span>
              </div>
            ))}
          </div>
        </aside>
      </div>

      {modal && (
        <PacienteFormModal
          title={modalTitle}
          initialValues={modalInitialValues}
          especies={painel?.especies ?? []}
          onClose={() => setModal(null)}
          onSubmit={(values) =>
            modal.mode === 'edit' ? handleUpdate(modal.paciente.paciente_id, values) : handleCreate(values)
          }
        />
      )}
    </div>
  )
}
