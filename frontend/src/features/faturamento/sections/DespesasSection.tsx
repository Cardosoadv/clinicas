import { Ban, Check, ChevronLeft, ChevronRight, ListOrdered, Paperclip, Plus } from 'lucide-react'
import { useEffect, useState } from 'react'
import { ApiError } from '../../../lib/api'
import { comprovanteUrl, createDespesa, fetchDespesas, fetchGrupoDespesas, marcarDespesaPaga, updateDespesa } from '../api'
import { CancelarDespesaModal } from '../CancelarDespesaModal'
import { deslocarMes, hojeISO, mesDe, rotuloParcela, statusExibido } from '../despesas'
import { DespesaFormModal } from '../DespesaFormModal'
import { useFaturamento } from '../FaturamentoContext'
import { ParcelasGrupoModal } from '../ParcelasGrupoModal'
import { despesaToFormValues, emptyDespesaForm, type Despesa, type DespesasPagina, type DespesaFormValues } from '../types'

type ModalState =
  | { mode: 'create' }
  | { mode: 'edit'; despesa: Despesa; grupo: Despesa[] }
  | { mode: 'cancel'; despesa: Despesa }
  | { mode: 'parcelas'; despesa: Despesa }
  | null

const statusBadgeClass: Record<string, string> = {
  Pendente: 'badge--warning',
  Pago: 'badge--success',
  Atrasado: 'badge--danger',
  Cancelado: 'badge--danger',
}

const nomesMeses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro']

function formatCurrency(value: number): string {
  return Number(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(value: string): string {
  const [ano, mes, dia] = value.split('-')
  return `${dia}/${mes}/${ano}`
}

function rotuloMes(mes: string): string {
  const [ano, m] = mes.split('-').map(Number)
  return `${nomesMeses[m - 1]} de ${ano}`
}

export function DespesasSection() {
  const { reloadDashboard } = useFaturamento()
  const [modal, setModal] = useState<ModalState>(null)
  const [mes, setMes] = useState(() => mesDe(hojeISO()))
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [pagina, setPagina] = useState<DespesasPagina | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [actionError, setActionError] = useState<string | null>(null)
  const [reloadToken, setReloadToken] = useState(0)

  useEffect(() => {
    let cancelado = false
    fetchDespesas({ mes, status, page })
      .then((res) => {
        if (cancelado) return
        // a página pode ter deixado de existir após uma ação (ex.: última pendente marcada como paga)
        if (res.data && page > res.data.total_pages) {
          setPage(res.data.total_pages)
          return
        }
        setPagina(res.data ?? null)
        setError(null)
      })
      .catch((err) => {
        if (!cancelado) setError(err instanceof ApiError ? err.message : 'Erro ao carregar despesas.')
      })
    return () => {
      cancelado = true
    }
  }, [mes, status, page, reloadToken])

  function refresh() {
    reloadDashboard()
    setReloadToken((n) => n + 1)
  }

  function mudarMes(novo: string) {
    setMes(novo)
    setPage(1)
  }

  function mudarStatus(novo: string) {
    setStatus(novo)
    setPage(1)
  }

  async function handleCreate(values: DespesaFormValues): Promise<number | void> {
    const result = await createDespesa(values)
    setModal(null)
    // mostra o mês da (primeira) parcela criada, para o lançamento aparecer na lista
    mudarMes(mesDe(values.data_vencimento))
    refresh()
    // despesa parcelada devolve `ids`; o comprovante é anexado à primeira parcela
    if (typeof result.id === 'number') return result.id
    return Array.isArray(result.ids) && typeof result.ids[0] === 'number' ? result.ids[0] : undefined
  }

  async function handleUpdate(despesa: Despesa, values: DespesaFormValues) {
    await updateDespesa(despesa.id, values, values.escopo, despesa)
    setModal(null)
    refresh()
  }

  async function abrirEdicao(despesa: Despesa) {
    setActionError(null)
    try {
      const grupo = despesa.des_grupo_id ? await fetchGrupoDespesas(despesa.des_grupo_id) : []
      setModal({ mode: 'edit', despesa, grupo })
    } catch {
      setActionError('Não foi possível carregar as parcelas do grupo.')
    }
  }

  async function marcarComoPaga(despesa: Despesa) {
    setActionError(null)
    try {
      await marcarDespesaPaga(despesa.id)
      refresh()
    } catch (err) {
      setActionError(err instanceof ApiError ? err.message : 'Erro ao marcar a despesa como paga.')
    }
  }

  const hoje = hojeISO()

  return (
    <>
      <div className="list-toolbar despesas-filtros">
        <div className="despesas-filtros__mes">
          <button type="button" className="icon-btn" aria-label="Mês anterior" onClick={() => mudarMes(deslocarMes(mes, -1))} disabled={!mes}>
            <ChevronLeft size={16} />
          </button>
          <input type="month" aria-label="Mês de vencimento" value={mes} onChange={(event) => mudarMes(event.target.value)} />
          <button type="button" className="icon-btn" aria-label="Próximo mês" onClick={() => mudarMes(deslocarMes(mes, 1))} disabled={!mes}>
            <ChevronRight size={16} />
          </button>
          <button type="button" className="btn btn--ghost" onClick={() => mudarMes(mes ? '' : mesDe(hoje))}>
            {mes ? 'Todos os meses' : 'Mês atual'}
          </button>
        </div>

        <select aria-label="Status" value={status} onChange={(event) => mudarStatus(event.target.value)}>
          <option value="">Todos os status</option>
          <option value="Pendente">Pendente</option>
          <option value="Pago">Pago</option>
          <option value="Cancelado">Cancelado</option>
        </select>

        <button type="button" className="btn btn--primary" onClick={() => setModal({ mode: 'create' })}>
          <Plus size={16} /> Nova Despesa
        </button>
      </div>

      {error && <p className="empty-state empty-state--error">{error}</p>}
      {actionError && (
        <p className="error-message" role="alert">
          {actionError}
        </p>
      )}
      {!error && !pagina && <p className="empty-state">Carregando...</p>}

      {!error && pagina && (
        <>
          <div className="record-list">
            {pagina.items.length === 0 && (
              <p className="empty-state">Nenhuma despesa {mes ? `com vencimento em ${rotuloMes(mes)}` : 'registrada'}.</p>
            )}
            {pagina.items.map((despesa) => {
              const parcela = rotuloParcela(despesa)
              const statusVisivel = statusExibido(despesa.status, despesa.data_vencimento, hoje)
              return (
                <div key={despesa.id} className="record-card">
                  <div className="record-card__main" style={{ cursor: 'pointer' }} onClick={() => void abrirEdicao(despesa)}>
                    <div className="record-card__title-row">
                      <h3>{despesa.descricao}</h3>
                      {parcela && <span className="badge badge--info">{parcela}</span>}
                      <span className={`badge ${statusBadgeClass[statusVisivel]}`}>{statusVisivel}</span>
                    </div>
                    <div className="record-card__meta">
                      <span>{despesa.categoria}</span>
                      <span>Valor: {formatCurrency(despesa.valor)}</span>
                      <span>Vencimento: {formatDate(despesa.data_vencimento)}</span>
                      {despesa.fornecedor && <span>Fornecedor: {despesa.fornecedor}</span>}
                    </div>
                  </div>
                  <div className="record-card__actions">
                    {despesa.status === 'Pendente' && (
                      <button type="button" className="icon-btn" aria-label="Marcar como paga" title="Marcar como paga" onClick={() => void marcarComoPaga(despesa)}>
                        <Check size={16} />
                      </button>
                    )}
                    {despesa.des_grupo_id && (
                      <button type="button" className="icon-btn" aria-label="Ver todas as parcelas" title="Ver todas as parcelas" onClick={() => setModal({ mode: 'parcelas', despesa })}>
                        <ListOrdered size={16} />
                      </button>
                    )}
                    {despesa.status === 'Pendente' && (
                      <button type="button" className="icon-btn icon-btn--danger" aria-label="Cancelar despesa" title="Cancelar despesa" onClick={() => setModal({ mode: 'cancel', despesa })}>
                        <Ban size={16} />
                      </button>
                    )}
                    {despesa.comprovante && (
                      <a
                        className="icon-btn"
                        href={comprovanteUrl(despesa.id, despesa.comprovante.split('/').pop() ?? '')}
                        target="_blank"
                        rel="noreferrer"
                        aria-label="Ver comprovante"
                      >
                        <Paperclip size={16} />
                      </a>
                    )}
                  </div>
                </div>
              )
            })}
          </div>

          {pagina.total > 0 && (
            <div className="despesas-paginacao">
              <button type="button" className="btn btn--ghost" disabled={page <= 1} onClick={() => setPage(page - 1)}>
                Anterior
              </button>
              <span>
                Página {pagina.page} de {pagina.total_pages} · {pagina.total} despesa{pagina.total === 1 ? '' : 's'}
              </span>
              <button type="button" className="btn btn--ghost" disabled={page >= pagina.total_pages} onClick={() => setPage(page + 1)}>
                Próxima
              </button>
            </div>
          )}
        </>
      )}

      {modal?.mode === 'create' && (
        <DespesaFormModal
          title="Nova Despesa"
          allowParcelar
          initialValues={emptyDespesaForm()}
          onClose={() => setModal(null)}
          onSubmit={handleCreate}
        />
      )}
      {modal?.mode === 'edit' && (
        <DespesaFormModal
          title="Editar Despesa"
          grupo={modal.despesa.des_grupo_id ? { atual: modal.despesa, parcelas: modal.grupo } : undefined}
          initialValues={despesaToFormValues(modal.despesa)}
          onClose={() => setModal(null)}
          onSubmit={(values) => handleUpdate(modal.despesa, values)}
        />
      )}
      {modal?.mode === 'cancel' && (
        <CancelarDespesaModal
          despesa={modal.despesa}
          onClose={() => setModal(null)}
          onDone={() => {
            setModal(null)
            refresh()
          }}
        />
      )}
      {modal?.mode === 'parcelas' && <ParcelasGrupoModal despesa={modal.despesa} onClose={() => setModal(null)} />}
    </>
  )
}
