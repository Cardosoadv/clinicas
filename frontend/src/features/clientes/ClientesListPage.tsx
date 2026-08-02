import {
  Building2,
  CheckCircle2,
  Clock,
  Eye,
  FileText,
  Gift,
  Mail,
  Pencil,
  Phone,
  Plus,
  Trash2,
  Users,
} from 'lucide-react'
import { useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { ApiError } from '../../lib/api'
import { createCliente, deleteCliente, fetchClientes, fetchClientesPainel, updateCliente } from './api'
import { ClienteFormModal } from './ClienteFormModal'
import {
  clienteToFormValues,
  emptyClienteForm,
  type Cliente,
  type ClienteFormValues,
  type ClienteStatusFiltro,
  type ClienteTipoFiltro,
  type ClientesPainel,
} from './types'

type ModalState = { mode: 'create' } | { mode: 'edit'; cliente: Cliente } | null

const statusTabs: { value: ClienteStatusFiltro; label: string }[] = [
  { value: 'ativos', label: 'Ativos' },
  { value: 'inativos', label: 'Inativos' },
  { value: 'todos', label: 'Todos' },
]

function formatDate(value: string): string {
  try {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(value))
  } catch {
    return value
  }
}

export function ClientesListPage() {
  const navigate = useNavigate()
  const [searchParams, setSearchParams] = useSearchParams()

  const [status, setStatus] = useState<ClienteStatusFiltro>(
    (searchParams.get('status') as ClienteStatusFiltro | null) ?? 'ativos',
  )
  const [tipo, setTipo] = useState<ClienteTipoFiltro>('')
  const [search, setSearch] = useState(searchParams.get('search') ?? '')

  const [clientes, setClientes] = useState<Cliente[] | null>(null)
  const [painel, setPainel] = useState<ClientesPainel | null>(null)
  const [isLoadingList, setIsLoadingList] = useState(true)
  const [listError, setListError] = useState<string | null>(null)

  const [modal, setModal] = useState<ModalState>(null)

  // Distingue mudanças de URL feitas por nós (debounce abaixo) de navegações
  // externas (ex.: busca da topbar chegando com ?search=...&status=...).
  const lastPushed = useRef<{ search: string; status: ClienteStatusFiltro } | null>(null)

  useEffect(() => {
    const urlSearch = searchParams.get('search') ?? ''
    const urlStatus = (searchParams.get('status') as ClienteStatusFiltro | null) ?? 'ativos'

    if (lastPushed.current?.search === urlSearch && lastPushed.current?.status === urlStatus) {
      return
    }

    setSearch(urlSearch)
    setStatus(urlStatus)
  }, [searchParams])

  useEffect(() => {
    fetchClientesPainel()
      .then((res) => setPainel(res.data ?? null))
      .catch(() => setPainel(null))
  }, [])

  useEffect(() => {
    const timeout = setTimeout(() => {
      setIsLoadingList(true)
      setListError(null)

      fetchClientes(status, tipo, search)
        .then((res) => setClientes(res.data ?? []))
        .catch((err) => setListError(err instanceof ApiError ? err.message : 'Erro ao carregar clientes.'))
        .finally(() => setIsLoadingList(false))

      lastPushed.current = { search, status }
      const next = new URLSearchParams()
      if (search.trim()) next.set('search', search.trim())
      if (status !== 'ativos') next.set('status', status)
      setSearchParams(next, { replace: true })
    }, 300)

    return () => clearTimeout(timeout)
  }, [status, tipo, search])

  const stats = painel?.stats

  function refreshAfterMutation() {
    fetchClientesPainel()
      .then((res) => setPainel(res.data ?? null))
      .catch(() => undefined)

    fetchClientes(status, tipo, search)
      .then((res) => setClientes(res.data ?? []))
      .catch(() => undefined)
  }

  async function handleCreate(values: ClienteFormValues) {
    await createCliente(values)
    setModal(null)
    refreshAfterMutation()
  }

  async function handleUpdate(id: number, values: ClienteFormValues) {
    await updateCliente(id, values)
    setModal(null)
    refreshAfterMutation()
  }

  async function handleDelete(cliente: Cliente) {
    if (!window.confirm(`Excluir o cliente "${cliente.nome}"?`)) {
      return
    }
    await deleteCliente(cliente.id)
    refreshAfterMutation()
  }

  const modalTitle = useMemo(() => (modal?.mode === 'edit' ? 'Editar Cliente' : 'Novo Cliente'), [modal])
  const modalInitialValues = modal?.mode === 'edit' ? clienteToFormValues(modal.cliente) : emptyClienteForm

  return (
    <div className="list-page">
      <div className="page-header">
        <div>
          <h2>Clientes &amp; CRM</h2>
          <p className="page-subtitle">Gerenciamento da carteira de clientes da clínica</p>
        </div>
        <button type="button" className="btn btn--primary" onClick={() => setModal({ mode: 'create' })}>
          <Plus size={16} />
          Novo Cliente
        </button>
      </div>

      <div className="stat-pills">
        <div className="stat-pill">
          <Users size={16} />
          Total: <strong>{stats?.total ?? '—'}</strong>
        </div>
        <div className="stat-pill stat-pill--success">
          <CheckCircle2 size={16} />
          Ativos: <strong>{stats?.ativos ?? '—'}</strong>
        </div>
        <div className="stat-pill">
          <Users size={16} />
          Pessoa Física: <strong>{stats?.pessoa_fisica ?? '—'}</strong>
        </div>
        <div className="stat-pill">
          <Building2 size={16} />
          Pessoa Jurídica: <strong>{stats?.pessoa_juridica ?? '—'}</strong>
        </div>
      </div>

      <div className="list-layout">
        <div className="list-main">
          <div className="list-toolbar">
            <div className="tabs">
              {statusTabs.map((tab) => (
                <button
                  key={tab.value}
                  type="button"
                  className={`tab${status === tab.value ? ' tab--active' : ''}`}
                  onClick={() => setStatus(tab.value)}
                >
                  {tab.label}
                </button>
              ))}
            </div>

            <select
              className="filter-select"
              value={tipo}
              onChange={(event) => setTipo(event.target.value as ClienteTipoFiltro)}
            >
              <option value="">Todos os Tipos</option>
              <option value="fisica">Pessoa Física</option>
              <option value="juridica">Pessoa Jurídica</option>
            </select>
          </div>

          <div className="record-list">
            {isLoadingList && <p className="empty-state">Carregando...</p>}

            {!isLoadingList && listError && <p className="empty-state empty-state--error">{listError}</p>}

            {!isLoadingList && !listError && clientes?.length === 0 && (
              <p className="empty-state">Nenhum cliente encontrado.</p>
            )}

            {!isLoadingList &&
              !listError &&
              clientes?.map((cliente) => {
                const isInactive = cliente.deleted_at !== null
                const isJuridica = Boolean(cliente.cnpj)

                return (
                  <div key={cliente.id} className={`record-card${isInactive ? ' record-card--inactive' : ''}`}>
                    <div className="record-card__main">
                      <div className="record-card__title-row">
                        <h3 className="record-card__title--link" onClick={() => navigate(`/clientes/${cliente.id}`)}>
                          {cliente.nome}
                        </h3>
                        <span className={`badge ${isInactive ? 'badge--danger' : 'badge--success'}`}>
                          {isInactive ? 'Inativo' : 'Ativo'}
                        </span>
                      </div>
                      <div className="record-card__meta">
                        <span>
                          <FileText size={14} />
                          Documento: {cliente.cnpj || cliente.cpf || '—'}
                        </span>
                        <span>
                          <Mail size={14} />
                          E-mail: {cliente.emails || '—'}
                        </span>
                        <span>
                          <Phone size={14} />
                          Telefone: {cliente.telefones || '—'}
                        </span>
                        <span>
                          <Users size={14} />
                          Tipo: {isJuridica ? 'Pessoa Jurídica' : 'Pessoa Física'}
                        </span>
                      </div>
                    </div>

                    <div className="record-card__actions">
                      <button
                        type="button"
                        className="icon-btn"
                        aria-label="Ver detalhes do cliente"
                        onClick={() => navigate(`/clientes/${cliente.id}`)}
                      >
                        <Eye size={16} />
                      </button>
                      <button
                        type="button"
                        className="icon-btn"
                        aria-label="Editar cliente"
                        onClick={() => setModal({ mode: 'edit', cliente })}
                      >
                        <Pencil size={16} />
                      </button>
                      <button
                        type="button"
                        className="icon-btn icon-btn--danger"
                        aria-label="Excluir cliente"
                        onClick={() => void handleDelete(cliente)}
                      >
                        <Trash2 size={16} />
                      </button>
                    </div>
                  </div>
                )
              })}
          </div>
        </div>

        <aside className="list-sidebar">
          <div className="side-card">
            <h3>
              <Clock size={16} />
              Adições Recentes
            </h3>
            {painel && painel.recentes.length === 0 && <p className="empty-state">Nenhum cliente cadastrado.</p>}
            {painel?.recentes.map((cliente) => (
              <div className="side-list-item" key={cliente.id}>
                <strong>{cliente.nome}</strong>
                <span>Cadastrado em {formatDate(cliente.created_at)}</span>
              </div>
            ))}
          </div>

          <div className="side-card">
            <h3>
              <Gift size={16} />
              Aniversariantes do Mês
            </h3>
            {painel && painel.aniversariantes.length === 0 && (
              <p className="empty-state">Nenhum aniversariante neste mês.</p>
            )}
            {painel?.aniversariantes.map((cliente) => (
              <div className="side-list-item" key={cliente.id}>
                <strong>{cliente.nome}</strong>
                <span>{cliente.nascimento ? formatDate(cliente.nascimento) : ''}</span>
              </div>
            ))}
          </div>
        </aside>
      </div>

      {modal && (
        <ClienteFormModal
          title={modalTitle}
          initialValues={modalInitialValues}
          onClose={() => setModal(null)}
          onSubmit={(values) => (modal.mode === 'edit' ? handleUpdate(modal.cliente.id, values) : handleCreate(values))}
        />
      )}
    </div>
  )
}
