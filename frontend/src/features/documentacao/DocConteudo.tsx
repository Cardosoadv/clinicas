import { forwardRef, useMemo, type MouseEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { prepararHtml } from './api'
import { destinoInterno } from './rotas'

interface Props {
  /** HTML já sanitizado pelo backend (lista branca de tags e atributos). */
  html: string
  className?: string
  /** Chamado antes de navegar por um link interno (ex.: fechar o painel de ajuda). */
  aoNavegar?: (destino: string) => void
}

/**
 * Corpo de uma página da documentação. Os links internos (outras páginas e
 * telas do sistema) navegam pelo React Router, sem recarregar a aplicação.
 */
export const DocConteudo = forwardRef<HTMLDivElement, Props>(function DocConteudo({ html, className, aoNavegar }, ref) {
  const navigate = useNavigate()
  const conteudo = useMemo(() => ({ __html: prepararHtml(html) }), [html])

  function aoClicar(evento: MouseEvent<HTMLDivElement>) {
    if (evento.defaultPrevented || evento.button !== 0 || evento.ctrlKey || evento.metaKey || evento.shiftKey) return
    const link = (evento.target as HTMLElement).closest('a')
    const destino = destinoInterno(link?.getAttribute('href') ?? null)
    if (!link || !destino) return
    evento.preventDefault()
    aoNavegar?.(destino)
    navigate(destino)
  }

  return <div ref={ref} className={`doc-corpo ${className ?? ''}`} dangerouslySetInnerHTML={conteudo} onClick={aoClicar} />
})
