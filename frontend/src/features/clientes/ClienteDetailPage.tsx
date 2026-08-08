import {
  ArrowLeft,
  Building2,
  Calendar,
  MapPin,
  MessageCircle,
  Pencil,
  PlusCircle,
  Send,
  StickyNote,
  Trash2,
  User,
} from 'lucide-react'
import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { PacienteAvatar } from '../../components/PacienteAvatar'
import { formatCNPJ, formatCPF } from '../../lib/document'
import { ApiError } from '../../lib/api'
import type { Paciente } from '../pacientes/types'
import {
  createClienteNota,
  deleteClienteNota,
  enviarComunicacao,
  fetchCliente,
  fetchClienteComunicacoes,
  fetchClienteNotas,
  fetchClientePacientes,
  updateCliente,
} from './api'
import { ClienteFormModal } from './ClienteFormModal'
import './clientes-detail.css'
import {
  clienteToFormValues,
  comunicacaoTipoLabel,
  type Cliente,
  type ClienteFormValues,
  type ClienteNota,
  type Comunicacao,
} from './types'

function formatDate(value: string | null): string {
  if (!value) return '—'
  try {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(value))
  } catch {
    return value
  }
}

function formatDateTime(value: string): string {
  try {
    return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value))
  } catch {
    return value
  }
}

export function ClienteDetailPage() {
  const params = useParams<{ id: string }>()
  const clienteId = Number(params.id)
  const navigate = useNavigate()

  const [cliente, setCliente] = useState<Cliente | null>(null)
  const [pacientes, setPacientes] = useState<Paciente[] | null>(null)
  const [notas, setNotas] = useState<ClienteNota[] | null>(null)
  const [comunicacoes, setComunicacoes] = useState<Comunicacao[] | null>(null)

  const [isLoading, setIsLoading] = useState(true)
  const [loadError, setLoadError] = useState<string | null>(null)
  const [isEditModalOpen, setIsEditModalOpen] = useState(false)

  const [novaNota, setNovaNota] = useState('')
  const [isSavingNota, setIsSavingNota] = useState(false)

  const [novaMensagem, setNovaMensagem] = useState('')
  const [isSendingMensagem, setIsSendingMensagem] = useState(false)
  const [sendError, setSendError] = useState<string | null>(null)

  useEffect(() => {
    if (!clienteId) return
    load()
  }, [clienteId])

  function load() {
    setIsLoading(true)
    setLoadError(null)

    Promise.all([
      fetchCliente(clienteId),
      fetchClientePacientes(clienteId),
      fetchClienteNotas(clienteId),
      fetchClienteComunicacoes(clienteId),
    ])
      .then(([clienteRes, pacientesRes, notasRes, comunicacoesRes]) => {
        setCliente(clienteRes.data ?? null)
        setPacientes(pacientesRes.data ?? [])
        setNotas(notasRes.data ?? [])
        setComunicacoes(comunicacoesRes.data ?? [])
      })
      .catch((err) => setLoadError(err instanceof ApiError ? err.message : 'Erro ao carregar dados do cliente.'))
      .finally(() => setIsLoading(false))
  }

  function reloadNotas() {
    fetchClienteNotas(clienteId)
      .then((res) => setNotas(res.data ?? []))
      .catch(() => undefined)
  }

  function reloadComunicacoes() {
    fetchClienteComunicacoes(clienteId)
      .then((res) => setComunicacoes(res.data ?? []))
      .catch(() => undefined)
  }

  async function handleUpdateCliente(values: ClienteFormValues) {
    await updateCliente(clienteId, values)
    setIsEditModalOpen(false)
    load()
  }

  async function handleAddNota() {
    if (!novaNota.trim()) return
    setIsSavingNota(true)
    try {
      await createClienteNota(clienteId, novaNota.trim())
      setNovaNota('')
      reloadNotas()
    } catch {
      // erro visível apenas se necessário voltar a tentar; formulário mantém o texto digitado
    } finally {
      setIsSavingNota(false)
    }
  }

  async function handleDeleteNota(nota: ClienteNota) {
    if (!window.confirm('Excluir esta nota?')) return
    await deleteClienteNota(clienteId, nota.id)
    reloadNotas()
  }

  async function handleEnviarMensagem() {
    if (!novaMensagem.trim()) return
    setSendError(null)
    setIsSendingMensagem(true)
    try {
      const res = await enviarComunicacao(clienteId, novaMensagem.trim())
      if (res.data?.link) {
        window.open(res.data.link, '_blank', 'noopener')
      }
      setNovaMensagem('')
      reloadComunicacoes()
    } catch (err) {
      setSendError(err instanceof ApiError ? err.message : 'Erro ao enviar mensagem.')
    } finally {
      setIsSendingMensagem(false)
    }
  }

  if (isLoading) {
    return (
      <div className="list-page">
        <p className="empty-state">Carregando...</p>
      </div>
    )
  }

  if (loadError || !cliente) {
    return (
      <div className="list-page">
        <p className="empty-state empty-state--error">{loadError ?? 'Cliente não encontrado.'}</p>
      </div>
    )
  }

  const isInactive = cliente.deleted_at !== null
  const isJuridica = Boolean(cliente.cnpj)

  return (
    <div className="list-page">
      <div className="page-header">
        <div className="cliente-detail__title">
          <button type="button" className="icon-btn" aria-label="Voltar" onClick={() => navigate('/clientes')}>
            <ArrowLeft size={18} />
          </button>
          <div>
            <div className="cliente-detail__title-row">
              <h2>{cliente.nome}</h2>
              <span className={`badge ${isInactive ? 'badge--danger' : 'badge--success'}`}>
                {isInactive ? 'Inativo' : 'Ativo'}
              </span>
              <span className="badge badge--info">{isJuridica ? 'Pessoa Jurídica' : 'Pessoa Física'}</span>
            </div>
            <p className="page-subtitle">{cliente.apelido || cliente.razao_social || 'Ficha do cliente'}</p>
          </div>
        </div>
        <button type="button" className="btn btn--primary" onClick={() => setIsEditModalOpen(true)}>
          <Pencil size={16} />
          Editar Cliente
        </button>
      </div>

      <div className="list-layout">
        <div className="list-main">
          <div className="side-card">
            <h3>
              <User size={16} />
              Dados do Cliente
            </h3>
            <div className="cliente-detail__fields">
              <div>
                <span className="cliente-detail__field-label">Documento</span>
                <span>{isJuridica ? formatCNPJ(cliente.cnpj ?? '') : cliente.cpf ? formatCPF(cliente.cpf) : '—'}</span>
              </div>
              {isJuridica && (
                <div>
                  <span className="cliente-detail__field-label">Razão Social</span>
                  <span>{cliente.razao_social || '—'}</span>
                </div>
              )}
              <div>
                <span className="cliente-detail__field-label">Telefones</span>
                <span>{cliente.telefones || '—'}</span>
              </div>
              <div>
                <span className="cliente-detail__field-label">E-mails</span>
                <span>{cliente.emails || '—'}</span>
              </div>
              <div>
                <span className="cliente-detail__field-label">
                  <Calendar size={13} /> Nascimento
                </span>
                <span>{formatDate(cliente.nascimento)}</span>
              </div>
              <div>
                <span className="cliente-detail__field-label">
                  <MapPin size={13} /> Localização
                </span>
                <span>
                  {[cliente.cidade, cliente.estado].filter(Boolean).join(' / ') || '—'}
                </span>
              </div>
              <div>
                <span className="cliente-detail__field-label">
                  <Building2 size={13} /> Cliente desde
                </span>
                <span>{formatDate(cliente.created_at)}</span>
              </div>
            </div>
            {cliente.observacoes && (
              <div className="cliente-detail__observacoes">
                <span className="cliente-detail__field-label">Observações gerais</span>
                <p>{cliente.observacoes}</p>
              </div>
            )}
          </div>

          <div className="side-card">
            <h3>
              <User size={16} />
              Pacientes Vinculados ({pacientes?.length ?? 0})
            </h3>
            {pacientes?.length === 0 && <p className="empty-state">Nenhum paciente vinculado a este cliente.</p>}
            <div className="cliente-detail__pacientes">
              {pacientes?.map((paciente) => (
                <button
                  type="button"
                  key={paciente.paciente_id}
                  className="cliente-detail__paciente-card"
                  onClick={() => navigate(`/pacientes?search=${encodeURIComponent(paciente.paciente_nome)}`)}
                >
                  <PacienteAvatar avatar={paciente.paciente_avatar} pacienteId={paciente.paciente_id} alt={paciente.paciente_nome} />
                  <div>
                    <strong>{paciente.paciente_nome}</strong>
                    <span>
                      {[paciente.paciente_especie, paciente.paciente_raca].filter(Boolean).join(' · ') || 'Espécie não informada'}
                    </span>
                  </div>
                  <span className={`badge ${paciente.paciente_status === 'Ativo' ? 'badge--success' : 'badge--danger'}`}>
                    {paciente.paciente_status}
                  </span>
                </button>
              ))}
            </div>
          </div>

          <div className="side-card">
            <h3>
              <MessageCircle size={16} />
              Últimas Comunicações
            </h3>

            <div className="cliente-detail__send-form">
              <textarea
                rows={2}
                placeholder="Escreva uma mensagem para enviar via WhatsApp..."
                value={novaMensagem}
                onChange={(event) => setNovaMensagem(event.target.value)}
              />
              <button
                type="button"
                className="btn btn--primary"
                disabled={isSendingMensagem || !novaMensagem.trim()}
                onClick={() => void handleEnviarMensagem()}
              >
                <Send size={16} />
                {isSendingMensagem ? 'Enviando...' : 'Enviar'}
              </button>
            </div>
            {sendError && (
              <p className="error-message" role="alert">
                {sendError}
              </p>
            )}

            {comunicacoes?.length === 0 && <p className="empty-state">Nenhuma comunicação registrada ainda.</p>}
            <div className="cliente-detail__timeline">
              {comunicacoes?.map((item) => (
                <div className="cliente-detail__timeline-item" key={item.id}>
                  <MessageCircle size={14} />
                  <div>
                    <div className="cliente-detail__timeline-header">
                      <strong>{comunicacaoTipoLabel[item.tipo]}</strong>
                      <span>{formatDateTime(item.created_at)}</span>
                    </div>
                    {item.paciente_nome && <span className="cliente-detail__timeline-pet">{item.paciente_nome}</span>}
                    <p>{item.mensagem}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        <aside className="list-sidebar">
          <div className="side-card">
            <h3>
              <StickyNote size={16} />
              Notas
            </h3>

            <div className="cliente-detail__nota-form">
              <textarea
                rows={3}
                placeholder="Adicionar uma nota sobre este cliente..."
                value={novaNota}
                onChange={(event) => setNovaNota(event.target.value)}
              />
              <button
                type="button"
                className="btn btn--ghost"
                disabled={isSavingNota || !novaNota.trim()}
                onClick={() => void handleAddNota()}
              >
                <PlusCircle size={16} />
                {isSavingNota ? 'Salvando...' : 'Adicionar Nota'}
              </button>
            </div>

            {notas?.length === 0 && <p className="empty-state">Nenhuma nota registrada.</p>}
            <div className="cliente-detail__notas-list">
              {notas?.map((nota) => (
                <div className="cliente-detail__nota" key={nota.id}>
                  <p>{nota.texto}</p>
                  <div className="cliente-detail__nota-footer">
                    <span>
                      {nota.autor ? `${nota.autor} · ` : ''}
                      {formatDateTime(nota.created_at)}
                    </span>
                    <button
                      type="button"
                      className="icon-btn icon-btn--danger"
                      aria-label="Excluir nota"
                      onClick={() => void handleDeleteNota(nota)}
                    >
                      <Trash2 size={14} />
                    </button>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </aside>
      </div>

      {isEditModalOpen && (
        <ClienteFormModal
          title="Editar Cliente"
          initialValues={clienteToFormValues(cliente)}
          onClose={() => setIsEditModalOpen(false)}
          onSubmit={handleUpdateCliente}
        />
      )}
    </div>
  )
}
