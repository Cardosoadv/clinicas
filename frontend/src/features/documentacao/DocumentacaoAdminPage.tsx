import { BookMarked, Eye, Pencil, Plus, SquarePen, Trash2 } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { usePermissao } from '../auth/AuthContext'
import { copiarDoArquivo, excluirPagina, listarParaEdicao } from './api'
import { caminhoDaDocumentacao, caminhoDaEdicao, dataCurta } from './rotas'
import type { DocItemEdicao } from './types'
import './documentacao.css'

const semAcento = (t: string) => t.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase()

/** Lista de edição: páginas do manual oficial (arquivo) e as criadas ou editadas pela interface. */
export function DocumentacaoAdminPage() {
  const podeEditar = usePermissao('documentacao.editar')
  const navigate = useNavigate()
  const [paginas, setPaginas] = useState<DocItemEdicao[]>([])
  const [carregando, setCarregando] = useState(true)
  const [falha, setFalha] = useState('')
  const [busca, setBusca] = useState('')
  const [filtro, setFiltro] = useState('')
  const [copiando, setCopiando] = useState<string | null>(null)
  const [mensagem, setMensagem] = useState<{ texto: string; erro?: boolean } | null>(null)

  function carregar() {
    setCarregando(true)
    setFalha('')
    listarParaEdicao()
      .then((r) => setPaginas(r.data ?? []))
      .catch(() => setFalha('Não foi possível carregar as páginas.'))
      .finally(() => setCarregando(false))
  }

  useEffect(() => {
    if (podeEditar) carregar()
  }, [podeEditar])

  const filtradas = useMemo(() => {
    const termo = semAcento(busca.trim())
    return paginas.filter((p) => {
      if (filtro === 'banco' && p.origem !== 'banco') return false
      if (filtro === 'arquivo' && p.origem !== 'arquivo') return false
      if (filtro === 'rascunho' && p.status !== 'rascunho') return false
      return !termo || semAcento(`${p.titulo} ${p.slug}`).includes(termo)
    })
  }, [paginas, busca, filtro])

  async function copiar(p: DocItemEdicao) {
    setCopiando(p.slug)
    setMensagem(null)
    try {
      const r = await copiarDoArquivo(p.slug)
      if (r.data) navigate(caminhoDaEdicao(r.data.id), { state: { aviso: 'Cópia criada como rascunho. A versão oficial continua no ar até você publicar.' } })
    } catch (erro) {
      setMensagem({ texto: erro instanceof Error ? erro.message : 'Não foi possível criar a cópia.', erro: true })
    } finally {
      setCopiando(null)
    }
  }

  async function excluir(p: DocItemEdicao) {
    const aviso = p.tem_arquivo
      ? `Excluir a cópia editada de "${p.titulo}"? A página volta a mostrar a versão do manual oficial.`
      : `Excluir a página "${p.titulo}"? Ela some da documentação.`
    if (!p.id || !window.confirm(aviso)) return
    try {
      await excluirPagina(p.id)
      setMensagem({ texto: 'Página excluída.' })
      carregar()
    } catch (erro) {
      setMensagem({ texto: erro instanceof Error ? erro.message : 'Não foi possível excluir.', erro: true })
    }
  }

  if (!podeEditar) {
    return <p className="empty-state empty-state--error">Você não tem permissão para editar a documentação.</p>
  }

  return (
    <div className="list-page">
      <div className="page-header">
        <div>
          <h2>Edição da documentação</h2>
          <p className="page-subtitle">Crie páginas novas ou edite cópias das páginas do manual. Rascunhos só aparecem para quem edita.</p>
        </div>
        <div className="doc-acoes">
          <Link to={caminhoDaDocumentacao()} className="btn btn--ghost">
            <BookMarked size={16} /> Ver documentação
          </Link>
          <Link to={caminhoDaEdicao('nova')} className="btn btn--primary">
            <Plus size={16} /> Nova página
          </Link>
        </div>
      </div>

      {mensagem && (
        <p className={`doc-mensagem${mensagem.erro ? ' doc-mensagem--erro' : ''}`} role={mensagem.erro ? 'alert' : 'status'}>
          {mensagem.texto}
        </p>
      )}

      <div className="doc-filtros">
        <input type="search" value={busca} placeholder="Buscar por título ou endereço" aria-label="Buscar páginas" onChange={(e) => setBusca(e.target.value)} />
        <select className="filter-select" value={filtro} aria-label="Filtrar páginas" onChange={(e) => setFiltro(e.target.value)}>
          <option value="">Todas</option>
          <option value="banco">Criadas ou editadas aqui</option>
          <option value="rascunho">Rascunhos</option>
          <option value="arquivo">Só do manual oficial (arquivo)</option>
        </select>
      </div>

      {carregando ? (
        <p className="empty-state" role="status">Carregando…</p>
      ) : falha ? (
        <div className="empty-state empty-state--error" role="alert">
          <p>{falha}</p>
          <button type="button" className="btn btn--ghost" onClick={carregar}>Tentar de novo</button>
        </div>
      ) : !filtradas.length ? (
        <p className="empty-state">Nenhuma página encontrada.</p>
      ) : (
        <div className="doc-tabela-container">
          <table className="doc-tabela">
            <thead>
              <tr>
                <th>Título</th>
                <th>Endereço</th>
                <th>Origem</th>
                <th>Status</th>
                <th>Alterada em</th>
                <th className="doc-tabela-acoes">Ações</th>
              </tr>
            </thead>
            <tbody>
              {filtradas.map((p) => (
                <tr key={p.slug}>
                  <td>{p.titulo}</td>
                  <td><code>{p.slug}</code></td>
                  <td>
                    {p.origem === 'arquivo' ? (
                      <span className="doc-selo">Manual oficial</span>
                    ) : (
                      <span className="doc-selo doc-selo--banco" title={p.tem_arquivo ? 'Substitui a página do manual oficial quando publicada' : undefined}>
                        {p.tem_arquivo ? 'Cópia editada' : 'Criada aqui'}
                      </span>
                    )}
                  </td>
                  <td>
                    <span className={`doc-selo ${p.status === 'rascunho' ? 'doc-selo--rascunho' : 'doc-selo--publicado'}`}>
                      {p.status === 'rascunho' ? 'Rascunho' : 'Publicado'}
                    </span>
                  </td>
                  <td>{dataCurta(p.atualizado_em)}</td>
                  <td className="doc-tabela-acoes">
                    <Link to={caminhoDaDocumentacao(p.slug)} className="icon-btn" title="Ver na documentação" aria-label={`Ver "${p.titulo}" na documentação`}>
                      <Eye size={16} />
                    </Link>
                    {p.id ? (
                      <>
                        <Link to={caminhoDaEdicao(p.id)} className="icon-btn" title="Editar" aria-label={`Editar "${p.titulo}"`}>
                          <Pencil size={16} />
                        </Link>
                        <button type="button" className="icon-btn icon-btn--danger" title="Excluir" aria-label={`Excluir "${p.titulo}"`} onClick={() => void excluir(p)}>
                          <Trash2 size={16} />
                        </button>
                      </>
                    ) : (
                      <button type="button" className="btn btn--ghost doc-btn-pequeno" disabled={copiando === p.slug} onClick={() => void copiar(p)}>
                        <SquarePen size={15} /> {copiando === p.slug ? 'Copiando…' : 'Editar cópia'}
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
