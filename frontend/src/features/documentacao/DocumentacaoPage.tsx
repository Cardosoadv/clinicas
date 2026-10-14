import { ArrowLeft, ArrowRight, ListTree, PencilLine, Printer, SquarePen } from 'lucide-react'
import { useCallback, useEffect, useRef, useState } from 'react'
import { Link, useLocation, useNavigate, useParams } from 'react-router-dom'
import { ApiError } from '../../lib/api'
import { usePermissao } from '../auth/AuthContext'
import { copiarDoArquivo, obterIndice, obterPagina } from './api'
import { DocArvore } from './DocArvore'
import { DocBusca } from './DocBusca'
import { DocConteudo } from './DocConteudo'
import { DocSumario } from './DocSumario'
import { caminhoDaDocumentacao, caminhoDaEdicao, dataCurta } from './rotas'
import type { DocPagina, DocSecao } from './types'
import './documentacao.css'

/** `[id="…"]` com aspas e barras escapadas (o id vem do backend, mas não confiamos no formato). */
const seletorDoId = (id: string) => `[id="${id.replace(/["\\]/g, '\\$&')}"]`

const telaPequena = () => typeof window.matchMedia === 'function' && window.matchMedia('(max-width: 768px)').matches

/**
 * Leitura da documentação: índice e busca à esquerda, página no centro e
 * "Nesta página" à direita. Endereço: `/documentacao/{slug}#{ancora}`.
 */
export function DocumentacaoPage() {
  const slug = useParams()['*']?.replace(/\/+$/, '') || null
  const location = useLocation()
  const navigate = useNavigate()
  const ancora = location.hash ? decodificar(location.hash.slice(1)) : null
  const podeEditar = usePermissao('documentacao.editar')

  const [indice, setIndice] = useState<DocSecao[]>([])
  const [falhaIndice, setFalhaIndice] = useState('')
  const [pagina, setPagina] = useState<DocPagina | null>(null)
  const [carregando, setCarregando] = useState(false)
  const [falha, setFalha] = useState<{ naoEncontrada: boolean; mensagem: string } | null>(null)
  const [tentativa, setTentativa] = useState(0)
  const [ancoraAtiva, setAncoraAtiva] = useState<string | null>(null)
  const [arvoreRecolhida, setArvoreRecolhida] = useState(false)
  const [gavetaAberta, setGavetaAberta] = useState(false)
  const [preparandoEdicao, setPreparandoEdicao] = useState(false)
  const [mensagem, setMensagem] = useState('')
  const raiz = useRef<HTMLDivElement>(null)
  const corpo = useRef<HTMLDivElement>(null)

  useEffect(() => {
    obterIndice()
      .then((r) => setIndice(r.data ?? []))
      .catch(() => setFalhaIndice('Não foi possível carregar o índice.'))
  }, [])

  useEffect(() => {
    setFalha(null)
    setAncoraAtiva(null)
    if (!slug) {
      setPagina(null)
      return
    }
    let cancelado = false
    setCarregando(true)
    obterPagina(slug)
      .then((r) => {
        if (!cancelado) setPagina(r.data ?? null)
      })
      .catch((erro) => {
        if (cancelado) return
        setPagina(null)
        const naoEncontrada = erro instanceof ApiError && erro.status === 404
        setFalha({
          naoEncontrada,
          mensagem: naoEncontrada
            ? 'Ela pode ter sido removida ou não estar disponível para o seu perfil.'
            : 'Não foi possível carregar a página.',
        })
      })
      .finally(() => {
        if (!cancelado) setCarregando(false)
      })
    return () => {
      cancelado = true
    }
  }, [slug, tentativa])

  // Ao abrir uma página, rola até a âncora ou volta ao topo.
  const paginaAberta = pagina?.slug
  useEffect(() => {
    if (!paginaAberta) return
    const alvo = ancora ? corpo.current?.querySelector<HTMLElement>(seletorDoId(ancora)) : null
    if (alvo) {
      alvo.scrollIntoView?.({ behavior: 'smooth', block: 'start' })
      setAncoraAtiva(ancora)
    } else {
      raiz.current?.scrollIntoView?.({ block: 'start' })
    }
  }, [paginaAberta, ancora])

  // "Nesta página" acompanha a leitura: destaca o título que está no topo.
  useEffect(() => {
    if (!pagina?.sumario.length || !corpo.current || typeof IntersectionObserver === 'undefined') return
    const titulos = pagina.sumario
      .map((item) => corpo.current!.querySelector<HTMLElement>(seletorDoId(item.ancora)))
      .filter((el): el is HTMLElement => el !== null)
    const observador = new IntersectionObserver(
      (entradas) => {
        const visiveis = entradas.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)
        if (visiveis.length) setAncoraAtiva((visiveis[0].target as HTMLElement).id)
      },
      { rootMargin: '0px 0px -70% 0px' },
    )
    titulos.forEach((t) => observador.observe(t))
    return () => observador.disconnect()
  }, [pagina])

  const fecharGaveta = useCallback(() => setGavetaAberta(false), [])

  function alternarArvore() {
    if (telaPequena()) setGavetaAberta((v) => !v)
    else setArvoreRecolhida((v) => !v)
  }

  /** Página do banco: abre o editor. Página de arquivo: cria a cópia editável (rascunho) e abre. */
  async function editarPagina() {
    if (!pagina) return
    if (pagina.id) {
      navigate(caminhoDaEdicao(pagina.id))
      return
    }
    setPreparandoEdicao(true)
    setMensagem('')
    try {
      const r = await copiarDoArquivo(pagina.slug)
      if (r.data) navigate(caminhoDaEdicao(r.data.id), { state: { aviso: 'Cópia criada como rascunho. A versão oficial continua no ar até você publicar.' } })
    } catch (erro) {
      setMensagem(erro instanceof Error ? erro.message : 'Não foi possível preparar a edição.')
    } finally {
      setPreparandoEdicao(false)
    }
  }

  const arvoreVisivel = telaPequena() ? gavetaAberta : !arvoreRecolhida
  const classesLayout = [
    'doc-layout',
    !pagina?.sumario.length && 'sem-sumario',
    arvoreRecolhida && 'arvore-recolhida',
    gavetaAberta && 'gaveta-aberta',
  ].filter(Boolean).join(' ')

  return (
    <div ref={raiz} className="list-page documentacao">
      <div className="page-header">
        <div>
          <h2>Documentação</h2>
          <p className="page-subtitle">Manual do sistema: como usar cada tela.</p>
        </div>
        <div className="doc-acoes">
          <button type="button" className="btn btn--ghost" aria-expanded={arvoreVisivel} aria-controls="doc-lateral" onClick={alternarArvore}>
            <ListTree size={16} /> Índice
          </button>
          {pagina && (
            <button type="button" className="btn btn--ghost" onClick={() => window.print()}>
              <Printer size={16} /> Imprimir
            </button>
          )}
          {podeEditar && pagina && (
            <button type="button" className="btn btn--ghost" disabled={preparandoEdicao} onClick={() => void editarPagina()}>
              <PencilLine size={16} /> {preparandoEdicao ? 'Preparando…' : 'Editar esta página'}
            </button>
          )}
          {podeEditar && (
            <Link to={caminhoDaEdicao()} className="btn btn--ghost">
              <SquarePen size={16} /> Editar documentação
            </Link>
          )}
        </div>
      </div>

      {mensagem && <p className="doc-mensagem doc-mensagem--erro" role="alert">{mensagem}</p>}

      <div className={classesLayout}>
        {gavetaAberta && <div className="doc-gaveta-fundo" onClick={fecharGaveta} />}
        <aside id="doc-lateral" className="doc-lateral">
          {falhaIndice && <p className="doc-falha-indice" role="alert">{falhaIndice}</p>}
          <DocArvore
            indice={indice}
            slugAtual={slug}
            aoNavegar={fecharGaveta}
            topo={<DocBusca aoEscolher={(s) => { fecharGaveta(); navigate(caminhoDaDocumentacao(s)) }} />}
          />
        </aside>

        {carregando ? (
          <div className="doc-artigo" role="status">Carregando…</div>
        ) : falha ? (
          <div className="doc-artigo" role="alert">
            <h1>{falha.naoEncontrada ? 'Página não encontrada' : 'Algo deu errado'}</h1>
            <p className="doc-resumo">{falha.mensagem}</p>
            <div className="doc-falha-acoes">
              {!falha.naoEncontrada && (
                <button type="button" className="btn btn--ghost" onClick={() => setTentativa((t) => t + 1)}>
                  Tentar de novo
                </button>
              )}
              <Link to={caminhoDaDocumentacao()} className="btn btn--ghost">
                Ir para o início da documentação
              </Link>
            </div>
          </div>
        ) : pagina ? (
          <article className="doc-artigo">
            <h1>
              {pagina.titulo}
              {pagina.status === 'rascunho' && <span className="doc-selo doc-selo--rascunho" title="Só quem edita vê esta versão">Rascunho</span>}
            </h1>
            {pagina.resumo && <p className="doc-resumo">{pagina.resumo}</p>}
            <DocConteudo ref={corpo} html={pagina.html} />

            {(pagina.anterior || pagina.proxima) && (
              <nav className="doc-vizinhas" aria-label="Páginas vizinhas">
                {pagina.anterior && (
                  <Link className="anterior" to={caminhoDaDocumentacao(pagina.anterior.slug)}>
                    <small><ArrowLeft size={13} /> Anterior</small>
                    {pagina.anterior.titulo}
                  </Link>
                )}
                {pagina.proxima && (
                  <Link className="proxima" to={caminhoDaDocumentacao(pagina.proxima.slug)}>
                    <small>Próxima <ArrowRight size={13} /></small>
                    {pagina.proxima.titulo}
                  </Link>
                )}
              </nav>
            )}
            <p className="doc-atualizado">Atualizada em {dataCurta(pagina.atualizado_em)}</p>
          </article>
        ) : (
          <div className="doc-inicio">
            {!indice.length && !falhaIndice && <p className="doc-artigo">Nenhuma página disponível para o seu perfil.</p>}
            <div className="doc-secoes">
              {indice.map((secao) => (
                <section key={secao.slug} className="doc-secao-cartao">
                  <h3>{secao.capa ? <Link to={caminhoDaDocumentacao(secao.slug)}>{secao.titulo}</Link> : secao.titulo}</h3>
                  {secao.resumo && <p className="doc-secao-resumo">{secao.resumo}</p>}
                  <ul>
                    {secao.paginas.map((p) => (
                      <li key={p.slug}>
                        <Link to={caminhoDaDocumentacao(p.slug)}>{p.titulo}</Link>
                      </li>
                    ))}
                  </ul>
                </section>
              ))}
            </div>
          </div>
        )}

        {pagina && <DocSumario itens={pagina.sumario} slug={pagina.slug} ativa={ancoraAtiva} />}
      </div>
    </div>
  )
}

function decodificar(texto: string): string {
  try {
    return decodeURIComponent(texto)
  } catch {
    return texto
  }
}
