import { useAuth } from '../features/auth/AuthContext'

export function DashboardPage() {
  const { user } = useAuth()

  return (
    <div className="dashboard-page">
      <h2>Bem-vindo{user?.username ? `, ${user.username}` : ''}!</h2>
      <p>Você está autenticado como {user?.email}.</p>
    </div>
  )
}
