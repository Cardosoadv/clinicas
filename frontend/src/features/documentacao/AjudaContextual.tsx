import { BookMarked, CircleHelp, X } from 'lucide-react'
import { useCallback, useEffect, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import { Link, useLocation } from 'react-router-dom'
import { ApiError } from '../../lib/api'
import { obterPaginaDaTela } from './api'
import { DocConteudo } from './DocConteudo'
import { caminhoDaDocumentacao } from './rotas'
import { telaDaRota } from './telas'
import type { DocPagina } from './types'
import './documentacao.css'

// Telas sem ajuda (404) ficam guardadas como `null` durante a sessão; falha de rede não é guardada.
const cache = new Map<string, DocPagina | null>()

/**
 * Botão "?" da barra superior: abre, num painel lateral, a página da documentação
 * que declara a tela atual em `telas`. Some quando a tela não tem ajuda.
 * Atalhos: F1 ou `?` (fora de campos de texto); Esc fecha.
 */
export function AjudaContextual() {
  const { pathname } = useLocation()
  const tela = telaDaRota(pathname)
  const [pagina, setPagina] = useState<DocPagina | null>(null)
  const [aberto, setAberto] = useState(false)
  const painel = useRef<HTMLElement>(null)

  useEffect(() => {
    setAberto(false)
    if (!tela) {
      setPagina(null)
      return
    }
    if (cache.has(tela)) {
      setPagina(cache.get(tela) ?? null)
      return
    }
    let cancelado = false
    setPagina(null)
    obterPaginaDaTela(tela)
      .then((r) => {
        cache.set(tela, r.data ?? null)
        if (!cancelado) setPagina(r.data ?? null)
      })
      .catch((erro) => {
        if (erro instanceof ApiError && erro.status === 404) cache.set(tela, null)
      })
    return () => {
      cancelado = true
    }
  }, [tela])

  const fechar = useCallback(() => setAberto(false), [])

  useEffect(() => {
    if (aberto) painel.current?.focus()
  }, [aberto])

  useEffect(() => {
    function atalho(evento: KeyboardEvent) {
      if (evento.key === 'Escape' && aberto) {
        setAberto(false)
        return
      }
      const ehAtalho = evento.key === 'F1' || (evento.key === '?' && !evento.ctrlKey && !evento.metaKey && !evento.altKey)
      if (!ehAtalho || !pagina) return
      const alvo = evento.target as HTMLElement | null
      if (evento.key === '?' && alvo && (alvo.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(alvo.tagName))) return
      evento.preventDefault()
      setAberto((v) => !v)
    }
    window.addEventListener('keydown', atalho)
    return () => window.removeEventListener('keydown', atalho)
  }, [aberto, pagina])

  if (!pagina) return null

  return (
    <>
      <button
        type="button"
        className="admin-icon-btn"
        title="Ajuda desta tela (F1)"
        aria-label="Abrir a ajuda desta tela"
        aria-expanded={aberto}
        aria-controls="ajuda-contextual"
        onClick={() => setAberto((v) => !v)}
      >
        <CircleHelp size={18} />
      </button>

      {aberto &&
        createPortal(
          <>
            <div className="ajuda-fundo" onClick={fechar} />
            <aside
              id="ajuda-contextual"
              ref={painel}
              className="ajuda-painel"
              role="dialog"
              aria-labelledby="ajuda-contextual-titulo"
              tabIndex={-1}
            >
              <header className="ajuda-cabecalho">
                <p className="ajuda-rotulo">Ajuda</p>
                <button type="button" className="ajuda-fechar" aria-label="Fechar a ajuda" title="Fechar (Esc)" onClick={fechar}>
                  <X size={18} />
                </button>
              </header>
              <h2 id="ajuda-contextual-titulo">{pagina.titulo}</h2>
              {pagina.resumo && <p className="doc-resumo">{pagina.resumo}</p>}
              <DocConteudo html={pagina.html} aoNavegar={fechar} />
              <footer className="ajuda-rodape">
                <Link to={caminhoDaDocumentacao(pagina.slug)} onClick={fechar}>
                  <BookMarked size={15} /> Abrir na documentação
                </Link>
                <button type="button" className="btn btn--ghost" onClick={fechar}>
                  Fechar
                </button>
              </footer>
            </aside>
          </>,
          document.body,
        )}
    </>
  )
}
