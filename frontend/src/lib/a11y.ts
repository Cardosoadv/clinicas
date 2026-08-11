import type { KeyboardEvent } from 'react'

/** Aciona `callback` ao pressionar Enter ou Espaço, para uso em elementos com role="button". */
export function handleActivationKeyDown(callback: () => void) {
  return (event: KeyboardEvent) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault()
      callback()
    }
  }
}
