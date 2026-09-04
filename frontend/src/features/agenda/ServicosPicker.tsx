import { Search, X } from 'lucide-react'
import { useMemo, useRef, useState, type KeyboardEvent } from 'react'
import { useClickOutside } from '../../hooks/useClickOutside'
import type { ServicoOption } from './types'

interface ServicosPickerProps {
  id?: string
  selectedIds: string[]
  servicos: ServicoOption[]
  onChange: (selectedIds: string[]) => void
  error?: string
}

function formatCurrency(value: number): string {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function normalize(text: string): string {
  return text
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
}

export function ServicosPicker({
  id = 'servicos-picker-input',
  selectedIds,
  servicos,
  onChange,
  error,
}: ServicosPickerProps) {
  const [query, setQuery] = useState('')
  const [isOpen, setIsOpen] = useState(false)
  const [selectedIndex, setSelectedIndex] = useState(-1)
  const inputRef = useRef<HTMLInputElement>(null)

  const containerRef = useClickOutside<HTMLDivElement>(() => {
    setIsOpen(false)
    setSelectedIndex(-1)
  })

  // Serviços atualmente selecionados em ordem
  const selectedServicos = useMemo(() => {
    return selectedIds
      .map((idStr) => servicos.find((s) => String(s.ser_id) === idStr))
      .filter((s): s is ServicoOption => s !== undefined)
  }, [selectedIds, servicos])

  // Serviços disponíveis para adicionar (não selecionados e filtrados pelo query)
  const availableServicos = useMemo(() => {
    const unselected = servicos.filter((s) => !selectedIds.includes(String(s.ser_id)))
    const term = normalize(query.trim())
    if (!term) return unselected
    return unselected.filter((s) => normalize(s.ser_nome).includes(term))
  }, [servicos, selectedIds, query])

  function handleSelect(servico: ServicoOption) {
    const next = [...selectedIds, String(servico.ser_id)]
    onChange(next)
    setQuery('')
    setSelectedIndex(-1)
    inputRef.current?.focus()
  }

  function handleRemove(idStr: string) {
    const next = selectedIds.filter((id) => id !== idStr)
    onChange(next)
  }

  function handleKeyDown(event: KeyboardEvent<HTMLInputElement>) {
    if (event.key === 'Escape') {
      setIsOpen(false)
      setSelectedIndex(-1)
    } else if (event.key === 'ArrowDown') {
      if (!isOpen) {
        setIsOpen(true)
      } else if (availableServicos.length > 0) {
        event.preventDefault()
        setSelectedIndex((prev) => (prev < availableServicos.length - 1 ? prev + 1 : 0))
      }
    } else if (event.key === 'ArrowUp') {
      if (availableServicos.length > 0) {
        event.preventDefault()
        setSelectedIndex((prev) => (prev > 0 ? prev - 1 : availableServicos.length - 1))
      }
    } else if (event.key === 'Enter') {
      if (isOpen && selectedIndex >= 0 && selectedIndex < availableServicos.length) {
        event.preventDefault()
        handleSelect(availableServicos[selectedIndex])
      } else if (isOpen && availableServicos.length === 1) {
        event.preventDefault()
        handleSelect(availableServicos[0])
      }
    } else if (event.key === 'Backspace' && query === '' && selectedIds.length > 0) {
      // Remove o último serviço selecionado se apagar com campo vazio
      handleRemove(selectedIds[selectedIds.length - 1])
    }
  }

  return (
    <div className="servicos-picker" ref={containerRef}>
      {/* Chips de serviços selecionados */}
      {selectedServicos.length > 0 && (
        <div className="servicos-picker__chips">
          {selectedServicos.map((servico) => (
            <span key={servico.ser_id} className="servico-chip">
              <span className="servico-chip__icon">{servico.ser_icone || '🐾'}</span>
              <span className="servico-chip__name">{servico.ser_nome}</span>
              {servico.ser_valor > 0 && (
                <span className="servico-chip__price">{formatCurrency(servico.ser_valor)}</span>
              )}
              <button
                type="button"
                className="servico-chip__remove"
                onClick={() => handleRemove(String(servico.ser_id))}
                title={`Remover ${servico.ser_nome}`}
                aria-label={`Remover serviço ${servico.ser_nome}`}
              >
                <X size={13} />
              </button>
            </span>
          ))}
        </div>
      )}

      {/* Campo de autocompletar */}
      <div className="servicos-picker__input-wrapper">
        <Search size={15} className="servicos-picker__search-icon" />
        <input
          ref={inputRef}
          id={id}
          type="text"
          placeholder={
            selectedServicos.length === 0
              ? 'Buscar e selecionar serviços...'
              : 'Adicionar mais um serviço...'
          }
          value={query}
          onChange={(event) => {
            setQuery(event.target.value)
            setIsOpen(true)
            setSelectedIndex(0)
          }}
          onFocus={() => setIsOpen(true)}
          onKeyDown={handleKeyDown}
          autoComplete="off"
          aria-invalid={Boolean(error)}
        />
        {query && (
          <button
            type="button"
            className="servicos-picker__clear-btn"
            onClick={() => {
              setQuery('')
              inputRef.current?.focus()
            }}
            aria-label="Limpar texto"
          >
            <X size={13} />
          </button>
        )}
      </div>

      {/* Dropdown de opções */}
      {isOpen && (
        <div className="servicos-picker__dropdown" role="listbox">
          {servicos.length === 0 && (
            <p className="servicos-picker__hint">Nenhum serviço cadastrado no sistema.</p>
          )}

          {servicos.length > 0 && availableServicos.length === 0 && (
            <p className="servicos-picker__hint">
              {query.trim()
                ? 'Nenhum serviço encontrado para o termo pesquisado.'
                : 'Todos os serviços disponíveis já foram adicionados.'}
            </p>
          )}

          {availableServicos.map((servico, index) => {
            const isHighlighted = selectedIndex === index
            return (
              <button
                key={servico.ser_id}
                type="button"
                role="option"
                aria-selected={isHighlighted}
                className={`servicos-picker__option${isHighlighted ? ' servicos-picker__option--active' : ''}`}
                onClick={() => handleSelect(servico)}
                onMouseEnter={() => setSelectedIndex(index)}
              >
                <div className="servicos-picker__option-left">
                  <span className="servicos-picker__option-icon">{servico.ser_icone || '🐾'}</span>
                  <span className="servicos-picker__option-name">{servico.ser_nome}</span>
                </div>
                <div className="servicos-picker__option-meta">
                  {servico.ser_valor > 0 && (
                    <span className="servicos-picker__option-price">
                      {formatCurrency(servico.ser_valor)}
                    </span>
                  )}
                </div>
              </button>
            )
          })}
        </div>
      )}

      {error && <span className="field-error">{error}</span>}
    </div>
  )
}
