import { House } from 'lucide-react'
import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { caminhoDaDocumentacao } from './rotas'
import type { DocSecao } from './types'

interface Props {
  indice: DocSecao[]
  slugAtual: string | null
  topo?: ReactNode
  aoNavegar?: () => void
}

/** Índice lateral: seções e páginas. */
export function DocArvore({ indice, slugAtual, topo, aoNavegar }: Props) {
  const classe = (ativo: boolean) => (ativo ? 'ativo' : undefined)
  const atual = (ativo: boolean) => (ativo ? 'page' : undefined)

  return (
    <nav className="doc-arvore" aria-label="Índice da documentação">
      {topo}
      <Link to={caminhoDaDocumentacao()} className={`doc-arvore-inicio ${classe(!slugAtual) ?? ''}`} aria-current={atual(!slugAtual)} onClick={aoNavegar}>
        <House size={15} /> Início
      </Link>
      {indice.map((secao) => (
        <div key={secao.slug} className="doc-arvore-secao">
          {secao.capa ? (
            <Link
              to={caminhoDaDocumentacao(secao.slug)}
              className={`doc-arvore-titulo ${classe(slugAtual === secao.slug) ?? ''}`}
              aria-current={atual(slugAtual === secao.slug)}
              onClick={aoNavegar}
            >
              {secao.titulo}
            </Link>
          ) : (
            <span className="doc-arvore-titulo">{secao.titulo}</span>
          )}
          <ul>
            {secao.paginas.map((p) => (
              <li key={p.slug}>
                <Link
                  to={caminhoDaDocumentacao(p.slug)}
                  className={classe(slugAtual === p.slug)}
                  aria-current={atual(slugAtual === p.slug)}
                  onClick={aoNavegar}
                >
                  {p.titulo}
                  {p.status === 'rascunho' && <span className="doc-arvore-rascunho"> · rascunho</span>}
                </Link>
              </li>
            ))}
          </ul>
        </div>
      ))}
    </nav>
  )
}
