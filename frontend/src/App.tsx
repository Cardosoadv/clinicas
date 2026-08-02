import { Navigate, Route, Routes } from 'react-router-dom'
import { ProtectedRoute } from './components/ProtectedRoute'
import { AuthProvider } from './features/auth/AuthContext'
import { LoginPage } from './features/auth/LoginPage'
import { AgendaPage } from './features/agenda/AgendaPage'
import { ClienteDetailPage } from './features/clientes/ClienteDetailPage'
import { ClientesListPage } from './features/clientes/ClientesListPage'
import { ConfiguracoesPage } from './features/configuracoes/ConfiguracoesPage'
import { EquipeListPage } from './features/equipe/EquipeListPage'
import { EstoqueListPage } from './features/estoque/EstoqueListPage'
import { FaturamentoPage } from './features/faturamento/FaturamentoPage'
import { LojasListPage } from './features/lojas/LojasListPage'
import { PacientesListPage } from './features/pacientes/PacientesListPage'
import { PacotesListPage } from './features/pacotes/PacotesListPage'
import { RelatoriosPage } from './features/relatorios/RelatoriosPage'
import { ServicosListPage } from './features/servicos/ServicosListPage'
import { AdminLayout } from './layouts/AdminLayout'
import { DashboardPage } from './pages/DashboardPage'
import './App.css'

function App() {
  return (
    <AuthProvider>
      <Routes>
        <Route path="/login" element={<LoginPage />} />

        <Route element={<ProtectedRoute />}>
          <Route element={<AdminLayout />}>
            <Route path="/" element={<DashboardPage />} />
            <Route path="/clientes" element={<ClientesListPage />} />
            <Route path="/clientes/:id" element={<ClienteDetailPage />} />
            <Route path="/pacientes" element={<PacientesListPage />} />
            <Route path="/agenda" element={<AgendaPage />} />
            <Route path="/servicos" element={<ServicosListPage />} />
            <Route path="/pacotes" element={<PacotesListPage />} />
            <Route path="/estoque" element={<EstoqueListPage />} />
            <Route path="/equipe" element={<EquipeListPage />} />
            <Route path="/lojas" element={<LojasListPage />} />
            <Route path="/faturamento" element={<FaturamentoPage />} />
            <Route path="/relatorios/extrato" element={<RelatoriosPage />} />
            <Route path="/relatorios/dre" element={<RelatoriosPage />} />
            <Route path="/relatorios/livro-caixa" element={<RelatoriosPage />} />
            <Route path="/configuracoes" element={<ConfiguracoesPage />} />
          </Route>
        </Route>

        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </AuthProvider>
  )
}

export default App
