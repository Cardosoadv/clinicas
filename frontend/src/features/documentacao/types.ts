/**
 * Tipos da API da documentação interna (`api/v1/documentacao/*`).
 * Contrato: docs/api-documentacao.md.
 */

export type DocStatus = 'publicado' | 'rascunho'
export type DocOrigem = 'arquivo' | 'banco'

export interface DocPaginaResumo {
  slug: string
  titulo: string
  resumo: string
  /** Rascunhos só chegam para quem tem documentacao.editar. */
  status?: DocStatus
}

/** Seção do índice. Com `capa`, o próprio `slug` da seção abre a página de capa. */
export interface DocSecao extends DocPaginaResumo {
  capa: boolean
  paginas: DocPaginaResumo[]
}

export interface DocSumarioItem {
  nivel: 2 | 3
  texto: string
  ancora: string
}

export interface DocVizinha {
  slug: string
  titulo: string
}

export interface DocPagina {
  slug: string
  secao: string
  capa: boolean
  origem: DocOrigem
  status: DocStatus
  /** Id da página no banco (null para página de arquivo). */
  id: number | null
  titulo: string
  resumo: string
  /** Corpo já sanitizado pelo backend (lista branca de tags e atributos). */
  html: string
  sumario: DocSumarioItem[]
  anterior: DocVizinha | null
  proxima: DocVizinha | null
  /** ISO 8601 */
  atualizado_em: string
}

export interface DocResultadoBusca {
  slug: string
  titulo: string
  /** Título da seção. */
  secao: string
  /** Texto puro, sem HTML. */
  trecho: string
}

// ---------------------------------------------------------------- edição (documentacao.editar)

/** Item da lista de edição: páginas do banco e de arquivo. */
export interface DocItemEdicao {
  id: number | null
  slug: string
  titulo: string
  secao: string
  origem: DocOrigem
  status: DocStatus
  /** Há arquivo de mesmo slug (que a página do banco substitui ao ser publicada). */
  tem_arquivo: boolean
  atualizado_em: string | null
}

/** Campos editáveis de uma página. */
export interface DocEntradaPagina {
  slug: string
  titulo: string
  resumo: string
  ordem: number | null
  permissao: string
  telas: string[]
  palavras_chave: string[]
  markdown: string
}

export interface DocPaginaEdicao extends Omit<DocEntradaPagina, 'permissao'> {
  id: number
  permissao: string | null
  status: DocStatus
  tem_arquivo: boolean
  publicado_em: string | null
  criado_em: string | null
  atualizado_em: string | null
}

export interface DocPreview {
  html: string
  sumario: DocSumarioItem[]
  avisos: string[]
}

export interface DocImagemEnviada {
  caminho: string
  /** Pronto para colar: `![Descreva a imagem](img/arquivo.png)`. */
  markdown: string
  largura: number
  altura: number
}
