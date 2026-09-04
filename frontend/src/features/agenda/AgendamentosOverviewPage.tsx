import { ArrowLeft, Plus, Search } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { ApiError } from '../../lib/api'
import {
  faturarAgendamento,
  fetchAgendamentos,
  fetchEquipeOptions,
  fetchServicosOptions,
  updateAgendamento,
  updateAgendamentoStatus,
} from './api'
import { AgendamentoFormModal } from './AgendamentoFormModal'
import { AgendamentoOverviewCard, findVeterinarioNome } from './AgendamentoOverviewCard'
import { FaturarModal } from './FaturarModal'
import { statusLabels } from './statusMeta'
import './agenda.css'
import {
  agendamentoToFormValues,
  type Agendamento,
  type AgendamentoFormValues,
  type AgendamentoStatus,
  type EquipeOption,
  type FaturarFormValues,
  type ServicoOption,
} from './types'

type ModalState = { mode: 'edit'; agendamento: Agendamento } | { mode: 'faturar'; agendamento: Agendamento } | null

const statusTabs: { value: AgendamentoStatus | ''; label: string }[] = [
  { value: '', label: 'Todos' },
  { value: 'pendente', label: statusLabels.pendente },
  { value: 'confirmado', label: statusLabels.confirmado },
  { value: 'concluido', label: statusLabels.concluido },
  { value: 'cancelado', label: statusLabels.cancelado },
]

export function AgendamentosOverviewPage() {
  const [searchParams, setSearchParams] = useSearchParams()

  const [status, setStatus] = useState<AgendamentoStatus | ''>(
    (searchParams.get('status') as AgendamentoStatus | null) ?? '',
  )
  const [dataInicio, setDataInicio] = useState(searchParams.get('data_inicio') ?? '')
  const [dataFim, setDataFim] = useState(searchParams.get('data_fim') ?? '')
  const [servicoId, setServicoId] = useState(searchParams.get('servico_id') ?? '')
  const [veterinarioId, setVeterinarioId] = useState(searchParams.get('veterinario_id') ?? '')
  const [search, setSearch] = useState(searchParams.get('search') ?? '')

  const [agendamentos, setAgendamentos] = useState<Agendamento[] | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const [servicosOptions, setServicosOptions] = useState<ServicoOption[]>([])
  const [equipeOptions, setEquipeOptions] = useState<EquipeOption[]>([])

  const [modal, setModal] = useState<ModalState>(null)

  const lastPushed = useRef<string | null>(null)

  useEffect(() => {
    fetchServicosOptions()
      .then((res) => setServicosOptions(res.data ?? []))
      .catch(() => setServicosOptions([]))
    fetchEquipeOptions()
      .then((res) => setEquipeOptions(res.data ?? []))
      .catch(() => setEquipeOptions([]))
  }, [])

  function load() {
    setIsLoading(true)
    setError(null)
    fetchAgendamentos({ status, data_inicio: dataInicio, data_fim: dataFim, servico_id: servicoId, veterinario_id: veterinarioId, search })
      .then((res) => setAgendamentos(res.data ?? []))
      .catch((err) => setError(err instanceof ApiError ? err.message : 'Erro ao carregar agendamentos.'))
      .finally(() => setIsLoading(false))
  }

  useEffect(() => {
    const timeout = setTimeout(() => {
      load()

      const next = new URLSearchParams()
      if (status) next.set('status', status)
      if (dataInicio) next.set('data_inicio', dataInicio)
      if (dataFim) next.set('data_fim', dataFim)
      if (servicoId) next.set('servico_id', servicoId)
      if (veterinarioId) next.set('veterinario_id', veterinarioId)
      if (search.trim()) next.set('search', search.trim())

      const nextStr = next.toString()
      if (lastPushed.current !== nextStr) {
        lastPushed.current = nextStr
        setSearchParams(next, { replace: true })
      }
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, 300)

    return () => clearTimeout(timeout)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [status, dataInicio, dataFim, servicoId, veterinarioId, search])

  async function handleStatusChange(agendamento: Agendamento, newStatus: AgendamentoStatus) {
    const previous = agendamentos
    setAgendamentos((prev) =>
      (prev ?? []).map((item) => (item.age_id === agendamento.age_id ? { ...item, age_status: newStatus } : item)),
    )
    try {
      await updateAgendamentoStatus(agendamento.age_id, newStatus)
    } catch {
      setAgendamentos(previous)
    }
  }

  async function handleUpdate(id: number, values: AgendamentoFormValues) {
    await updateAgendamento(id, values)
    setModal(null)
    load()
  }

  async function handleFaturar(agendamento: Agendamento, values: FaturarFormValues) {
    await faturarAgendamento(agendamento.age_id, values)
    setModal(null)
    load()
  }

  function clearFilters() {
    setStatus('')
    setDataInicio('')
    setDataFim('')
    setServicoId('')
    setVeterinarioId('')
    setSearch('')
  }

  const hasFilters = Boolean(status || dataInicio || dataFim || servicoId || veterinarioId || search)

  return (
    <div className="agenda-page">
      <div className="page-header">
        <div>
          <Link to="/agenda" className="agenda-back-link">
            <ArrowLeft size={14} /> Voltar à agenda
          </Link>
          <h2>Todos os Agendamentos</h2>
          <p className="page-subtitle">Visão geral com filtros por status, período, serviço e profissional</p>
        </div>
        <Link to="/agenda" className="btn btn--primary">
          <Plus size={16} />
          Novo Agendamento
        </Link>
      </div>

      <div className="agendamentos-toolbar">
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

        <div className="agendamentos-toolbar__filters">
          <label className="agendamentos-toolbar__date-field">
            De
            <input type="date" value={dataInicio} onChange={(event) => setDataInicio(event.target.value)} />
          </label>
          <label className="agendamentos-toolbar__date-field">
            Até
            <input type="date" value={dataFim} onChange={(event) => setDataFim(event.target.value)} />
          </label>

          <select className="filter-select" value={servicoId} onChange={(event) => setServicoId(event.target.value)}>
            <option value="">Todos os Serviços</option>
            {servicosOptions.map((servico) => (
              <option key={servico.ser_id} value={servico.ser_id}>
                {servico.ser_nome}
              </option>
            ))}
          </select>

          <select className="filter-select" value={veterinarioId} onChange={(event) => setVeterinarioId(event.target.value)}>
            <option value="">Todos os Profissionais</option>
            {equipeOptions.map((membro) => (
              <option key={membro.equ_id} value={membro.equ_id}>
                {membro.equ_nome}
              </option>
            ))}
          </select>

          <div className="agendamentos-toolbar__search">
            <Search size={14} />
            <input
              type="text"
              placeholder="Buscar por paciente, tutor ou serviço..."
              value={search}
              onChange={(event) => setSearch(event.target.value)}
            />
          </div>

          {hasFilters && (
            <button type="button" className="btn btn--ghost" onClick={clearFilters}>
              Limpar filtros
            </button>
          )}
        </div>
      </div>

      {isLoading && <p className="empty-state">Carregando...</p>}
      {!isLoading && error && <p className="empty-state empty-state--error">{error}</p>}
      {!isLoading && !error && agendamentos?.length === 0 && (
        <p className="empty-state">Nenhum agendamento encontrado para os filtros selecionados.</p>
      )}

      {!isLoading && !error && agendamentos && agendamentos.length > 0 && (
        <div className="agendamentos-grid">
          {agendamentos.map((agendamento) => (
            <AgendamentoOverviewCard
              key={agendamento.age_id}
              agendamento={agendamento}
              veterinarioNome={findVeterinarioNome(equipeOptions, agendamento.age_veterinario)}
              onClick={() => setModal({ mode: 'edit', agendamento })}
              onStatusChange={(newStatus) => handleStatusChange(agendamento, newStatus)}
              onFaturar={() => setModal({ mode: 'faturar', agendamento })}
            />
          ))}
        </div>
      )}

      {modal && modal.mode === 'edit' && (
        <AgendamentoFormModal
          title="Editar Agendamento"
          initialValues={agendamentoToFormValues(modal.agendamento)}
          initialPacienteNome={modal.agendamento.paciente_nome}
          initialPacienteEndereco={modal.agendamento.paciente_endereco}
          onClose={() => setModal(null)}
          onSubmit={(values) => handleUpdate(modal.agendamento.age_id, values)}
        />
      )}

      {modal && modal.mode === 'faturar' && (
        <FaturarModal
          agendamento={modal.agendamento}
          servicosOptions={servicosOptions}
          onClose={() => setModal(null)}
          onSubmit={(values) => handleFaturar(modal.agendamento, values)}
        />
      )}
    </div>
  )
}
