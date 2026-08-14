import { useEffect } from 'react'

/** Chama `onEscape` ao pressionar a tecla Escape. */
export function useEscapeKey(onEscape: () => void) {
  useEffect(() => {
    function handleKeyDown(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        onEscape()
      }
    }
    document.addEventListener('keydown', handleKeyDown)
    return () => document.removeEventListener('keydown', handleKeyDown)
  }, [onEscape])
}
