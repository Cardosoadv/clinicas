import { useEffect, useState } from 'react'
import { searchPacientesLookup } from '../features/agenda/api'
import type { PacienteLookup } from '../features/agenda/types'
import { useClickOutside } from '../hooks/useClickOutside'

interface PacientePickerProps {
  value: string
  displayName?: string
  onChange: (pacienteId: string, pacienteNome: string) => void
  error?: string
}

/** Combobox de busca de paciente pelo nome (busca no servidor, com debounce). */
export function PacientePicker({ value, displayName, onChange, error }: PacientePickerProps) {
  const [query, setQuery] = useState(displayName ?? '')
  const [results, setResults] = useState<PacienteLookup[]>([])
  const [isOpen, setIsOpen] = useState(false)
  const [isLoading, setIsLoading] = useState(false)

  const containerRef = useClickOutside<HTMLDivElement>(() => setIsOpen(false))

  useEffect(() => {
    if (displayName) setQuery(displayName)
  }, [displayName])

  useEffect(() => {
    const term = query.trim()
    if (term.length < 2) {
      setResults([])
      return
    }

    setIsLoading(true)
    const timeout = setTimeout(() => {
      searchPacientesLookup(term)
        .then((res) => setResults(res.data ?? []))
        .catch(() => setResults([]))
        .finally(() => setIsLoading(false))
    }, 250)

    return () => clearTimeout(timeout)
  }, [query])

  function handleSelect(paciente: PacienteLookup) {
    onChange(String(paciente.paciente_id), paciente.paciente_nome)
    setQuery(paciente.paciente_nome)
    setIsOpen(false)
  }

  return (
    <div className="cliente-picker" ref={containerRef}>
      <input
        type="text"
        placeholder="Buscar paciente pelo nome..."
        value={query}
        onChange={(event) => {
          setQuery(event.target.value)
          setIsOpen(true)
          if (value) onChange('', '')
        }}
        onFocus={() => setIsOpen(true)}
        autoComplete="off"
        aria-invalid={Boolean(error)}
      />

      {isOpen && query.trim().length >= 2 && (
        <div className="cliente-picker__dropdown">
          {isLoading && <p className="cliente-picker__hint">Buscando...</p>}

          {!isLoading && results.length === 0 && <p className="cliente-picker__hint">Nenhum paciente encontrado.</p>}

          {!isLoading &&
            results.map((paciente) => (
              <button
                key={paciente.paciente_id}
                type="button"
                className="cliente-picker__option"
                onClick={() => handleSelect(paciente)}
              >
                <span>
                  {paciente.paciente_avatar || '🐾'} {paciente.paciente_nome}
                </span>
                <span className="cliente-picker__option-doc">
                  {paciente.pet_resp_nome ? `Tutor: ${paciente.pet_resp_nome}` : ''}
                </span>
              </button>
            ))}
        </div>
      )}

      {error && <span className="field-error">{error}</span>}
    </div>
  )
}
