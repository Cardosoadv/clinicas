import { Bell, LogOut, Mail, Menu } from 'lucide-react'
import { useEffect, useState } from 'react'
import { NavLink, Outlet, useLocation } from 'react-router-dom'
import fallbackLogo from '../assets/logo.png'
import { useAuth } from '../features/auth/AuthContext'
import { LojaPrincipalProvider, useLojaPrincipal, useLojaPrincipalLogoUrl } from '../features/lojas/LojaPrincipalContext'
import { useClickOutside } from '../hooks/useClickOutside'
import { useEscapeKey } from '../hooks/useEscapeKey'
import { allNavItems, navSections } from './navConfig'
import { TopbarSearch } from './TopbarSearch'
import './AdminLayout.css'

export function AdminLayout() {
  return (
    <LojaPrincipalProvider>
      <AdminLayoutContent />
    </LojaPrincipalProvider>
  )
}

function AdminLayoutContent() {
  const { user, logout } = useAuth()
  const { loja } = useLojaPrincipal()
  const logoUrl = useLojaPrincipalLogoUrl()
  const location = useLocation()

  useEffect(() => {
    document.title = loja?.nome || 'Clínicas'
  }, [loja])

  const [sidebarOpen, setSidebarOpen] = useState(false)
  const [userMenuOpen, setUserMenuOpen] = useState(false)
  const userMenuRef = useClickOutside<HTMLDivElement>(() => setUserMenuOpen(false))

  const currentItem = allNavItems.find((item) => item.path === location.pathname)

  // Fecha o menu mobile ao trocar de rota.
  useEffect(() => {
    setSidebarOpen(false)
  }, [location.pathname])

  useEscapeKey(() => {
    setSidebarOpen(false)
    setUserMenuOpen(false)
  })

  const initial = (user?.username ?? user?.email ?? '?').charAt(0).toUpperCase()

  return (
    <div className="admin-shell">
      <aside className={`admin-sidebar${sidebarOpen ? ' admin-sidebar--open' : ''}`}>
        <div className="admin-sidebar__brand">
          <img src={logoUrl ?? fallbackLogo} alt={loja?.nome ?? 'Logo da clínica'} className="admin-sidebar__brand-logo" />
        </div>

        <nav className="admin-sidebar__nav">
          {navSections.map((section) => (
            <div className="admin-nav-section" key={section.title}>
              <p className="admin-nav-section__title">{section.title}</p>
              {section.items.map((item) => (
                <NavLink
                  key={item.path}
                  to={item.path}
                  end={item.path === '/'}
                  className={({ isActive }) => `admin-nav-link${isActive ? ' admin-nav-link--active' : ''}`}
                >
                  <item.icon size={18} strokeWidth={1.75} />
                  <span>{item.label}</span>
                </NavLink>
              ))}
            </div>
          ))}
        </nav>
      </aside>

      {sidebarOpen && <div className="admin-sidebar-backdrop" onClick={() => setSidebarOpen(false)} />}

      <div className="admin-main">
        <header className="admin-topbar">
          <button
            type="button"
            className="admin-icon-btn admin-topbar__menu-btn"
            onClick={() => setSidebarOpen((v) => !v)}
            aria-label="Alternar menu"
          >
            <Menu size={20} />
          </button>

          <h1 className="admin-topbar__title">{currentItem?.label ?? ''}</h1>

          <TopbarSearch />

          <div className="admin-topbar__actions">
            <button type="button" className="admin-icon-btn" aria-label="Notificações">
              <Bell size={18} />
            </button>
            <button type="button" className="admin-icon-btn" aria-label="Mensagens">
              <Mail size={18} />
            </button>

            <div className="admin-user-menu" ref={userMenuRef}>
              <button
                type="button"
                className="admin-avatar"
                onClick={() => setUserMenuOpen((v) => !v)}
                aria-label="Menu do usuário"
                aria-expanded={userMenuOpen}
              >
                {initial}
              </button>

              {userMenuOpen && (
                <div className="admin-user-menu__dropdown">
                  <p className="admin-user-menu__email">{user?.email}</p>
                  <button type="button" onClick={() => void logout()}>
                    <LogOut size={16} />
                    Sair
                  </button>
                </div>
              )}
            </div>
          </div>
        </header>

        <main className="admin-content">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
