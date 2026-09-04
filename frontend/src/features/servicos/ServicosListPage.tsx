import { CheckCircle2, Clock, DollarSign, Package, Pencil, Plus, Stethoscope, Trash2, X, XCircle } from 'lucide-react'
import { useEffect, useMemo, useRef, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { ApiError } from '../../lib/api'
import { createServico, deleteServico, fetchServicos, updateServico } from './api'
import { BomModal } from './BomModal'
import { ServicoFormModal } from './ServicoFormModal'
import './servicos.css'
import { emptyServicoForm, servicoToFormValues, type Servico, type ServicoFormValues } from './types'

type ModalState = { mode: 'create' } | { mode: 'edit'; servico: Servico } | { mode: 'bom'; servico: Servico } | null

const statusTabs = [
  { value: '', label: 'Todos' },
  { value: 'Ativo', label: 'Ativos' },
  { value: 'Inativo', label: 'Inativos' },
]

function formatCurrency(value: number): string {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

export function ServicosListPage() {
  const [searchParams, setSearchParams] = useSearchParams()

  const [search, setSearch] = useState(searchParams.get('search') ?? '')
  const [status, setStatus] = useState(searchParams.get('status') ?? '')

  const [servicos, setServicos] = useState<Servico[] | null>(null)
  const [allServicos, setAllServicos] = useState<Servico[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [modal, setModal] = useState<ModalState>(null)

  const lastPushed = useRef<{ search: string; status: string } | null>(null)

  // Sincroniza estado com parâmetros da URL (caso navegue de fora com ?search=...)
  useEffect(() => {
    const urlSearch = searchParams.get('search') ?? ''
    const urlStatus = searchParams.get('status') ?? ''

    if (lastPushed.current?.search === urlSearch && lastPushed.current?.status === urlStatus) {
      return
    }

    setSearch(urlSearch)
    setStatus(urlStatus)
  }, [searchParams])

  // Carrega lista geral para alimentar estatísticas
  function loadStats() {
    fetchServicos('', '')
      .then((res) => setAllServicos(res.data ?? []))
      .catch(() => undefined)
  }

  useEffect(() => {
    loadStats()
  }, [])

  // Carrega serviços filtrados com debounce
  useEffect(() => {
    const timeout = setTimeout(() => {
      setIsLoading(true)
      setError(null)

      fetchServicos(search, status)
        .then((res) => setServicos(res.data ?? []))
        .catch((err) => setError(err instanceof ApiError ? err.message : 'Erro ao carregar serviços.'))
        .finally(() => setIsLoading(false))

      lastPushed.current = { search, status }
      const next = new URLSearchParams()
      if (search.trim()) next.set('search', search.trim())
      if (status) next.set('status', status)
      setSearchParams(next, { replace: true })
    }, 250)

    return () => clearTimeout(timeout)
  }, [search, status, setSearchParams])

  function refresh() {
    loadStats()
    setIsLoading(true)
    setError(null)
    fetchServicos(search, status)
      .then((res) => setServicos(res.data ?? []))
      .catch((err) => setError(err instanceof ApiError ? err.message : 'Erro ao carregar serviços.'))
      .finally(() => setIsLoading(false))
  }

  async function handleCreate(values: ServicoFormValues) {
    await createServico(values)
    setModal(null)
    refresh()
  }

  async function handleUpdate(id: number, values: ServicoFormValues) {
    await updateServico(id, values)
    setModal(null)
    refresh()
  }

  async function handleDelete(servico: Servico) {
    if (!window.confirm(`Excluir o serviço "${servico.ser_nome}"?`)) return
    await deleteServico(servico.ser_id)
    refresh()
  }

  function clearFilters() {
    setSearch('')
    setStatus('')
  }

  const modalTitle = useMemo(() => (modal?.mode === 'edit' ? 'Editar Serviço' : 'Novo Serviço'), [modal])
  const modalInitialValues = modal?.mode === 'edit' ? servicoToFormValues(modal.servico) : emptyServicoForm

  const stats = useMemo(() => {
    const total = allServicos.length
    const ativos = allServicos.filter((s) => s.ser_status === 'Ativo').length
    const inativos = total - ativos
    return { total, ativos, inativos }
  }, [allServicos])

  const hasFilters = Boolean(search.trim() || status)

  return (
    <div className="list-page">
      <div className="page-header">
        <div>
          <h2>Serviços</h2>
          <p className="page-subtitle">Procedimentos e serviços oferecidos pela clínica</p>
        </div>
        <button type="button" className="btn btn--primary" onClick={() => setModal({ mode: 'create' })}>
          <Plus size={16} />
          Novo Serviço
        </button>
      </div>

      <div className="stat-pills">
        <div className="stat-pill">
          <Stethoscope size={16} />
          Total: <strong>{stats.total}</strong>
        </div>
        <div className="stat-pill stat-pill--success">
          <CheckCircle2 size={16} />
          Ativos: <strong>{stats.ativos}</strong>
        </div>
        <div className="stat-pill">
          <XCircle size={16} />
          Inativos: <strong>{stats.inativos}</strong>
        </div>
      </div>

      <div className="servicos-toolbar">
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

        {search.trim() && (
          <div className="servico-filter-tag">
            <span>Buscando por: <strong>“{search}”</strong></span>
            <button
              type="button"
              onClick={() => {
                setSearch('')
                const next = new URLSearchParams(searchParams)
                next.delete('search')
                setSearchParams(next, { replace: true })
              }}
              aria-label="Limpar busca"
              title="Limpar busca"
            >
              <X size={14} />
            </button>
          </div>
        )}
      </div>

      <div className="record-list">
        {isLoading && <p className="empty-state">Carregando...</p>}
        {!isLoading && error && <p className="empty-state empty-state--error">{error}</p>}
        {!isLoading && !error && servicos?.length === 0 && (
          <div className="empty-state">
            <p>
              {hasFilters
                ? 'Nenhum serviço encontrado para os filtros selecionados.'
                : 'Nenhum serviço cadastrado.'}
            </p>
            {hasFilters && (
              <button
                type="button"
                className="btn btn--secondary"
                style={{ marginTop: 12 }}
                onClick={clearFilters}
              >
                Limpar filtros
              </button>
            )}
          </div>
        )}

        {!isLoading &&
          !error &&
          servicos?.map((servico) => (
            <div
              key={servico.ser_id}
              className={`record-card${servico.ser_status !== 'Ativo' ? ' record-card--inactive' : ''}`}
            >
              <div className="record-card__main">
                <div className="record-card__title-row">
                  <span className="servico-icon">{servico.ser_icone || '🐾'}</span>
                  <h3>{servico.ser_nome}</h3>
                  <span className={`badge ${servico.ser_status === 'Ativo' ? 'badge--success' : 'badge--danger'}`}>
                    {servico.ser_status}
                  </span>
                </div>
                <div className="record-card__meta">
                  <span>
                    <DollarSign size={14} /> {formatCurrency(servico.ser_valor)}
                  </span>
                  {servico.ser_tempo_estimado && (
                    <span>
                      <Clock size={14} /> {servico.ser_tempo_estimado} min
                    </span>
                  )}
                  {servico.ser_descricao && <span>{servico.ser_descricao}</span>}
                </div>
              </div>

              <div className="record-card__actions">
                <button
                  type="button"
                  className="icon-btn"
                  aria-label="Produtos vinculados"
                  title="Produtos vinculados (BOM)"
                  onClick={() => setModal({ mode: 'bom', servico })}
                >
                  <Package size={16} />
                </button>
                <button
                  type="button"
                  className="icon-btn"
                  aria-label="Editar serviço"
                  onClick={() => setModal({ mode: 'edit', servico })}
                >
                  <Pencil size={16} />
                </button>
                <button
                  type="button"
                  className="icon-btn icon-btn--danger"
                  aria-label="Excluir serviço"
                  onClick={() => void handleDelete(servico)}
                >
                  <Trash2 size={16} />
                </button>
              </div>
            </div>
          ))}
      </div>

      {modal && (modal.mode === 'create' || modal.mode === 'edit') && (
        <ServicoFormModal
          title={modalTitle}
          initialValues={modalInitialValues}
          onClose={() => setModal(null)}
          onSubmit={(values) =>
            modal.mode === 'edit' ? handleUpdate(modal.servico.ser_id, values) : handleCreate(values)
          }
        />
      )}

      {modal?.mode === 'bom' && (
        <BomModal servico={modal.servico} onClose={() => setModal(null)} onSaved={() => setModal(null)} />
      )}
    </div>
  )
}
