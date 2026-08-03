import { BookOpen, LayoutDashboard, Receipt, TrendingDown } from 'lucide-react'
import { useEffect, useState } from 'react'
import { NavLink, Outlet } from 'react-router-dom'
import { ApiError } from '../../lib/api'
import { fetchDashboard, fetchLancamentos, fetchNotas } from './api'
import { FaturamentoContext } from './FaturamentoContext'
import './faturamento.css'
import type { DashboardData, LancamentosData, Nota } from './types'

const submenuItems = [
  { to: 'dashboard', label: 'Visão Geral', icon: LayoutDashboard },
  { to: 'cobrancas', label: 'Cobranças', icon: Receipt },
  { to: 'despesas', label: 'Despesas', icon: TrendingDown },
  { to: 'notas', label: 'Notas', icon: BookOpen },
]

export function FaturamentoLayout() {
  const [dashboard, setDashboard] = useState<DashboardData | null>(null)
  const [lancamentos, setLancamentos] = useState<LancamentosData | null>(null)
  const [notas, setNotas] = useState<Nota[] | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  function reloadDashboard() {
    fetchDashboard()
      .then((res) => setDashboard(res.data ?? null))
      .catch((err) => setError(err instanceof ApiError ? err.message : 'Erro ao carregar faturamento.'))
  }

  function reloadLancamentos() {
    fetchLancamentos()
      .then((res) => setLancamentos(res.data ?? null))
      .catch(() => setLancamentos(null))
  }

  function reloadNotas() {
    fetchNotas()
      .then((res) => setNotas(res.data ?? []))
      .catch(() => setNotas([]))
  }

  useEffect(() => {
    setIsLoading(true)
    reloadDashboard()
    reloadLancamentos()
    reloadNotas()
    setIsLoading(false)
  }, [])

  return (
    <FaturamentoContext.Provider
      value={{ dashboard, lancamentos, notas, isLoading, error, reloadDashboard, reloadLancamentos, reloadNotas }}
    >
      <div className="list-page">
        <div className="page-header">
          <div>
            <h2>Faturamento</h2>
            <p className="page-subtitle">Cobranças, despesas e fluxo financeiro da clínica</p>
          </div>
        </div>

        <div className="faturamento-layout">
          <nav className="faturamento-submenu">
            {submenuItems.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                className={({ isActive }) => `faturamento-submenu__link${isActive ? ' faturamento-submenu__link--active' : ''}`}
              >
                <item.icon size={16} />
                {item.label}
              </NavLink>
            ))}
          </nav>

          <div className="faturamento-content">
            <Outlet />
          </div>
        </div>
      </div>
    </FaturamentoContext.Provider>
  )
}
