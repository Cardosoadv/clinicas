import { useEffect, useState } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import { fetchDRE, fetchExtrato, fetchLivroCaixa } from './api'
import './relatorios.css'
import { defaultPeriodo, type DREData, type ExtratoData, type LivroCaixaData, type PeriodoFiltro } from './types'

type Tab = 'extrato' | 'dre' | 'livro-caixa'

const tabPaths: Record<Tab, string> = {
  extrato: '/relatorios/extrato',
  dre: '/relatorios/dre',
  'livro-caixa': '/relatorios/livro-caixa',
}

function tabFromPath(pathname: string): Tab {
  if (pathname === tabPaths.dre) return 'dre'
  if (pathname === tabPaths['livro-caixa']) return 'livro-caixa'
  return 'extrato'
}

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

export function RelatoriosPage() {
  const location = useLocation()
  const navigate = useNavigate()
  const [tab, setTabState] = useState<Tab>(() => tabFromPath(location.pathname))
  const [periodo, setPeriodo] = useState<PeriodoFiltro>(defaultPeriodo)

  useEffect(() => {
    setTabState(tabFromPath(location.pathname))
  }, [location.pathname])

  function setTab(next: Tab) {
    setTabState(next)
    navigate(tabPaths[next])
  }

  const [extrato, setExtrato] = useState<ExtratoData | null>(null)
  const [dre, setDre] = useState<DREData | null>(null)
  const [livroCaixa, setLivroCaixa] = useState<LivroCaixaData | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    setIsLoading(true)
    const request =
      tab === 'extrato'
        ? fetchExtrato(periodo).then((res) => setExtrato(res.data ?? null))
        : tab === 'dre'
          ? fetchDRE(periodo).then((res) => setDre(res.data ?? null))
          : fetchLivroCaixa(periodo).then((res) => setLivroCaixa(res.data ?? null))

    request.finally(() => setIsLoading(false))
  }, [tab, periodo])

  return (
    <div className="list-page">
      <div className="page-header">
        <div>
          <h2>Relatórios</h2>
          <p className="page-subtitle">Extrato, DRE e Livro Caixa do período</p>
        </div>
      </div>

      <div className="list-toolbar">
        <div className="tabs">
          <button type="button" className={`tab${tab === 'extrato' ? ' tab--active' : ''}`} onClick={() => setTab('extrato')}>
            Extrato
          </button>
          <button type="button" className={`tab${tab === 'dre' ? ' tab--active' : ''}`} onClick={() => setTab('dre')}>
            DRE
          </button>
          <button
            type="button"
            className={`tab${tab === 'livro-caixa' ? ' tab--active' : ''}`}
            onClick={() => setTab('livro-caixa')}
          >
            Livro Caixa
          </button>
        </div>

        <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
          <input
            type="date"
            value={periodo.inicio}
            onChange={(event) => setPeriodo((prev) => ({ ...prev, inicio: event.target.value }))}
          />
          <span>até</span>
          <input
            type="date"
            value={periodo.fim}
            onChange={(event) => setPeriodo((prev) => ({ ...prev, fim: event.target.value }))}
          />
        </div>
      </div>

      {isLoading && <p className="empty-state">Carregando...</p>}

      {!isLoading && tab === 'extrato' && extrato && (
        <>
          <div className="stat-pills">
            <div className="stat-pill stat-pill--success">
              Entradas: <strong>{formatCurrency(extrato.total_entradas)}</strong>
            </div>
            <div className="stat-pill">
              Saídas: <strong>{formatCurrency(extrato.total_saidas)}</strong>
            </div>
            <div className="stat-pill">
              Saldo do período: <strong>{formatCurrency(extrato.saldo_periodo)}</strong>
            </div>
          </div>

          <table className="relatorio-table">
            <thead>
              <tr>
                <th>Data</th>
                <th>Histórico</th>
                <th>Natureza</th>
                <th>Forma</th>
                <th>Status</th>
                <th style={{ textAlign: 'right' }}>Valor</th>
              </tr>
            </thead>
            <tbody>
              {extrato.lancamentos.map((item) => (
                <tr key={`${item.tipo}-${item.id}`}>
                  <td>{formatDate(item.data)}</td>
                  <td>{item.historico}</td>
                  <td>
                    <span className={`badge ${item.natureza === 'Entrada' ? 'badge--success' : 'badge--danger'}`}>
                      {item.natureza}
                    </span>
                  </td>
                  <td>{item.forma_pgto}</td>
                  <td>{item.status}</td>
                  <td style={{ textAlign: 'right' }}>{formatCurrency(item.valor)}</td>
                </tr>
              ))}
              {extrato.lancamentos.length === 0 && (
                <tr>
                  <td colSpan={6} className="empty-state">
                    Nenhum lançamento no período.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </>
      )}

      {!isLoading && tab === 'dre' && dre && (
        <>
          <div className="stat-pills">
            <div className="stat-pill stat-pill--success">
              Receita líquida: <strong>{formatCurrency(dre.receita_liquida)}</strong>
            </div>
            <div className="stat-pill">
              Despesas: <strong>{formatCurrency(dre.total_despesas)}</strong>
            </div>
            <div className="stat-pill">
              Resultado: <strong>{formatCurrency(dre.resultado_liquido)}</strong>
            </div>
            <div className="stat-pill">
              Margem: <strong>{dre.margem_liquida}%</strong>
            </div>
          </div>

          <div className="side-card">
            <table className="relatorio-table relatorio-table--dre">
              <tbody>
                <tr className="relatorio-table__section">
                  <td>Receita Bruta</td>
                  <td style={{ textAlign: 'right' }}>{formatCurrency(dre.receita_bruta)}</td>
                </tr>
                {dre.por_servico.map((item) => (
                  <tr key={item.servico}>
                    <td style={{ paddingLeft: 24 }}>
                      {item.servico} ({item.qtd})
                    </td>
                    <td style={{ textAlign: 'right' }}>{formatCurrency(item.total)}</td>
                  </tr>
                ))}
                <tr>
                  <td>(-) Descontos</td>
                  <td style={{ textAlign: 'right' }}>-{formatCurrency(dre.descontos)}</td>
                </tr>
                <tr className="relatorio-table__section">
                  <td>= Receita Líquida</td>
                  <td style={{ textAlign: 'right' }}>{formatCurrency(dre.receita_liquida)}</td>
                </tr>
                {dre.por_categoria.map((item) => (
                  <tr key={item.categoria}>
                    <td style={{ paddingLeft: 24 }}>
                      (-) {item.categoria} ({item.qtd})
                    </td>
                    <td style={{ textAlign: 'right' }}>-{formatCurrency(item.total)}</td>
                  </tr>
                ))}
                <tr className="relatorio-table__section relatorio-table__total">
                  <td>= Resultado Líquido</td>
                  <td style={{ textAlign: 'right' }}>{formatCurrency(dre.resultado_liquido)}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </>
      )}

      {!isLoading && tab === 'livro-caixa' && livroCaixa && (
        <>
          <div className="side-card" style={{ textAlign: 'center' }}>
            <span className="page-subtitle">Saldo Inicial</span>
            <h3 style={{ margin: '4px 0 0' }}>{formatCurrency(livroCaixa.saldo_inicial)}</h3>
          </div>

          <table className="relatorio-table">
            <thead>
              <tr>
                <th>Data</th>
                <th>Histórico</th>
                <th>Forma</th>
                <th style={{ textAlign: 'right' }}>Entrada</th>
                <th style={{ textAlign: 'right' }}>Saída</th>
                <th style={{ textAlign: 'right' }}>Saldo Acumulado</th>
              </tr>
            </thead>
            <tbody>
              {livroCaixa.movimentos.map((item, index) => (
                <tr key={index}>
                  <td>{formatDate(item.data)}</td>
                  <td>{item.historico}</td>
                  <td>{item.forma_pgto}</td>
                  <td style={{ textAlign: 'right' }}>{item.entrada > 0 ? formatCurrency(item.entrada) : '—'}</td>
                  <td style={{ textAlign: 'right' }}>{item.saida > 0 ? formatCurrency(item.saida) : '—'}</td>
                  <td style={{ textAlign: 'right', color: item.saldo_acumulado < 0 ? '#b8341f' : '#1a7a3d' }}>
                    {formatCurrency(item.saldo_acumulado)}
                  </td>
                </tr>
              ))}
              {livroCaixa.movimentos.length === 0 && (
                <tr>
                  <td colSpan={6} className="empty-state">
                    Nenhuma movimentação paga no período.
                  </td>
                </tr>
              )}
            </tbody>
          </table>

          <div className="side-card" style={{ textAlign: 'center' }}>
            <span className="page-subtitle">Saldo Final</span>
            <h3 style={{ margin: '4px 0 0' }}>{formatCurrency(livroCaixa.saldo_final)}</h3>
          </div>
        </>
      )}
    </div>
  )
}
