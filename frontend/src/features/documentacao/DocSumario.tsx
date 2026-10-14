import { Link } from 'react-router-dom'
import { caminhoDaDocumentacao } from './rotas'
import type { DocSumarioItem } from './types'

interface Props {
  itens: DocSumarioItem[]
  slug: string
  ativa: string | null
}

/** "Nesta página": os títulos h2 e h3 da página aberta. */
export function DocSumario({ itens, slug, ativa }: Props) {
  if (!itens.length) return null

  return (
    <nav className="doc-sumario" aria-label="Nesta página">
      <p className="doc-sumario-rotulo">Nesta página</p>
      <ul>
        {itens.map((item) => (
          <li key={item.ancora} className={`nivel-${item.nivel}`}>
            <Link
              to={caminhoDaDocumentacao(slug, item.ancora)}
              className={item.ancora === ativa ? 'ativo' : undefined}
              aria-current={item.ancora === ativa ? 'location' : undefined}
            >
              {item.texto}
            </Link>
          </li>
        ))}
      </ul>
    </nav>
  )
}
