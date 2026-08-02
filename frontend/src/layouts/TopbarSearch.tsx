import { Search, X } from 'lucide-react'
import { useEffect, useState, type KeyboardEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { fetchClientes } from '../features/clientes/api'
import type { Cliente } from '../features/clientes/types'
import { fetchPacientes } from '../features/pacientes/api'
import type { Paciente } from '../features/pacientes/types'
import { useClickOutside } from '../hooks/useClickOutside'

type ResultKind = 'cliente' | 'paciente'
interface SearchResult {
  kind: ResultKind
  id: number
  nome: string
  meta: string
}

/** Busca rápida global na topbar — cobre Clientes e Pacientes. */
export function TopbarSearch() {
  const navigate = useNavigate()

  const [query, setQuery] = useState('')
  const [clienteResults, setClienteResults] = useState<Cliente[] | null>(null)
  const [pacienteResults, setPacienteResults] = useState<Paciente[] | null>(null)
  const [isLoading, setIsLoading] = useState(false)
  const [isOpen, setIsOpen] = useState(false)

  const containerRef = useClickOutside<HTMLDivElement>(() => setIsOpen(false))

  useEffect(() => {
    const term = query.trim()
    if (term.length < 2) {
      setClienteResults(null)
      setPacienteResults(null)
      setIsLoading(false)
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
      ])
        .then(([clientes, pacientes]) => {
          setClienteResults(clientes)
          setPacienteResults(pacientes)
        })
        .finally(() => setIsLoading(false))
    }, 300)

    return () => clearTimeout(timeout)
  }, [query])

  function goToClientesSearch(term: string) {
    // status=todos porque a busca da topbar procura em ativos e inativos.
    navigate(`/clientes?search=${encodeURIComponent(term)}&status=todos`)
    reset()
  }

  function goToPacientesSearch(term: string) {
    navigate(`/pacientes?search=${encodeURIComponent(term)}`)
    reset()
  }

  function reset() {
    setQuery('')
    setClienteResults(null)
    setPacienteResults(null)
    setIsOpen(false)
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
  ]

  function handleSelect(result: SearchResult) {
    if (result.kind === 'cliente') {
      goToClientesSearch(result.nome)
    } else {
      goToPacientesSearch(result.nome)
    }
  }

  function handleKeyDown(event: KeyboardEvent<HTMLInputElement>) {
    if (event.key === 'Escape') {
      setIsOpen(false)
      event.currentTarget.blur()
    } else if (event.key === 'Enter' && query.trim()) {
      event.preventDefault()
      if (results.length > 0) {
        handleSelect(results[0])
      } else {
        goToClientesSearch(query.trim())
      }
    }
  }

  const showDropdown = isOpen && query.trim().length >= 2

  return (
    <div className="admin-topbar__search" ref={containerRef}>
      <Search size={16} />
      <input
        type="search"
        placeholder="Buscar cliente ou paciente..."
        aria-label="Buscar cliente ou paciente"
        value={query}
        onChange={(event) => {
          setQuery(event.target.value)
          setIsOpen(true)
        }}
        onFocus={() => setIsOpen(true)}
        onKeyDown={handleKeyDown}
      />
      {query && (
        <button
          type="button"
          className="topbar-search__clear"
          aria-label="Limpar busca"
          onClick={() => {
            setQuery('')
            setClienteResults(null)
            setPacienteResults(null)
          }}
        >
          <X size={14} />
        </button>
      )}

      {showDropdown && (
        <div className="topbar-search__dropdown">
          {isLoading && <p className="topbar-search__hint">Buscando...</p>}

          {!isLoading && results.length === 0 && <p className="topbar-search__hint">Nenhum resultado encontrado.</p>}

          {!isLoading &&
            results.slice(0, 8).map((result) => (
              <button
                key={`${result.kind}-${result.id}`}
                type="button"
                className="topbar-search__result"
                onClick={() => handleSelect(result)}
              >
                <span className="topbar-search__result-name">
                  {result.nome}
                  <span className="topbar-search__result-tag">
                    {result.kind === 'cliente' ? 'Cliente' : 'Paciente'}
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
        </div>
      )}
    </div>
  )
}
