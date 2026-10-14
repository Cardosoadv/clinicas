/**
 * Destaque dos termos da busca da documentação. A API devolve o
 * trecho em texto puro; aqui ele é escapado e os termos ganham `<mark>`,
 * sem diferenciar maiúsculas nem acentos, como a busca do backend.
 */

export const BUSCA_MINIMO = 2

const escapar = (texto: string) =>
  texto.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]!)

/** Um caractere sem acento e em minúscula (mantém o comprimento para mapear posições). */
const normalizarCaractere = (c: string) => c.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase().charAt(0) || c

export function termosDaBusca(consulta: string): string[] {
  const termos = consulta
    .split(/\s+/)
    .map((t) => [...t].map(normalizarCaractere).join(''))
    .filter((t) => t.length >= BUSCA_MINIMO)
  return [...new Set(termos)]
}

/** HTML seguro do texto com os termos dentro de `<mark>`. */
export function destacarTermos(texto: string, consulta: string): string {
  const termos = termosDaBusca(consulta)
  const caracteres = [...texto]
  if (!termos.length || !caracteres.length) return escapar(texto)

  const plano = caracteres.map(normalizarCaractere).join('')
  const marcado = new Array<boolean>(caracteres.length).fill(false)
  for (const termo of termos) {
    for (let i = plano.indexOf(termo); i !== -1; i = plano.indexOf(termo, i + termo.length)) {
      marcado.fill(true, i, i + termo.length)
    }
  }

  let saida = ''
  let aberto = false
  caracteres.forEach((c, i) => {
    if (marcado[i] && !aberto) { saida += '<mark>'; aberto = true }
    if (!marcado[i] && aberto) { saida += '</mark>'; aberto = false }
    saida += escapar(c)
  })
  return aberto ? `${saida}</mark>` : saida
}
