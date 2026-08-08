import { Navigate, Route, Routes } from 'react-router-dom'
import { ProtectedRoute } from './components/ProtectedRoute'
import { AuthProvider } from './features/auth/AuthContext'
import { LoginPage } from './features/auth/LoginPage'
import { AgendaPage } from './features/agenda/AgendaPage'
import { ClienteDetailPage } from './features/clientes/ClienteDetailPage'
import { ClientesListPage } from './features/clientes/ClientesListPage'
import { ClientesDuplicadosPage } from './features/clientes/ClientesDuplicadosPage'
import { ConfiguracoesPage } from './features/configuracoes/ConfiguracoesPage'
import { EquipeListPage } from './features/equipe/EquipeListPage'
import { EstoqueListPage } from './features/estoque/EstoqueListPage'
import { FaturamentoLayout } from './features/faturamento/FaturamentoLayout'
import { CobrancasSection } from './features/faturamento/sections/CobrancasSection'
import { DashboardSection as FaturamentoDashboardSection } from './features/faturamento/sections/DashboardSection'
import { DespesasSection } from './features/faturamento/sections/DespesasSection'
import { NotasSection } from './features/faturamento/sections/NotasSection'
import { LojasListPage } from './features/lojas/LojasListPage'
import { PacientesListPage } from './features/pacientes/PacientesListPage'
import { PacotesListPage } from './features/pacotes/PacotesListPage'
import { AnamneseSection } from './features/prontuarios/sections/AnamneseSection'
import { EvolucaoSection } from './features/prontuarios/sections/EvolucaoSection'
import { HistoricoSection } from './features/prontuarios/sections/HistoricoSection'
import { ImagensSection } from './features/prontuarios/sections/ImagensSection'
import { PesoSection } from './features/prontuarios/sections/PesoSection'
import { PrescricoesSection } from './features/prontuarios/sections/PrescricoesSection'
import { VacinasSection } from './features/prontuarios/sections/VacinasSection'
import { ProntuarioListPage } from './features/prontuarios/ProntuarioListPage'
import { ProntuarioLayout } from './features/prontuarios/ProntuarioLayout'
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
            <Route path="/clientes/duplicados" element={<ClientesDuplicadosPage />} />
            <Route path="/clientes/:id" element={<ClienteDetailPage />} />
            <Route path="/pacientes" element={<PacientesListPage />} />
            <Route path="/agenda" element={<AgendaPage />} />
            <Route path="/prontuarios" element={<ProntuarioListPage />} />
            <Route path="/prontuarios/:id" element={<ProntuarioLayout />}>
              <Route index element={<Navigate to="anamnese" replace />} />
              <Route path="anamnese" element={<AnamneseSection />} />
              <Route path="historico" element={<HistoricoSection />} />
              <Route path="vacinas" element={<VacinasSection />} />
              <Route path="peso" element={<PesoSection />} />
              <Route path="prescricoes" element={<PrescricoesSection />} />
              <Route path="evolucao" element={<EvolucaoSection />} />
              <Route path="imagens" element={<ImagensSection />} />
            </Route>
            <Route path="/servicos" element={<ServicosListPage />} />
            <Route path="/pacotes" element={<PacotesListPage />} />
            <Route path="/estoque" element={<EstoqueListPage />} />
            <Route path="/equipe" element={<EquipeListPage />} />
            <Route path="/lojas" element={<LojasListPage />} />
            <Route path="/faturamento" element={<FaturamentoLayout />}>
              <Route index element={<Navigate to="dashboard" replace />} />
              <Route path="dashboard" element={<FaturamentoDashboardSection />} />
              <Route path="cobrancas" element={<CobrancasSection />} />
              <Route path="despesas" element={<DespesasSection />} />
              <Route path="notas" element={<NotasSection />} />
            </Route>
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
