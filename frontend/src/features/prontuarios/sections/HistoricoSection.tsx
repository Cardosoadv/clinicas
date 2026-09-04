import { Calendar, Receipt } from 'lucide-react'
import { useProntuario } from '../ProntuarioContext'

function formatDate(value: string): string {
  try {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(value))
  } catch {
    return value
  }
}

function formatCurrency(value: number): string {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

const statusBadgeClass: Record<string, string> = {
  pendente: 'badge--warning',
  confirmado: 'badge--info',
  concluido: 'badge--success',
  cancelado: 'badge--danger',
}

const statusLabels: Record<string, string> = {
  pendente: 'Pendente',
  confirmado: 'Confirmado',
  concluido: 'Concluído',
  cancelado: 'Cancelado',
}

export function HistoricoSection() {
  const { record } = useProntuario()
  const { agendamentos, procedimentos } = record.historico

  return (
    <div className="prontuario-section">
      <div className="side-card">
        <h3>
          <Calendar size={16} />
          Histórico de Agendamentos
        </h3>
        {agendamentos.length === 0 && <p className="empty-state">Nenhum agendamento registrado.</p>}
        {agendamentos.length > 0 && (
          <table className="relatorio-table">
            <thead>
              <tr>
                <th>Data</th>
                <th>Serviço</th>
                <th>Status</th>
                <th>Observações</th>
              </tr>
            </thead>
            <tbody>
              {agendamentos.map((item) => {
                const badgeClass = statusBadgeClass[item.age_status] || 'badge--info'
                const label = statusLabels[item.age_status] || item.age_status || 'Concluído'
                return (
                  <tr key={item.age_id}>
                    <td>{formatDate(item.age_data)}</td>
                    <td>{item.age_servico || 'Atendimento'}</td>
                    <td>
                      <span className={`badge ${badgeClass}`}>{label}</span>
                    </td>
                    <td>{item.age_obs || '—'}</td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        )}
      </div>

      <div className="side-card">
        <h3>
          <Receipt size={16} />
          Procedimentos Faturados
        </h3>
        {procedimentos.length === 0 && <p className="empty-state">Nenhum procedimento faturado.</p>}
        {procedimentos.length > 0 && (
          <table className="relatorio-table">
            <thead>
              <tr>
                <th>Data</th>
                <th>Serviço</th>
                <th>Status</th>
                <th>Observações</th>
                <th style={{ textAlign: 'right' }}>Valor</th>
              </tr>
            </thead>
            <tbody>
              {procedimentos.map((item) => (
                <tr key={item.id}>
                  <td>{formatDate(item.data_servico)}</td>
                  <td>{item.servico}</td>
                  <td>{item.status}</td>
                  <td>{item.age_obs || item.observacoes || '—'}</td>
                  <td style={{ textAlign: 'right' }}>{formatCurrency(item.valor)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  )
}
