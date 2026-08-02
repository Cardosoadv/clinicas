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
        <div className="prontuario-timeline">
          {agendamentos.map((item) => (
            <div className="prontuario-timeline__item" key={item.age_id}>
              <div className="prontuario-timeline__dot" />
              <div>
                <div className="prontuario-timeline__header">
                  <strong>{item.age_servico || 'Atendimento'}</strong>
                  <span>{formatDate(item.age_data)}</span>
                </div>
                <span className="badge badge--info">{item.age_status || 'Concluído'}</span>
                {item.age_obs && <p>{item.age_obs}</p>}
              </div>
            </div>
          ))}
        </div>
      </div>

      <div className="side-card">
        <h3>
          <Receipt size={16} />
          Procedimentos Faturados
        </h3>
        {procedimentos.length === 0 && <p className="empty-state">Nenhum procedimento faturado.</p>}
        <table className="relatorio-table">
          <thead>
            <tr>
              <th>Data</th>
              <th>Serviço</th>
              <th>Status</th>
              <th style={{ textAlign: 'right' }}>Valor</th>
            </tr>
          </thead>
          <tbody>
            {procedimentos.map((item) => (
              <tr key={item.id}>
                <td>{formatDate(item.data_servico)}</td>
                <td>{item.servico}</td>
                <td>{item.status}</td>
                <td style={{ textAlign: 'right' }}>{formatCurrency(item.valor)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
