import { useEffect, useState, type KeyboardEvent } from 'react'
import { searchPacientesLookup } from '../features/agenda/api'
import type { PacienteLookup } from '../features/agenda/types'
import { useClickOutside } from '../hooks/useClickOutside'
import { PacienteAvatar } from './PacienteAvatar'

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
  const [selectedIndex, setSelectedIndex] = useState<number>(-1)

  const containerRef = useClickOutside<HTMLDivElement>(() => setIsOpen(false))

  useEffect(() => {
    if (displayName) setQuery(displayName)
  }, [displayName])

  useEffect(() => {
    const term = query.trim()
    if (term.length < 2) {
      setResults([])
      setSelectedIndex(-1)
      return
    }

    setIsLoading(true)
    const timeout = setTimeout(() => {
      searchPacientesLookup(term)
        .then((res) => {
          setResults(res.data ?? [])
          setSelectedIndex(-1)
        })
        .catch(() => {
          setResults([])
          setSelectedIndex(-1)
        })
        .finally(() => setIsLoading(false))
    }, 250)

    return () => clearTimeout(timeout)
  }, [query])

  function handleSelect(paciente: PacienteLookup) {
    onChange(String(paciente.paciente_id), paciente.paciente_nome)
    setQuery(paciente.paciente_nome)
    setIsOpen(false)
    setSelectedIndex(-1)
  }

  function handleKeyDown(event: KeyboardEvent<HTMLInputElement>) {
    if (event.key === 'Escape') {
      setIsOpen(false)
      setSelectedIndex(-1)
      event.currentTarget.blur()
    } else if (event.key === 'ArrowDown') {
      if (!isOpen) setIsOpen(true)
      if (results.length > 0) {
        event.preventDefault()
        setSelectedIndex((prev) => (prev < results.length - 1 ? prev + 1 : 0))
      }
    } else if (event.key === 'ArrowUp') {
      if (results.length > 0) {
        event.preventDefault()
        setSelectedIndex((prev) => (prev > 0 ? prev - 1 : results.length - 1))
      }
    } else if (event.key === 'Enter' && query.trim()) {
      if (selectedIndex >= 0 && selectedIndex < results.length) {
        event.preventDefault()
        handleSelect(results[selectedIndex])
      }
    }
  }

  const showDropdown = isOpen && query.trim().length >= 2

  return (
    <div className="cliente-picker" ref={containerRef}>
      <input
        type="text"
        placeholder="Buscar paciente pelo nome..."
        value={query}
        role="combobox"
        aria-expanded={showDropdown}
        aria-haspopup="listbox"
        aria-autocomplete="list"
        aria-controls="paciente-picker-listbox"
        aria-activedescendant={
          selectedIndex >= 0 && results[selectedIndex]
            ? `paciente-picker-option-${results[selectedIndex].paciente_id}`
            : undefined
        }
        onChange={(event) => {
          setQuery(event.target.value)
          setIsOpen(true)
          if (value) onChange('', '')
        }}
        onFocus={() => setIsOpen(true)}
        onKeyDown={handleKeyDown}
        autoComplete="off"
        aria-invalid={Boolean(error)}
      />

      {showDropdown && (
        <div className="cliente-picker__dropdown" id="paciente-picker-listbox" role="listbox" aria-label="Sugestões de pacientes">
          {isLoading && <p className="cliente-picker__hint">Buscando...</p>}

          {!isLoading && results.length === 0 && <p className="cliente-picker__hint">Nenhum paciente encontrado.</p>}

          {!isLoading &&
            results.map((paciente, idx) => (
              <button
                key={paciente.paciente_id}
                id={`paciente-picker-option-${paciente.paciente_id}`}
                role="option"
                aria-selected={selectedIndex === idx}
                type="button"
                className={`cliente-picker__option${selectedIndex === idx ? ' cliente-picker__option--active' : ''}`}
                onClick={() => handleSelect(paciente)}
              >
                <span style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <PacienteAvatar avatar={paciente.paciente_avatar} pacienteId={paciente.paciente_id} alt={paciente.paciente_nome} /> {paciente.paciente_nome}
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
