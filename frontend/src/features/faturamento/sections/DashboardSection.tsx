import { CreditCard, Receipt, TrendingDown, TrendingUp, Wallet } from 'lucide-react'
import { PacienteAvatar } from '../../../components/PacienteAvatar'
import { useFaturamento } from '../FaturamentoContext'
import type { Cobranca, Despesa } from '../types'

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

export function DashboardSection() {
  const { dashboard, error } = useFaturamento()

  if (error) return <p className="empty-state empty-state--error">{error}</p>
  if (!dashboard) return <p className="empty-state">Carregando...</p>

  const { kpis } = dashboard

  return (
    <>
      <div className="faturamento-stat-pills">
        <div className="faturamento-stat-pill">
          <TrendingUp size={24} />
          <div>
            <strong>{formatCurrency(kpis.receitas)}</strong>
            <span>Receitas do mês</span>
          </div>
        </div>
        <div className="faturamento-stat-pill faturamento-stat-pill--despesa">
          <TrendingDown size={24} />
          <div>
            <strong>{formatCurrency(kpis.despesas)}</strong>
            <span>Despesas do mês</span>
          </div>
        </div>
        <div className="faturamento-stat-pill faturamento-stat-pill--saldo">
          <Wallet size={24} />
          <div>
            <strong>{formatCurrency(kpis.saldo)}</strong>
            <span>Saldo do mês</span>
          </div>
        </div>
        <div className="faturamento-stat-pill faturamento-stat-pill--areceber">
          <CreditCard size={24} />
          <div>
            <strong>{formatCurrency(kpis.a_receber)}</strong>
            <span>A receber</span>
          </div>
        </div>
        <div className="faturamento-stat-pill faturamento-stat-pill--vencidos">
          <Receipt size={24} />
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
                    {formatDate(item.created_at)} · {formatCurrency(item.valor)} · {isDespesaItem ? 'Despesa' : 'Cobrança'}
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
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <PacienteAvatar avatar={pet.paciente_avatar} pacienteId={pet.paciente_id} />
                  <strong>{pet.paciente_nome}</strong>
                </div>
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
  )
}
