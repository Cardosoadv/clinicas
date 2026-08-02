import { useEffect, useState } from 'react'
import { fetchClientes } from '../clientes/api'
import type { Cliente } from '../clientes/types'
import { useClickOutside } from '../../hooks/useClickOutside'

interface ClientePickerProps {
  value: string
  onChange: (clienteId: string) => void
  error?: string
}

/** Combobox de busca de tutor (cliente) pelo nome — carrega os clientes ativos uma vez e filtra localmente. */
export function ClientePicker({ value, onChange, error }: ClientePickerProps) {
  const [clientes, setClientes] = useState<Cliente[]>([])
  const [query, setQuery] = useState('')
  const [isOpen, setIsOpen] = useState(false)
  const [isLoading, setIsLoading] = useState(true)

  const containerRef = useClickOutside<HTMLDivElement>(() => setIsOpen(false))

  useEffect(() => {
    fetchClientes('ativos', '', '')
      .then((res) => setClientes(res.data ?? []))
      .catch(() => setClientes([]))
      .finally(() => setIsLoading(false))
  }, [])

  // Preenche o campo com o nome do tutor já vinculado (edição) assim que a lista carrega.
  useEffect(() => {
    if (!value) return
    const match = clientes.find((cliente) => String(cliente.id) === value)
    if (match) setQuery(match.nome)
  }, [value, clientes])

  const term = query.trim().toLowerCase()
  const filtered = term ? clientes.filter((cliente) => cliente.nome.toLowerCase().includes(term)) : clientes

  function handleSelect(cliente: Cliente) {
    onChange(String(cliente.id))
    setQuery(cliente.nome)
    setIsOpen(false)
  }

  return (
    <div className="cliente-picker" ref={containerRef}>
      <input
        type="text"
        placeholder={isLoading ? 'Carregando tutores...' : 'Buscar tutor pelo nome...'}
        value={query}
        onChange={(event) => {
          setQuery(event.target.value)
          setIsOpen(true)
          if (value) onChange('')
        }}
        onFocus={() => setIsOpen(true)}
        disabled={isLoading}
        autoComplete="off"
        aria-invalid={Boolean(error)}
      />

      {isOpen && !isLoading && (
        <div className="cliente-picker__dropdown">
          {filtered.length === 0 && <p className="cliente-picker__hint">Nenhum tutor encontrado.</p>}

          {filtered.slice(0, 50).map((cliente) => (
            <button
              key={cliente.id}
              type="button"
              className="cliente-picker__option"
              onClick={() => handleSelect(cliente)}
            >
              <span>{cliente.nome}</span>
              <span className="cliente-picker__option-doc">{cliente.cnpj || cliente.cpf || cliente.emails || ''}</span>
            </button>
          ))}
        </div>
      )}

      {error && <span className="field-error">{error}</span>}
    </div>
  )
}
