import { Search, X } from 'lucide-react'
import { useEffect, useRef, useState, type KeyboardEvent } from 'react'
import { useLocation, useNavigate, useSearchParams } from 'react-router-dom'
import { fetchClientes } from '../features/clientes/api'
import type { Cliente } from '../features/clientes/types'
import { fetchPacientes } from '../features/pacientes/api'
import type { Paciente } from '../features/pacientes/types'
import { fetchServicos } from '../features/servicos/api'
import type { Servico } from '../features/servicos/types'
import { useClickOutside } from '../hooks/useClickOutside'

type ResultKind = 'cliente' | 'paciente' | 'servico'
interface SearchResult {
  kind: ResultKind
  id: number
  nome: string
  meta: string
}

/** Busca rápida global na topbar — cobre Clientes, Pacientes e Serviços. */
export function TopbarSearch() {
  const navigate = useNavigate()
  const location = useLocation()
  const [searchParams, setSearchParams] = useSearchParams()

  const isServicos = location.pathname.startsWith('/servicos')
  const isPacientes = location.pathname.startsWith('/pacientes')
  const isClientes = location.pathname.startsWith('/clientes')

  const [query, setQuery] = useState('')
  const [clienteResults, setClienteResults] = useState<Cliente[] | null>(null)
  const [pacienteResults, setPacienteResults] = useState<Paciente[] | null>(null)
  const [servicoResults, setServicoResults] = useState<Servico[] | null>(null)
  const [isLoading, setIsLoading] = useState(false)
  const [isOpen, setIsOpen] = useState(false)
  const [selectedIndex, setSelectedIndex] = useState<number>(-1)

  const containerRef = useClickOutside<HTMLDivElement>(() => setIsOpen(false))
  const lastLocationRef = useRef<string>(location.pathname)

  // Sincroniza a busca da topbar com o parâmetro ?search= quando estiver no módulo de serviços
  useEffect(() => {
    if (isServicos) {
      const urlSearch = searchParams.get('search') ?? ''
      setQuery(urlSearch)
    } else if (lastLocationRef.current !== location.pathname) {
      if (lastLocationRef.current.startsWith('/servicos')) {
        setQuery('')
      }
    }
    lastLocationRef.current = location.pathname
  }, [location.pathname, searchParams, isServicos])

  useEffect(() => {
    const term = query.trim()
    if (term.length < 2) {
      setClienteResults(null)
      setPacienteResults(null)
      setServicoResults(null)
      setIsLoading(false)
      setSelectedIndex(-1)
      return
    }

    setIsLoading(true)
    const timeout = setTimeout(() => {
      Promise.all([
        fetchClientes('todos', '', term)
          .then((res) => res.data ?? [])
          .catch(() => []),
        fetchPacientes('', '', term)
          .then((res) => res.data ?? [])
          .catch(() => []),
        fetchServicos(term)
          .then((res) => res.data ?? [])
          .catch(() => []),
      ])
        .then(([clientes, pacientes, servicos]) => {
          setClienteResults(clientes)
          setPacienteResults(pacientes)
          setServicoResults(servicos)
          setSelectedIndex(-1)
        })
        .finally(() => setIsLoading(false))
    }, 300)

    return () => clearTimeout(timeout)
  }, [query])

  function handleInputChange(text: string) {
    setQuery(text)
    setIsOpen(true)

    if (isServicos) {
      const next = new URLSearchParams(searchParams)
      if (text.trim()) {
        next.set('search', text.trim())
      } else {
        next.delete('search')
      }
      setSearchParams(next, { replace: true })
    }
  }

  function handleClear() {
    setQuery('')
    setClienteResults(null)
    setPacienteResults(null)
    setServicoResults(null)
    setSelectedIndex(-1)
    if (isServicos) {
      const next = new URLSearchParams(searchParams)
      next.delete('search')
      setSearchParams(next, { replace: true })
    }
  }

  function goToClientesSearch(term: string) {
    // status=todos porque a busca da topbar procura em ativos e inativos.
    navigate(`/clientes?search=${encodeURIComponent(term)}&status=todos`)
    reset()
  }

  function goToPacientesSearch(term: string) {
    navigate(`/pacientes?search=${encodeURIComponent(term)}`)
    reset()
  }

  function goToServicosSearch(term: string) {
    navigate(`/servicos?search=${encodeURIComponent(term)}`)
    reset()
  }

  function reset() {
    setQuery('')
    setClienteResults(null)
    setPacienteResults(null)
    setServicoResults(null)
    setIsOpen(false)
    setSelectedIndex(-1)
  }

  const results: SearchResult[] = [
    ...(clienteResults ?? []).map((cliente) => ({
      kind: 'cliente' as const,
      id: cliente.id,
      nome: cliente.nome,
      meta: cliente.cnpj || cliente.cpf || cliente.emails || 'Sem documento',
    })),
    ...(pacienteResults ?? []).map((paciente) => ({
      kind: 'paciente' as const,
      id: paciente.paciente_id,
      nome: paciente.paciente_nome,
      meta: paciente.pet_resp_nome ? `Tutor: ${paciente.pet_resp_nome}` : paciente.paciente_especie || '',
    })),
    ...(servicoResults ?? []).map((servico) => ({
      kind: 'servico' as const,
      id: servico.ser_id,
      nome: `${servico.ser_icone || '🐾'} ${servico.ser_nome}`,
      meta: servico.ser_valor
        ? servico.ser_valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
        : (servico.ser_descricao || 'Serviço'),
    })),
  ]

  const visibleResults = results.slice(0, 8)

  function handleSelect(result: SearchResult) {
    if (result.kind === 'cliente') {
      goToClientesSearch(result.nome)
    } else if (result.kind === 'paciente') {
      goToPacientesSearch(result.nome)
    } else {
      // Remove o emoji inicial para passar o termo limpo de busca
      const cleanName = result.nome.replace(/^[^\s]+\s/, '')
      goToServicosSearch(cleanName)
    }
  }

  function handleKeyDown(event: KeyboardEvent<HTMLInputElement>) {
    if (event.key === 'Escape') {
      setIsOpen(false)
      setSelectedIndex(-1)
      event.currentTarget.blur()
    } else if (event.key === 'ArrowDown') {
      if (!isOpen) setIsOpen(true)
      if (visibleResults.length > 0) {
        event.preventDefault()
        setSelectedIndex((prev) => (prev < visibleResults.length - 1 ? prev + 1 : 0))
      }
    } else if (event.key === 'ArrowUp') {
      if (visibleResults.length > 0) {
        event.preventDefault()
        setSelectedIndex((prev) => (prev > 0 ? prev - 1 : visibleResults.length - 1))
      }
    } else if (event.key === 'Enter' && query.trim()) {
      event.preventDefault()
      if (selectedIndex >= 0 && selectedIndex < visibleResults.length) {
        handleSelect(visibleResults[selectedIndex])
      } else if (isServicos) {
        setIsOpen(false)
        goToServicosSearch(query.trim())
      } else if (results.length > 0) {
        handleSelect(results[0])
      } else {
        goToClientesSearch(query.trim())
      }
    }
  }

  const showDropdown = isOpen && query.trim().length >= 2

  const placeholder = isServicos
    ? 'Buscar serviços por nome ou descrição...'
    : isPacientes
    ? 'Buscar paciente ou tutor...'
    : isClientes
    ? 'Buscar cliente por nome ou documento...'
    : 'Buscar cliente, paciente ou serviço...'

  const ariaLabel = isServicos
    ? 'Buscar serviços'
    : isPacientes
    ? 'Buscar pacientes'
    : isClientes
    ? 'Buscar clientes'
    : 'Buscar cliente, paciente ou serviço'

  return (
    <div className="admin-topbar__search" ref={containerRef}>
      <Search size={16} />
      <input
        type="search"
        placeholder={placeholder}
        aria-label={ariaLabel}
        role="combobox"
        aria-expanded={showDropdown}
        aria-autocomplete="list"
        aria-controls="topbar-search-listbox"
        aria-activedescendant={
          selectedIndex >= 0 && visibleResults[selectedIndex]
            ? `topbar-search-option-${visibleResults[selectedIndex].kind}-${visibleResults[selectedIndex].id}`
            : undefined
        }
        value={query}
        onChange={(event) => handleInputChange(event.target.value)}
        onFocus={() => setIsOpen(true)}
        onKeyDown={handleKeyDown}
      />
      {query && (
        <button
          type="button"
          className="topbar-search__clear"
          aria-label="Limpar busca"
          onClick={handleClear}
        >
          <X size={14} />
        </button>
      )}

      {showDropdown && (
        <div className="topbar-search__dropdown" id="topbar-search-listbox" role="listbox">
          {isLoading && <p className="topbar-search__hint">Buscando...</p>}

          {!isLoading && results.length === 0 && <p className="topbar-search__hint">Nenhum resultado encontrado.</p>}

          {!isLoading &&
            visibleResults.map((result, idx) => (
              <button
                key={`${result.kind}-${result.id}`}
                id={`topbar-search-option-${result.kind}-${result.id}`}
                role="option"
                aria-selected={selectedIndex === idx}
                type="button"
                className={`topbar-search__result${selectedIndex === idx ? ' topbar-search__result--active' : ''}`}
                onClick={() => handleSelect(result)}
              >
                <span className="topbar-search__result-name">
                  {result.nome}
                  <span className="topbar-search__result-tag">
                    {result.kind === 'cliente' ? 'Cliente' : result.kind === 'paciente' ? 'Paciente' : 'Serviço'}
                  </span>
                </span>
                <span className="topbar-search__result-meta">{result.meta}</span>
              </button>
            ))}

          {!isLoading && (clienteResults?.length ?? 0) > 0 && (
            <button type="button" className="topbar-search__seeall" onClick={() => goToClientesSearch(query.trim())}>
              Ver todos os clientes para “{query.trim()}”
            </button>
          )}

          {!isLoading && (pacienteResults?.length ?? 0) > 0 && (
            <button type="button" className="topbar-search__seeall" onClick={() => goToPacientesSearch(query.trim())}>
              Ver todos os pacientes para “{query.trim()}”
            </button>
          )}

          {!isLoading && (servicoResults?.length ?? 0) > 0 && (
            <button type="button" className="topbar-search__seeall" onClick={() => goToServicosSearch(query.trim())}>
              Ver todos os serviços para “{query.trim()}”
            </button>
          )}
        </div>
      )}
    </div>
  )
}
