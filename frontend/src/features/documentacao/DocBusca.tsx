import { Search } from 'lucide-react'
import { useEffect, useRef, useState, type KeyboardEvent } from 'react'
import { buscar } from './api'
import { destacarTermos, termosDaBusca } from './busca'
import type { DocResultadoBusca } from './types'

interface Props {
  aoEscolher: (slug: string) => void
}

/** Busca na documentação, com navegação pelo teclado. Atalho: `/`. */
export function DocBusca({ aoEscolher }: Props) {
  const [consulta, setConsulta] = useState('')
  /** Consulta que gerou os resultados exibidos (o destaque usa esta, não a que está sendo digitada). */
  const [consultaFeita, setConsultaFeita] = useState('')
  const [resultados, setResultados] = useState<DocResultadoBusca[]>([])
  const [buscando, setBuscando] = useState(false)
  const [falha, setFalha] = useState('')
  const [aberta, setAberta] = useState(false)
  const [ativo, setAtivo] = useState(-1)
  const campo = useRef<HTMLInputElement>(null)
  const pedido = useRef(0)

  const temConsulta = termosDaBusca(consulta).length > 0

  // Busca 300 ms depois da última tecla; respostas atrasadas são descartadas.
  useEffect(() => {
    const texto = consulta.trim()
    const meu = ++pedido.current
    if (!termosDaBusca(texto).length) {
      setResultados([])
      setBuscando(false)
      setAberta(false)
      return
    }
    setBuscando(true)
    setFalha('')
    setAberta(true)
    const espera = window.setTimeout(() => {
      buscar(texto)
        .then((resposta) => {
          if (meu !== pedido.current) return
          const lista = resposta.data ?? []
          setResultados(lista)
          setConsultaFeita(texto)
          setAtivo(lista.length ? 0 : -1)
        })
        .catch(() => {
          if (meu !== pedido.current) return
          setResultados([])
          setFalha('Não foi possível buscar agora.')
        })
        .finally(() => {
          if (meu === pedido.current) setBuscando(false)
        })
    }, 300)
    return () => window.clearTimeout(espera)
  }, [consulta])

  // `/` foca a busca, exceto quando o usuário já está digitando em algum campo.
  useEffect(() => {
    function atalho(evento: globalThis.KeyboardEvent) {
      if (evento.key !== '/' || evento.ctrlKey || evento.metaKey || evento.altKey) return
      const alvo = evento.target as HTMLElement | null
      if (alvo && (alvo.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(alvo.tagName))) return
      evento.preventDefault()
      campo.current?.focus()
      campo.current?.select()
    }
    window.addEventListener('keydown', atalho)
    return () => window.removeEventListener('keydown', atalho)
  }, [])

  function escolher(i: number) {
    const r = resultados[i]
    if (!r) return
    setAberta(false)
    setConsulta('')
    setResultados([])
    aoEscolher(r.slug)
  }

  function aoTeclar(evento: KeyboardEvent<HTMLDivElement>) {
    const total = resultados.length
    if (evento.key === 'ArrowDown' && total) {
      evento.preventDefault()
      setAberta(true)
      setAtivo((a) => (a + 1) % total)
    } else if (evento.key === 'ArrowUp' && total) {
      evento.preventDefault()
      setAtivo((a) => (a - 1 + total) % total)
    } else if (evento.key === 'Enter' && aberta && ativo >= 0) {
      evento.preventDefault()
      escolher(ativo)
    } else if (evento.key === 'Escape') {
      evento.stopPropagation()
      if (aberta) setAberta(false)
      else setConsulta('')
    }
  }

  return (
    <div className="doc-busca" onKeyDown={aoTeclar}>
      <label className="doc-oculto" htmlFor="doc-busca-campo">
        Buscar na documentação
      </label>
      <div className="doc-busca-campo">
        <Search size={15} aria-hidden="true" />
        <input
          id="doc-busca-campo"
          ref={campo}
          type="search"
          value={consulta}
          placeholder="Buscar na documentação ( / )"
          autoComplete="off"
          role="combobox"
          aria-autocomplete="list"
          aria-controls="doc-busca-resultados"
          aria-expanded={aberta}
          aria-activedescendant={aberta && ativo >= 0 ? `doc-busca-item-${ativo}` : undefined}
          onChange={(e) => setConsulta(e.target.value)}
          onFocus={() => setAberta(temConsulta)}
          onBlur={() => setAberta(false)}
        />
      </div>

      {aberta && (
        <div className="doc-busca-painel">
          {buscando && !resultados.length ? (
            <p className="doc-busca-estado" role="status">Buscando…</p>
          ) : falha ? (
            <p className="doc-busca-estado" role="alert">{falha}</p>
          ) : !resultados.length ? (
            <p className="doc-busca-estado" role="status">Nada encontrado para “{consulta.trim()}”.</p>
          ) : (
            <ul id="doc-busca-resultados" role="listbox" aria-label="Resultados da busca">
              {resultados.map((r, i) => (
                <li
                  key={r.slug}
                  id={`doc-busca-item-${i}`}
                  role="option"
                  aria-selected={i === ativo}
                  className={i === ativo ? 'ativo' : undefined}
                  // mousedown + preventDefault: o campo não perde o foco antes da escolha.
                  onMouseDown={(e) => {
                    e.preventDefault()
                    escolher(i)
                  }}
                  onMouseEnter={() => setAtivo(i)}
                >
                  <span className="doc-busca-titulo" dangerouslySetInnerHTML={{ __html: destacarTermos(r.titulo, consultaFeita) }} />
                  <span className="doc-busca-secao">{r.secao}</span>
                  <span className="doc-busca-trecho" dangerouslySetInnerHTML={{ __html: destacarTermos(r.trecho, consultaFeita) }} />
                </li>
              ))}
            </ul>
          )}
        </div>
      )}
    </div>
  )
}
