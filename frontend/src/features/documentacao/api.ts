/**
 * Cliente da API da documentação interna (`api/v1/documentacao/*`).
 * Página inexistente ou invisível para o usuário responde 404.
 * Contrato: docs/api-documentacao.md.
 */
import { api, BASE_URL, type ApiEnvelope } from '../../lib/api'
import type {
  DocEntradaPagina, DocImagemEnviada, DocItemEdicao, DocPagina, DocPaginaEdicao, DocPreview, DocResultadoBusca, DocSecao,
} from './types'

/** `agenda/faturar` → `agenda/faturar`, com cada segmento codificado. */
const caminho = (slug: string) => slug.split('/').map(encodeURIComponent).join('/')

export function obterIndice(): Promise<ApiEnvelope<DocSecao[]>> {
  return api.get<DocSecao[]>('/documentacao')
}

export function obterPagina(slug: string): Promise<ApiEnvelope<DocPagina>> {
  return api.get<DocPagina>(`/documentacao/paginas/${caminho(slug)}`)
}

export function buscar(termo: string): Promise<ApiEnvelope<DocResultadoBusca[]>> {
  return api.get<DocResultadoBusca[]>(`/documentacao/busca?${new URLSearchParams({ q: termo })}`)
}

/** Página de ajuda de uma tela (ver telas.ts); 404 quando a tela não tem. */
export function obterPaginaDaTela(tela: string): Promise<ApiEnvelope<DocPagina>> {
  return api.get<DocPagina>(`/documentacao/tela/${encodeURIComponent(tela)}`)
}

/**
 * As imagens do HTML vêm com o caminho relativo à API (`documentacao/imagens/...`):
 * aqui elas ganham a URL base da API (que pode estar em outro host no desenvolvimento).
 */
export function prepararHtml(html: string): string {
  return html.replaceAll('src="documentacao/imagens/', `src="${BASE_URL}/documentacao/imagens/`)
}

// ---------------------------------------------------------------- edição (documentacao.editar)

/** Gravação: a página e os avisos que não impedem salvar. */
export interface DocGravacao extends ApiEnvelope<DocPaginaEdicao> {
  avisos?: string[]
}

export function listarParaEdicao(): Promise<ApiEnvelope<DocItemEdicao[]>> {
  return api.get<DocItemEdicao[]>('/documentacao/admin/paginas')
}

export function obterParaEdicao(id: number): Promise<ApiEnvelope<DocPaginaEdicao>> {
  return api.get<DocPaginaEdicao>(`/documentacao/admin/paginas/${id}`)
}

export function criarPagina(entrada: DocEntradaPagina): Promise<DocGravacao> {
  return api.post<DocPaginaEdicao>('/documentacao/admin/paginas', entrada)
}

export function salvarPagina(id: number, entrada: DocEntradaPagina): Promise<DocGravacao> {
  return api.put<DocPaginaEdicao>(`/documentacao/admin/paginas/${id}`, entrada)
}

export function publicarPagina(id: number): Promise<ApiEnvelope<DocPaginaEdicao>> {
  return api.post<DocPaginaEdicao>(`/documentacao/admin/paginas/${id}/publicar`)
}

export function despublicarPagina(id: number): Promise<ApiEnvelope<DocPaginaEdicao>> {
  return api.post<DocPaginaEdicao>(`/documentacao/admin/paginas/${id}/despublicar`)
}

export function excluirPagina(id: number): Promise<ApiEnvelope> {
  return api.delete(`/documentacao/admin/paginas/${id}`)
}

/** Cópia editável (rascunho) de uma página de arquivo. */
export function copiarDoArquivo(slug: string): Promise<DocGravacao> {
  return api.post<DocPaginaEdicao>(`/documentacao/admin/paginas/de-arquivo/${caminho(slug)}`)
}

export function previsualizar(markdown: string, slug: string): Promise<ApiEnvelope<DocPreview>> {
  return api.post<DocPreview>('/documentacao/admin/preview', { markdown, slug })
}

export function enviarImagem(arquivo: File, secao: string): Promise<ApiEnvelope<DocImagemEnviada>> {
  const corpo = new FormData()
  corpo.append('arquivo', arquivo)
  corpo.append('secao', secao)
  return api.postForm<DocImagemEnviada>('/documentacao/admin/imagens', corpo)
}
