import { CreditCard, FileText, Paperclip, Plus, Receipt, TrendingDown, TrendingUp, Wallet } from 'lucide-react'
import { useEffect, useState } from 'react'
import { ApiError } from '../../lib/api'
import {
  comprovanteUrl,
  createCobranca,
  createDespesa,
  createNota,
  fetchDashboard,
  fetchLancamentos,
  fetchNotas,
  updateCobranca,
  updateDespesa,
} from './api'
import { CobrancaFormModal } from './CobrancaFormModal'
import { DespesaFormModal } from './DespesaFormModal'
import './faturamento.css'
import { NotaFormModal } from './NotaFormModal'
import {
  cobrancaToFormValues,
  despesaToFormValues,
  emptyCobrancaForm,
  emptyDespesaForm,
  type Cobranca,
  type CobrancaFormValues,
  type DashboardData,
  type Despesa,
  type DespesaFormValues,
  type LancamentosData,
  type Nota,
} from './types'

type Tab = 'dashboard' | 'cobrancas' | 'despesas' | 'notas'

type CobrancaModalState = { mode: 'create' } | { mode: 'edit'; cobranca: Cobranca } | null
type DespesaModalState = { mode: 'create' } | { mode: 'edit'; despesa: Despesa } | null

function formatCurrency(value: number): string {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(value: string): string {
  try {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(value))
  } catch {
    return value
  }
}

const statusBadgeClass: Record<string, string> = {
  Pendente: 'badge--warning',
  Pago: 'badge--success',
  Atrasado: 'badge--danger',
  Cancelado: 'badge--danger',
}

export function FaturamentoPage() {
  const [tab, setTab] = useState<Tab>('dashboard')

  const [dashboard, setDashboard] = useState<DashboardData | null>(null)
  const [lancamentos, setLancamentos] = useState<LancamentosData | null>(null)
  const [notas, setNotas] = useState<Nota[] | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const [cobrancaModal, setCobrancaModal] = useState<CobrancaModalState>(null)
  const [despesaModal, setDespesaModal] = useState<DespesaModalState>(null)
  const [showNotaModal, setShowNotaModal] = useState(false)

  function loadDashboard() {
    fetchDashboard()
      .then((res) => setDashboard(res.data ?? null))
      .catch((err) => setError(err instanceof ApiError ? err.message : 'Erro ao carregar faturamento.'))
  }

  function loadLancamentos() {
    fetchLancamentos()
      .then((res) => setLancamentos(res.data ?? null))
      .catch(() => setLancamentos(null))
  }

  function loadNotas() {
    fetchNotas()
      .then((res) => setNotas(res.data ?? []))
      .catch(() => setNotas([]))
  }

  useEffect(() => {
    setIsLoading(true)
    loadDashboard()
    loadLancamentos()
    loadNotas()
    setIsLoading(false)
  }, [])

  function refreshAll() {
    loadDashboard()
    loadLancamentos()
  }

  async function handleCreateCobranca(values: CobrancaFormValues) {
    await createCobranca(values)
    setCobrancaModal(null)
    refreshAll()
  }

  async function handleUpdateCobranca(id: number, values: CobrancaFormValues) {
    await updateCobranca(id, values)
    setCobrancaModal(null)
    refreshAll()
  }

  async function handleCreateDespesa(values: DespesaFormValues): Promise<number | void> {
    const result = await createDespesa(values)
    setDespesaModal(null)
    refreshAll()
    return typeof result.id === 'number' ? result.id : undefined
  }

  async function handleUpdateDespesa(id: number, values: DespesaFormValues) {
    await updateDespesa(id, values)
    setDespesaModal(null)
    refreshAll()
  }

  const kpis = dashboard?.kpis

  return (
    <div className="list-page">
      <div className="page-header">
        <div>
          <h2>Faturamento</h2>
          <p className="page-subtitle">Cobranças, despesas e fluxo financeiro da clínica</p>
        </div>
        {tab === 'cobrancas' && (
          <button type="button" className="btn btn--primary" onClick={() => setCobrancaModal({ mode: 'create' })}>
            <Plus size={16} /> Nova Cobrança
          </button>
        )}
        {tab === 'despesas' && (
          <button type="button" className="btn btn--primary" onClick={() => setDespesaModal({ mode: 'create' })}>
            <Plus size={16} /> Nova Despesa
          </button>
        )}
        {tab === 'notas' && (
          <button type="button" className="btn btn--primary" onClick={() => setShowNotaModal(true)}>
            <Plus size={16} /> Nova Nota
          </button>
        )}
      </div>

      <div className="tabs">
        <button type="button" className={`tab${tab === 'dashboard' ? ' tab--active' : ''}`} onClick={() => setTab('dashboard')}>
          Visão Geral
        </button>
        <button type="button" className={`tab${tab === 'cobrancas' ? ' tab--active' : ''}`} onClick={() => setTab('cobrancas')}>
          Cobranças
        </button>
        <button type="button" className={`tab${tab === 'despesas' ? ' tab--active' : ''}`} onClick={() => setTab('despesas')}>
          Despesas
        </button>
        <button type="button" className={`tab${tab === 'notas' ? ' tab--active' : ''}`} onClick={() => setTab('notas')}>
          Notas
        </button>
      </div>

      {isLoading && <p className="empty-state">Carregando...</p>}
      {!isLoading && error && <p className="empty-state empty-state--error">{error}</p>}

      {!isLoading && !error && tab === 'dashboard' && kpis && (
        <>
          <div className="stat-pills">
            <div className="agenda-stat-pill">
              <TrendingUp size={18} />
              <div>
                <strong>{formatCurrency(kpis.receitas)}</strong>
                <span>Receitas do mês</span>
              </div>
            </div>
            <div className="agenda-stat-pill">
              <TrendingDown size={18} />
              <div>
                <strong>{formatCurrency(kpis.despesas)}</strong>
                <span>Despesas do mês</span>
              </div>
            </div>
            <div className="agenda-stat-pill">
              <Wallet size={18} />
              <div>
                <strong>{formatCurrency(kpis.saldo)}</strong>
                <span>Saldo do mês</span>
              </div>
            </div>
            <div className="agenda-stat-pill">
              <CreditCard size={18} />
              <div>
                <strong>{formatCurrency(kpis.a_receber)}</strong>
                <span>A receber</span>
              </div>
            </div>
            <div className="agenda-stat-pill">
              <Receipt size={18} />
              <div>
                <strong>{formatCurrency(kpis.vencidos)}</strong>
                <span>Vencidos</span>
              </div>
            </div>
          </div>

          <div className="list-layout">
            <div className="list-main">
              <div className="side-card">
                <h3>Receita vs Despesa (últimos 7 meses)</h3>
                <div className="mini-chart">
                  {dashboard.stats.chart_data.points.map((point) => (
                    <div className="mini-chart__col" key={point.label}>
                      <div className="mini-chart__bars">
                        <div
                          className="mini-chart__bar mini-chart__bar--receita"
                          style={{ height: `${(point.receita / (dashboard.stats.chart_data.max_value || 1)) * 100}%` }}
                          title={formatCurrency(point.receita)}
                        />
                        <div
                          className="mini-chart__bar mini-chart__bar--despesa"
                          style={{ height: `${(point.despesa / (dashboard.stats.chart_data.max_value || 1)) * 100}%` }}
                          title={formatCurrency(point.despesa)}
                        />
                      </div>
                      <span>{point.label}</span>
                    </div>
                  ))}
                </div>
                <div className="mini-chart__legend">
                  <span>
                    <i className="mini-chart__legend-dot mini-chart__legend-dot--receita" /> Receita
                  </span>
                  <span>
                    <i className="mini-chart__legend-dot mini-chart__legend-dot--despesa" /> Despesa
                  </span>
                </div>
              </div>

              <div className="side-card">
                <h3>Últimas transações</h3>
                {dashboard.transacoes.length === 0 && <p className="empty-state">Nenhuma transação recente.</p>}
                {dashboard.transacoes.map((item, index) => {
                  const isDespesaItem = 'categoria' in item
                  return (
                    <div className="side-list-item" key={`${isDespesaItem ? 'd' : 'c'}-${index}`}>
                      <strong>{isDespesaItem ? (item as Despesa).descricao : (item as Cobranca).servico}</strong>
                      <span>
                        {formatDate(item.created_at)} · {formatCurrency(item.valor)} ·{' '}
                        {isDespesaItem ? 'Despesa' : 'Cobrança'}
                      </span>
                    </div>
                  )
                })}
              </div>
            </div>

            <aside className="list-sidebar">
              <div className="side-card">
                <h3>Top Pacientes</h3>
                {dashboard.stats.top_pets.length === 0 && <p className="empty-state">Sem dados no período.</p>}
                {dashboard.stats.top_pets.map((pet) => (
                  <div className="side-list-item" key={pet.paciente_nome}>
                    <strong>
                      {pet.paciente_avatar || '🐾'} {pet.paciente_nome}
                    </strong>
                    <span>
                      {pet.qtd} atendimento{pet.qtd === 1 ? '' : 's'} · {formatCurrency(pet.total)}
                    </span>
                  </div>
                ))}
              </div>

              <div className="side-card">
                <h3>Formas de Pagamento</h3>
                {dashboard.stats.formas_pgto.map((item) => {
                  const max = Math.max(...dashboard.stats.formas_pgto.map((f) => f.total), 1)
                  return (
                    <div className="bar-row" key={item.forma_pagamento}>
                      <span>{item.forma_pagamento}</span>
                      <div className="bar-row__track">
                        <div className="bar-row__fill" style={{ width: `${(item.total / max) * 100}%` }} />
                      </div>
                      <span>{formatCurrency(item.total)}</span>
                    </div>
                  )
                })}
              </div>
            </aside>
          </div>
        </>
      )}

      {!isLoading && !error && tab === 'cobrancas' && (
        <div className="record-list">
          {lancamentos?.cobrancas.length === 0 && <p className="empty-state">Nenhuma cobrança registrada.</p>}
          {lancamentos?.cobrancas.map((cobranca) => (
            <div
              key={cobranca.id}
              className="record-card"
              style={{ cursor: 'pointer' }}
              onClick={() => setCobrancaModal({ mode: 'edit', cobranca })}
            >
              <div className="record-card__main">
                <div className="record-card__title-row">
                  <h3>{cobranca.servico}</h3>
                  <span className={`badge ${statusBadgeClass[cobranca.status]}`}>{cobranca.status}</span>
                </div>
                <div className="record-card__meta">
                  <span>Paciente: {cobranca.paciente_nome ?? '—'}</span>
                  <span>Valor: {formatCurrency(cobranca.valor)}</span>
                  <span>Vencimento: {formatDate(cobranca.vencimento)}</span>
                  <span>{cobranca.forma_pagamento}</span>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {!isLoading && !error && tab === 'despesas' && (
        <div className="record-list">
          {lancamentos?.despesas.length === 0 && <p className="empty-state">Nenhuma despesa registrada.</p>}
          {lancamentos?.despesas.map((despesa) => (
            <div key={despesa.id} className="record-card">
              <div
                className="record-card__main"
                style={{ cursor: 'pointer' }}
                onClick={() => setDespesaModal({ mode: 'edit', despesa })}
              >
                <div className="record-card__title-row">
                  <h3>{despesa.descricao}</h3>
                  <span className={`badge ${statusBadgeClass[despesa.status]}`}>{despesa.status}</span>
                </div>
                <div className="record-card__meta">
                  <span>{despesa.categoria}</span>
                  <span>Valor: {formatCurrency(despesa.valor)}</span>
                  <span>Vencimento: {formatDate(despesa.data_vencimento)}</span>
                  {despesa.fornecedor && <span>Fornecedor: {despesa.fornecedor}</span>}
                </div>
              </div>
              {despesa.comprovante && (
                <div className="record-card__actions">
                  <a
                    className="icon-btn"
                    href={comprovanteUrl(despesa.id, despesa.comprovante.split('/').pop() ?? '')}
                    target="_blank"
                    rel="noreferrer"
                    aria-label="Ver comprovante"
                  >
                    <Paperclip size={16} />
                  </a>
                </div>
              )}
            </div>
          ))}
        </div>
      )}

      {!isLoading && !error && tab === 'notas' && (
        <div className="record-list">
          {notas?.length === 0 && <p className="empty-state">Nenhuma nota emitida ainda.</p>}
          {notas?.map((nota) => (
            <div key={nota.id} className="record-card">
              <div className="record-card__main">
                <div className="record-card__title-row">
                  <FileText size={16} />
                  <h3>
                    {nota.tipo}-{String(nota.id).padStart(3, '0')}
                  </h3>
                  <span className="badge badge--info">{nota.tipo}</span>
                </div>
                <div className="record-card__meta">
                  <span>{nota.descricao}</span>
                  <span>Valor: {formatCurrency(nota.valor)}</span>
                  <span>Emissão: {formatDate(nota.data_emissao)}</span>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {cobrancaModal && (
        <CobrancaFormModal
          title={cobrancaModal.mode === 'edit' ? 'Editar Cobrança' : 'Nova Cobrança'}
          initialValues={cobrancaModal.mode === 'edit' ? cobrancaToFormValues(cobrancaModal.cobranca) : emptyCobrancaForm()}
          initialPacienteNome={cobrancaModal.mode === 'edit' ? cobrancaModal.cobranca.paciente_nome : undefined}
          onClose={() => setCobrancaModal(null)}
          onSubmit={(values) =>
            cobrancaModal.mode === 'edit'
              ? handleUpdateCobranca(cobrancaModal.cobranca.id, values)
              : handleCreateCobranca(values)
          }
        />
      )}

      {despesaModal && (
        <DespesaFormModal
          title={despesaModal.mode === 'edit' ? 'Editar Despesa' : 'Nova Despesa'}
          initialValues={despesaModal.mode === 'edit' ? despesaToFormValues(despesaModal.despesa) : emptyDespesaForm()}
          onClose={() => setDespesaModal(null)}
          onSubmit={(values) =>
            despesaModal.mode === 'edit'
              ? handleUpdateDespesa(despesaModal.despesa.id, values)
              : handleCreateDespesa(values)
          }
        />
      )}

      {showNotaModal && (
        <NotaFormModal
          onClose={() => setShowNotaModal(false)}
          onSubmit={async (values) => {
            await createNota(values)
            setShowNotaModal(false)
            loadNotas()
          }}
        />
      )}
    </div>
  )
}
