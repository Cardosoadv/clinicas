import { Boxes, Cake, CalendarDays, Clock, PawPrint, Stethoscope, Syringe, UserPlus, Wallet } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { PacienteAvatar } from '../components/PacienteAvatar'
import { useAuth } from '../features/auth/AuthContext'
import { ApiError } from '../lib/api'
import { fetchHomeDashboard } from './dashboard.api'
import './dashboard.css'
import type { HomeDashboardData } from './dashboard.types'

function formatCurrency(value: number): string {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDateLong(date: Date): string {
  const text = new Intl.DateTimeFormat('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' }).format(date)
  return text.charAt(0).toUpperCase() + text.slice(1)
}

function formatShortDate(value: string): string {
  try {
    return new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: '2-digit' }).format(new Date(`${value}T00:00:00`))
  } catch {
    return value
  }
}

function formatDayMonth(value: string | null): string {
  if (!value) return '—'
  try {
    return new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: 'long' }).format(new Date(`${value}T00:00:00`))
  } catch {
    return value
  }
}

function formatTime(value: string): string {
  return value.slice(0, 5)
}

export function DashboardPage() {
  const { user } = useAuth()
  const navigate = useNavigate()

  const [dashboard, setDashboard] = useState<HomeDashboardData | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    fetchHomeDashboard()
      .then((res) => setDashboard(res.data ?? null))
      .catch((err) => setError(err instanceof ApiError ? err.message : 'Erro ao carregar o dashboard.'))
  }, [])

  if (error) return <p className="empty-state empty-state--error">{error}</p>
  if (!dashboard) return <p className="empty-state">Carregando...</p>

  const { kpis } = dashboard
  const maxServico = Math.max(...dashboard.servicos.map((item) => item.count), 1)

  return (
    <div className="list-page dashboard-page">
      <div className="page-header">
        <div>
          <h2>Olá{user?.username ? `, ${user.username}` : ''}!</h2>
          <p className="page-subtitle">{formatDateLong(new Date())}</p>
        </div>
      </div>

      <div className="dash-kpis">
        <div className="stat-pill">
          <CalendarDays size={18} />
          <div>
            <strong>{kpis.hoje}</strong> agendamento{kpis.hoje === 1 ? '' : 's'} hoje
          </div>
        </div>
        <div className="stat-pill">
          <PawPrint size={18} />
          <div>
            <strong>{kpis.ativos}</strong> paciente{kpis.ativos === 1 ? '' : 's'} ativo{kpis.ativos === 1 ? '' : 's'}
          </div>
        </div>
        <div className="stat-pill stat-pill--success">
          <Wallet size={18} />
          <div>
            <strong>{formatCurrency(kpis.receita)}</strong> receita no mês
          </div>
        </div>
        <div className="stat-pill">
          <Clock size={18} />
          <div>
            <strong>{kpis.pendentes}</strong> agendamento{kpis.pendentes === 1 ? '' : 's'} pendente{kpis.pendentes === 1 ? '' : 's'}
          </div>
        </div>
      </div>

      <div className="list-layout">
        <div className="list-main">
          <div className="side-card">
            <h3>
              <CalendarDays size={16} /> Agenda de hoje
            </h3>
            {dashboard.hoje.length === 0 && <p className="empty-state">Nenhum agendamento para hoje.</p>}
            {dashboard.hoje.map((item) => (
              <div className="dash-agenda-item" key={item.age_id} onClick={() => navigate('/agenda')}>
                <div className="dash-agenda-item__main">
                  <PacienteAvatar avatar={item.paciente_avatar} pacienteId={item.paciente_id} alt={item.paciente_nome} />
                  <div className="dash-agenda-item__info">
                    <strong>{item.paciente_nome}</strong>
                    <span>{item.age_servico || 'Sem serviço definido'}</span>
                  </div>
                </div>
                <div className="dash-agenda-item__side">
                  <span className="dash-agenda-item__time">
                    <Clock size={12} />
                    {formatTime(item.age_hora)}
                  </span>
                </div>
              </div>
            ))}
          </div>

          <div className="side-card">
            <h3>
              <CalendarDays size={16} /> Próximos agendamentos (7 dias)
            </h3>
            {dashboard.upcoming.length === 0 && <p className="empty-state">Nenhum agendamento futuro.</p>}
            {dashboard.upcoming.map((item) => (
              <div className="dash-agenda-item" key={item.age_id} onClick={() => navigate('/agenda')}>
                <div className="dash-agenda-item__main">
                  <PacienteAvatar avatar={item.paciente_avatar} pacienteId={item.paciente_id} alt={item.paciente_nome} />
                  <div className="dash-agenda-item__info">
                    <strong>{item.paciente_nome}</strong>
                    <span>
                      {item.tutor_nome ? `${item.tutor_nome} · ` : ''}
                      {item.age_servico || 'Sem serviço definido'}
                    </span>
                  </div>
                </div>
                <div className="dash-agenda-item__side">
                  <span className="dash-agenda-item__time">
                    {formatShortDate(item.age_data)} · {formatTime(item.age_hora)}
                  </span>
                </div>
              </div>
            ))}
          </div>

          <div className="side-card">
            <h3>
              <Stethoscope size={16} /> Serviços mais realizados no mês
            </h3>
            {dashboard.servicos.length === 0 && <p className="empty-state">Nenhum serviço registrado neste mês.</p>}
            {dashboard.servicos.map((item) => (
              <div className="dash-bar-row" key={item.service_id}>
                <span>{item.service}</span>
                <div className="dash-bar-row__track">
                  <div className="dash-bar-row__fill" style={{ width: `${(item.count / maxServico) * 100}%` }} />
                </div>
                <span>
                  {item.count} atendimento{item.count === 1 ? '' : 's'}
                </span>
              </div>
            ))}
          </div>
        </div>

        <aside className="list-sidebar">
          <div className="side-card">
            <h3>
              <Cake size={16} /> Aniversariantes do mês
            </h3>
            {dashboard.aniversariantes.length === 0 && <p className="empty-state">Nenhum aniversariante este mês.</p>}
            {dashboard.aniversariantes.map((pet) => (
              <div className="side-list-item" key={pet.paciente_id}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <PacienteAvatar avatar={pet.paciente_avatar} pacienteId={pet.paciente_id} alt={pet.paciente_nome} />
                  <strong>{pet.paciente_nome}</strong>
                </div>
                <span>
                  {formatDayMonth(pet.paciente_nascimento)}
                  {pet.pet_resp_nome ? ` · ${pet.pet_resp_nome}` : ''}
                </span>
              </div>
            ))}
          </div>

          <div className="side-card">
            <h3>
              <Boxes size={16} /> Estoque baixo
            </h3>
            {dashboard.alertasEstoque.length === 0 && <p className="empty-state">Estoque em dia.</p>}
            {dashboard.alertasEstoque.map((produto) => (
              <div className="dash-list-item--stacked" style={{ cursor: 'pointer' }} key={produto.id} onClick={() => navigate('/estoque')}>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px' }}>
                  <strong>{produto.nome}</strong>
                  <span className="badge badge--danger">
                    {produto.quantidade_atual} / {produto.estoque_minimo} {produto.unidade_medida || ''}
                  </span>
                </div>
              </div>
            ))}
          </div>

          <div className="side-card">
            <h3>
              <Syringe size={16} /> Vacinas a vencer (7 dias)
            </h3>
            {dashboard.proximasVacinas.length === 0 && <p className="empty-state">Nenhuma vacina a vencer.</p>}
            {dashboard.proximasVacinas.map((vacina) => (
              <div className="side-list-item" key={vacina.id}>
                <strong>{vacina.paciente_nome}</strong>
                <span>
                  {vacina.vacina_nome} · vence em {formatShortDate(vacina.data_proxima_dose || '')}
                </span>
              </div>
            ))}
          </div>

          <div className="side-card">
            <h3>
              <Stethoscope size={16} /> Pós-consulta
            </h3>
            {dashboard.posConsulta.length === 0 && <p className="empty-state">Nenhum atendimento recente.</p>}
            {dashboard.posConsulta.map((item) => (
              <div className="side-list-item" key={item.age_id}>
                <strong>{item.paciente_nome}</strong>
                <span>
                  {item.age_servico || 'Atendimento'} · {formatShortDate(item.age_data)}
                </span>
              </div>
            ))}
          </div>

          <div className="side-card">
            <h3>
              <UserPlus size={16} /> Pacientes recentes
            </h3>
            {dashboard.recentes.length === 0 && <p className="empty-state">Nenhum paciente cadastrado ainda.</p>}
            {dashboard.recentes.map((paciente) => (
              <div
                className="side-list-item"
                style={{ cursor: 'pointer' }}
                key={paciente.paciente_id}
                onClick={() => navigate(`/prontuarios/${paciente.paciente_id}`)}
              >
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <PacienteAvatar avatar={paciente.paciente_avatar} pacienteId={paciente.paciente_id} alt={paciente.paciente_nome} />
                  <strong>{paciente.paciente_nome}</strong>
                </div>
                <span>{paciente.paciente_especie || 'Espécie não informada'}</span>
              </div>
            ))}
          </div>
        </aside>
      </div>
    </div>
  )
}
