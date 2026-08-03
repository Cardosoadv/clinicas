import {
  Activity,
  ArrowLeft,
  Calculator,
  FileText,
  History,
  Images,
  Pill,
  Printer,
  Scale,
  Stethoscope,
  Syringe,
} from 'lucide-react'
import { useCallback, useEffect, useState } from 'react'
import { NavLink, Outlet, useNavigate, useParams } from 'react-router-dom'
import { ApiError } from '../../lib/api'
import { fetchProntuario, pacienteAvatarUrl } from './api'
import { CalculadoraModal } from './CalculadoraModal'
import { ProntuarioContext } from './ProntuarioContext'
import './prontuarios.css'
import type { ProntuarioRecord } from './types'

const submenuItems = [
  { to: 'anamnese', label: 'Anamnese', icon: FileText },
  { to: 'historico', label: 'Histórico', icon: History },
  { to: 'vacinas', label: 'Vacinas', icon: Syringe },
  { to: 'peso', label: 'Peso', icon: Scale },
  { to: 'prescricoes', label: 'Prescrições', icon: Pill },
  { to: 'evolucao', label: 'Evolução', icon: Activity },
  { to: 'imagens', label: 'Imagens', icon: Images },
]

function calcIdade(nascimento: string | null): string {
  if (!nascimento || nascimento.startsWith('0000')) return 'idade não informada'

  const nasc = new Date(nascimento)
  if (Number.isNaN(nasc.getTime())) return 'idade não informada'

  const now = new Date()
  let anos = now.getFullYear() - nasc.getFullYear()
  let meses = now.getMonth() - nasc.getMonth()
  if (now.getDate() < nasc.getDate()) meses -= 1
  if (meses < 0) {
    anos -= 1
    meses += 12
  }

  if (anos <= 0) return `${meses} ${meses === 1 ? 'mês' : 'meses'}`
  return `${anos} ${anos === 1 ? 'ano' : 'anos'}${meses > 0 ? ` e ${meses} ${meses === 1 ? 'mês' : 'meses'}` : ''}`
}

function formatCurrency(value: number): string {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

const statusBadgeClass: Record<string, string> = {
  Ativo: 'badge--success',
  Inativo: 'badge--danger',
  Suspenso: 'badge--warning',
}

export function ProntuarioLayout() {
  const params = useParams<{ id: string }>()
  const pacienteId = Number(params.id)
  const navigate = useNavigate()

  const [record, setRecord] = useState<ProntuarioRecord | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [isCalcOpen, setIsCalcOpen] = useState(false)
  const [prefillEvolucao, setPrefillEvolucao] = useState<string | null>(null)

  const load = useCallback(() => {
    if (!pacienteId) return
    setIsLoading(true)
    setError(null)
    fetchProntuario(pacienteId)
      .then((res) => setRecord(res.data ?? null))
      .catch((err) => setError(err instanceof ApiError ? err.message : 'Erro ao carregar prontuário.'))
      .finally(() => setIsLoading(false))
  }, [pacienteId])

  useEffect(() => {
    load()
  }, [load])

  if (isLoading) {
    return (
      <div className="list-page">
        <p className="empty-state">Carregando prontuário...</p>
      </div>
    )
  }

  if (error || !record) {
    return (
      <div className="list-page">
        <p className="empty-state empty-state--error">{error ?? 'Prontuário não encontrado.'}</p>
      </div>
    )
  }

  const { pet, estatisticas } = record
  const hasAvatarFile = Boolean(pet.paciente_avatar && pet.paciente_avatar.includes('/'))
  const ultimoPeso = record.pesos[0]?.peso ?? null

  return (
    <ProntuarioContext.Provider value={{ pacienteId, record, reload: load, prefillEvolucao, setPrefillEvolucao }}>
      <div className="list-page prontuario-page">
        <div className="page-header">
          <div className="prontuario-header__title">
            <button type="button" className="icon-btn" aria-label="Voltar" onClick={() => navigate('/prontuarios')}>
              <ArrowLeft size={18} />
            </button>
            {hasAvatarFile ? (
              <img
                className="prontuario-avatar-img"
                src={pacienteAvatarUrl(pacienteId, pet.paciente_avatar!.split('/').pop()!)}
                alt={pet.paciente_nome}
                crossOrigin="use-credentials"
              />
            ) : (
              <span className="paciente-avatar prontuario-avatar">{pet.paciente_avatar || '🐾'}</span>
            )}
            <div>
              <div className="prontuario-header__title-row">
                <h2>{pet.paciente_nome}</h2>
                <span className={`badge ${statusBadgeClass[pet.paciente_status] ?? 'badge--info'}`}>
                  {pet.paciente_status}
                </span>
              </div>
              <p className="page-subtitle">
                {[pet.paciente_especie, pet.paciente_raca, calcIdade(pet.paciente_nascimento)].filter(Boolean).join(' · ')}
                {pet.pet_resp_nome ? ` · Tutor: ${pet.pet_resp_nome}` : ''}
                {pet.pet_resp_tel ? ` · ${pet.pet_resp_tel}` : ''}
              </p>
            </div>
          </div>

          <div className="prontuario-header__actions">
            <button type="button" className="btn btn--ghost" onClick={() => setIsCalcOpen(true)}>
              <Calculator size={16} />
              Calculadora
            </button>
            <button type="button" className="btn btn--ghost" onClick={() => window.print()}>
              <Printer size={16} />
              Imprimir
            </button>
          </div>
        </div>

        <div className="stat-pills">
          <div className="stat-pill">
            <Stethoscope size={16} />
            Consultas: <strong>{estatisticas.consultas}</strong>
          </div>
          <div className="stat-pill">
            <Images size={16} />
            Exames: <strong>{estatisticas.imagens}</strong>
          </div>
          <div className="stat-pill stat-pill--success">
            Total investido: <strong>{formatCurrency(estatisticas.total_investido)}</strong>
          </div>
        </div>

        <div className="prontuario-layout">
          <nav className="prontuario-submenu">
            {submenuItems.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                className={({ isActive }) => `prontuario-submenu__link${isActive ? ' prontuario-submenu__link--active' : ''}`}
              >
                <item.icon size={16} />
                {item.label}
              </NavLink>
            ))}
          </nav>

          <div className="prontuario-content">
            <Outlet />
          </div>
        </div>
      </div>

      {isCalcOpen && (
        <CalculadoraModal
          pesoAtual={ultimoPeso}
          onClose={() => setIsCalcOpen(false)}
          onCopyToEvolucao={(texto) => {
            setPrefillEvolucao(texto)
            setIsCalcOpen(false)
            navigate(`/prontuarios/${pacienteId}/evolucao`)
          }}
        />
      )}
    </ProntuarioContext.Provider>
  )
}
