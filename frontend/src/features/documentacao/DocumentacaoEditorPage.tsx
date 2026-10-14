import { Eye, ImagePlus, Trash2, TriangleAlert } from 'lucide-react'
import { useEffect, useMemo, useRef, useState, type ChangeEvent, type KeyboardEvent } from 'react'
import { Link, useLocation, useNavigate, useParams } from 'react-router-dom'
import { ApiError } from '../../lib/api'
import { usePermissao } from '../auth/AuthContext'
import {
  criarPagina, despublicarPagina, enviarImagem, excluirPagina, obterParaEdicao, previsualizar, publicarPagina, salvarPagina,
  type DocGravacao,
} from './api'
import { DocConteudo } from './DocConteudo'
import { caminhoDaDocumentacao, caminhoDaEdicao } from './rotas'
import type { DocEntradaPagina, DocPaginaEdicao, DocPreview } from './types'
import './documentacao.css'

const vazio = (): DocEntradaPagina => ({
  slug: '', titulo: '', resumo: '', ordem: null, permissao: '', telas: [], palavras_chave: [], markdown: '',
})

const CAMPOS = ['slug', 'titulo', 'resumo', 'ordem', 'permissao', 'markdown', 'telas', 'palavras_chave']

/** Listas editadas como texto separado por vírgula. */
const lista = (t: string) => t.split(',').map((v) => v.trim()).filter(Boolean)

function normalizar(form: DocEntradaPagina): DocEntradaPagina {
  return { ...form, slug: form.slug.trim().toLowerCase(), telas: lista(form.telas.join(',')), palavras_chave: lista(form.palavras_chave.join(',')) }
}

function deEdicao(p: DocPaginaEdicao): DocEntradaPagina {
  return {
    slug: p.slug, titulo: p.titulo, resumo: p.resumo || '', ordem: p.ordem, permissao: p.permissao || '',
    telas: [...p.telas], palavras_chave: [...p.palavras_chave], markdown: p.markdown,
  }
}

/** Editor de uma página da documentação (Markdown com pré-visualização). `/documentacao-admin/{id|nova}`. */
export function DocumentacaoEditorPage() {
  const podeEditar = usePermissao('documentacao.editar')
  const parametro = useParams().id
  const id = parametro && /^\d+$/.test(parametro) ? Number(parametro) : null
  const navigate = useNavigate()
  const location = useLocation()

  const [form, setForm] = useState<DocEntradaPagina>(vazio)
  // Texto das listas como digitado (com vírgulas e espaços), convertido ao salvar.
  const [telasTexto, setTelasTexto] = useState('')
  const [palavrasTexto, setPalavrasTexto] = useState('')
  const [original, setOriginal] = useState<DocPaginaEdicao | null>(null)
  const [referencia, setReferencia] = useState(JSON.stringify(vazio()))
  const [carregando, setCarregando] = useState(false)
  const [falhaCarga, setFalhaCarga] = useState('')
  const [salvando, setSalvando] = useState(false)
  const [mudandoStatus, setMudandoStatus] = useState(false)
  const [enviandoImagem, setEnviandoImagem] = useState(false)
  const [erros, setErros] = useState<Record<string, string>>({})
  const [errosGerais, setErrosGerais] = useState<string[]>([])
  const [avisos, setAvisos] = useState<string[]>([])
  const [mensagem, setMensagem] = useState<string>((location.state as { aviso?: string } | null)?.aviso ?? '')
  const [previa, setPrevia] = useState<DocPreview>({ html: '', sumario: [], avisos: [] })
  const [previsualizando, setPrevisualizando] = useState(false)
  const texto = useRef<HTMLTextAreaElement>(null)
  /** Id recém-criado: ao trocar o endereço para ele, os dados já estão no formulário. */
  const criadaAgora = useRef<number | null>(null)

  const atual = useMemo(() => normalizar({ ...form, telas: lista(telasTexto), palavras_chave: lista(palavrasTexto) }), [form, telasTexto, palavrasTexto])
  const sujo = JSON.stringify(atual) !== referencia
  const ocupado = salvando || mudandoStatus
  const secao = useMemo(() => {
    const s = form.slug.trim().toLowerCase().split('/')[0]
    return /^[a-z0-9]+(-[a-z0-9]+)*$/.test(s) ? s : ''
  }, [form.slug])

  function preencher(p: DocPaginaEdicao | null) {
    const dados = p ? deEdicao(p) : vazio()
    setOriginal(p)
    setForm(dados)
    setTelasTexto(dados.telas.join(', '))
    setPalavrasTexto(dados.palavras_chave.join(', '))
    setReferencia(JSON.stringify(normalizar(dados)))
  }

  useEffect(() => {
    if (!podeEditar) return
    if (id !== null && criadaAgora.current === id) return
    setErros({})
    setErrosGerais([])
    setAvisos([])
    setFalhaCarga('')
    if (id === null) {
      preencher(null)
      return
    }
    let cancelado = false
    setCarregando(true)
    obterParaEdicao(id)
      .then((r) => {
        if (!cancelado) preencher(r.data ?? null)
      })
      .catch((erro) => {
        if (!cancelado) setFalhaCarga(erro instanceof ApiError && erro.status === 404 ? 'Página não encontrada.' : 'Não foi possível carregar a página.')
      })
      .finally(() => {
        if (!cancelado) setCarregando(false)
      })
    return () => {
      cancelado = true
    }
  }, [id, podeEditar])

  // Pré-visualização 600 ms depois da última alteração no texto ou no endereço.
  useEffect(() => {
    if (!podeEditar) return
    let cancelado = false
    const espera = window.setTimeout(() => {
      setPrevisualizando(true)
      previsualizar(form.markdown, form.slug.trim().toLowerCase() || 'rascunho')
        .then((r) => {
          if (!cancelado && r.data) setPrevia(r.data)
        })
        .catch(() => {
          // Mantém a última prévia; o erro de verdade aparece ao salvar.
        })
        .finally(() => {
          if (!cancelado) setPrevisualizando(false)
        })
    }, 600)
    return () => {
      cancelado = true
      window.clearTimeout(espera)
    }
  }, [form.markdown, form.slug, podeEditar])

  // Aviso do navegador ao fechar a aba com alterações não salvas.
  useEffect(() => {
    if (!sujo) return
    function aoFechar(e: BeforeUnloadEvent) {
      e.preventDefault()
      e.returnValue = ''
    }
    window.addEventListener('beforeunload', aoFechar)
    return () => window.removeEventListener('beforeunload', aoFechar)
  }, [sujo])

  function tratarErro(erro: unknown, padrao: string) {
    const porCampo = (erro instanceof ApiError ? (erro.body?.errors as Record<string, string> | undefined) : undefined) ?? {}
    setErros(porCampo)
    const gerais = Object.entries(porCampo)
      .filter(([k]) => !CAMPOS.some((c) => k === c || k.startsWith(`${c}.`)))
      .map(([, m]) => m)
    setErrosGerais(Object.keys(porCampo).length ? gerais : [erro instanceof Error ? erro.message : padrao])
  }

  /** Grava (cria ou atualiza). Devolve falso se a validação recusou. */
  async function salvar(): Promise<boolean> {
    setSalvando(true)
    setErros({})
    setErrosGerais([])
    setMensagem('')
    try {
      const r: DocGravacao = id ? await salvarPagina(id, atual) : await criarPagina(atual)
      if (r.data) preencher(r.data)
      setAvisos(r.avisos ?? [])
      setMensagem('Rascunho salvo.')
      if (!id && r.data) {
        criadaAgora.current = r.data.id
        navigate(caminhoDaEdicao(r.data.id), { replace: true })
      }
      return true
    } catch (erro) {
      tratarErro(erro, 'Não foi possível salvar.')
      return false
    } finally {
      setSalvando(false)
    }
  }

  async function mudarStatus(status: 'publicado' | 'rascunho') {
    if (!id) return
    if (sujo && !(await salvar())) return
    setMudandoStatus(true)
    try {
      const r = status === 'publicado' ? await publicarPagina(id) : await despublicarPagina(id)
      if (r.data) preencher(r.data)
      setMensagem(status === 'publicado' ? 'Página publicada: já aparece para os leitores.' : 'Página voltou a ser rascunho.')
    } catch (erro) {
      tratarErro(erro, 'Não foi possível mudar o status.')
    } finally {
      setMudandoStatus(false)
    }
  }

  async function excluir() {
    if (!id || !original) return
    const aviso = original.tem_arquivo
      ? 'Excluir esta cópia? A página volta a mostrar a versão do manual oficial.'
      : 'Excluir esta página? Ela some da documentação.'
    if (!window.confirm(aviso)) return
    try {
      await excluirPagina(id)
      setReferencia(JSON.stringify(atual))
      navigate(caminhoDaEdicao(), { replace: true })
    } catch (erro) {
      tratarErro(erro, 'Não foi possível excluir.')
    }
  }

  function sair(destino: string) {
    if (sujo && !window.confirm('Há alterações não salvas. Sair mesmo assim?')) return
    navigate(destino)
  }

  function inserir(trecho: string) {
    const area = texto.current
    const inicio = area?.selectionStart ?? form.markdown.length
    const fim = area?.selectionEnd ?? inicio
    setForm((f) => ({ ...f, markdown: f.markdown.slice(0, inicio) + trecho + f.markdown.slice(fim) }))
    requestAnimationFrame(() => {
      area?.focus()
      area?.setSelectionRange(inicio + trecho.length, inicio + trecho.length)
    })
  }

  async function aoEscolherImagem(evento: ChangeEvent<HTMLInputElement>) {
    const arquivo = evento.target.files?.[0]
    evento.target.value = ''
    if (!arquivo || !secao) return
    setEnviandoImagem(true)
    try {
      const r = await enviarImagem(arquivo, secao)
      if (r.data) inserir(`\n${r.data.markdown}\n`)
      setMensagem('Imagem enviada. Troque "Descreva a imagem" por uma descrição.')
    } catch (erro) {
      const porCampo = (erro instanceof ApiError ? (erro.body?.errors as Record<string, string> | undefined) : undefined) ?? {}
      setErrosGerais([porCampo.arquivo || porCampo.secao || (erro instanceof Error ? erro.message : 'Não foi possível enviar a imagem.')])
    } finally {
      setEnviandoImagem(false)
    }
  }

  function aoTeclar(evento: KeyboardEvent<HTMLTextAreaElement>) {
    if ((evento.ctrlKey || evento.metaKey) && evento.key.toLowerCase() === 's') {
      evento.preventDefault()
      if (!ocupado) void salvar()
    }
  }

  const erroDeLista = (campo: string) => erros[campo] || Object.entries(erros).find(([k]) => k.startsWith(`${campo}.`))?.[1] || ''
  const campo = (nome: keyof DocEntradaPagina) => (e: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const valor = e.target.value
    setForm((f) => ({ ...f, [nome]: nome === 'ordem' ? (valor === '' ? null : Number(valor)) : valor }))
  }

  if (!podeEditar) {
    return <p className="empty-state empty-state--error">Você não tem permissão para editar a documentação.</p>
  }

  return (
    <div className="list-page doc-editor">
      <div className="page-header">
        <div>
          <p className="doc-migalha">
            <a href={caminhoDaEdicao()} onClick={(e) => { e.preventDefault(); sair(caminhoDaEdicao()) }}>Edição da documentação</a>
          </p>
          <h2 className="doc-editor-titulo">
            {id ? original?.titulo || 'Página' : 'Nova página'}
            {original && (
              <span className={`doc-selo ${original.status === 'rascunho' ? 'doc-selo--rascunho' : 'doc-selo--publicado'}`}>
                {original.status === 'rascunho' ? 'Rascunho' : 'Publicado'}
              </span>
            )}
          </h2>
          {original?.tem_arquivo && (
            <p className="page-subtitle">Cópia de uma página do manual oficial. Enquanto for rascunho, os leitores continuam vendo a versão oficial.</p>
          )}
        </div>
        <div className="doc-acoes">
          {sujo && <span className="doc-sujo" role="status">Alterações não salvas</span>}
          {original && (
            <Link to={caminhoDaDocumentacao(original.slug)} className="btn btn--ghost" onClick={(e) => { e.preventDefault(); sair(caminhoDaDocumentacao(original.slug)) }}>
              <Eye size={16} /> Ver
            </Link>
          )}
          {original && (
            <button type="button" className="btn btn--ghost doc-perigo" disabled={ocupado} onClick={() => void excluir()}>
              <Trash2 size={16} /> Excluir
            </button>
          )}
          <button type="button" className="btn btn--ghost" disabled={ocupado || (!!id && !sujo)} title="Salvar (Ctrl+S)" onClick={() => void salvar()}>
            {salvando ? 'Salvando…' : 'Salvar rascunho'}
          </button>
          {original?.status === 'publicado' ? (
            <button type="button" className="btn btn--ghost" disabled={ocupado} onClick={() => void mudarStatus('rascunho')}>
              Despublicar
            </button>
          ) : (
            <button type="button" className="btn btn--primary" disabled={ocupado || !id} title={!id ? 'Salve antes de publicar' : undefined} onClick={() => void mudarStatus('publicado')}>
              {sujo ? 'Salvar e publicar' : 'Publicar'}
            </button>
          )}
        </div>
      </div>

      {carregando ? (
        <p className="empty-state" role="status">Carregando…</p>
      ) : falhaCarga ? (
        <p className="empty-state empty-state--error" role="alert">{falhaCarga}</p>
      ) : (
        <>
          {mensagem && <p className="doc-mensagem" role="status">{mensagem}</p>}
          {errosGerais.map((m) => (
            <p key={m} className="doc-mensagem doc-mensagem--erro" role="alert">{m}</p>
          ))}
          {avisos.length > 0 && (
            <div className="doc-avisos" role="status">
              <strong><TriangleAlert size={15} /> Atenção</strong>
              <ul>{avisos.map((a) => <li key={a}>{a}</li>)}</ul>
            </div>
          )}

          <details className="form-section doc-dados" open={!id}>
            <summary>Dados da página</summary>
            <div className="form-grid">
              <div className="form-field">
                <label htmlFor="doc-titulo">Título *</label>
                <input id="doc-titulo" value={form.titulo} maxLength={120} aria-invalid={Boolean(erros.titulo)} onChange={campo('titulo')} />
                {erros.titulo && <span className="field-error">{erros.titulo}</span>}
              </div>
              <div className="form-field">
                <label htmlFor="doc-slug">Endereço *</label>
                <input
                  id="doc-slug"
                  value={form.slug}
                  placeholder="secao/pagina"
                  aria-invalid={Boolean(erros.slug)}
                  onChange={campo('slug')}
                  onBlur={() => setForm((f) => ({ ...f, slug: f.slug.trim().toLowerCase() }))}
                />
                {erros.slug ? (
                  <span className="field-error">{erros.slug}</span>
                ) : (
                  <span className="field-hint">"secao" para a capa, "secao/pagina" para uma página. Ex.: agenda/encaixes</span>
                )}
              </div>
              <div className="form-field form-field--full">
                <label htmlFor="doc-resumo">Resumo</label>
                <input id="doc-resumo" value={form.resumo} maxLength={300} aria-invalid={Boolean(erros.resumo)} onChange={campo('resumo')} />
                {erros.resumo && <span className="field-error">{erros.resumo}</span>}
              </div>
              <div className="form-field">
                <label htmlFor="doc-ordem">Ordem</label>
                <input id="doc-ordem" type="number" min={0} max={9999} placeholder="100" value={form.ordem ?? ''} aria-invalid={Boolean(erros.ordem)} onChange={campo('ordem')} />
                {erros.ordem && <span className="field-error">{erros.ordem}</span>}
              </div>
              <div className="form-field">
                <label htmlFor="doc-permissao">Permissão</label>
                <input id="doc-permissao" value={form.permissao} placeholder="ex.: documentacao.editar" aria-invalid={Boolean(erros.permissao)} onChange={campo('permissao')} />
                {erros.permissao ? (
                  <span className="field-error">{erros.permissao}</span>
                ) : (
                  <span className="field-hint">Em branco, todos os usuários veem a página.</span>
                )}
              </div>
              <div className="form-field">
                <label htmlFor="doc-telas">Telas da ajuda</label>
                <input id="doc-telas" value={telasTexto} placeholder="ex.: agenda, agenda-todos" aria-invalid={Boolean(erroDeLista('telas'))} onChange={(e) => setTelasTexto(e.target.value)} />
                {erroDeLista('telas') ? (
                  <span className="field-error">{erroDeLista('telas')}</span>
                ) : (
                  <span className="field-hint">Separe por vírgula. O botão "?" dessas telas abre esta página.</span>
                )}
              </div>
              <div className="form-field">
                <label htmlFor="doc-palavras">Palavras-chave</label>
                <input id="doc-palavras" value={palavrasTexto} placeholder="ex.: vacina, reforço" aria-invalid={Boolean(erroDeLista('palavras_chave'))} onChange={(e) => setPalavrasTexto(e.target.value)} />
                {erroDeLista('palavras_chave') && <span className="field-error">{erroDeLista('palavras_chave')}</span>}
              </div>
            </div>
          </details>

          <div className="doc-editor-area">
            <section className="doc-editor-painel">
              <div className="doc-editor-barra">
                <strong>Texto (Markdown)</strong>
                <span className="doc-espaco" />
                <button type="button" className="btn btn--ghost doc-btn-pequeno" onClick={() => inserir('\n> [!DICA]\n> ')}>Dica</button>
                <button type="button" className="btn btn--ghost doc-btn-pequeno" onClick={() => inserir('\n> [!ATENCAO]\n> ')}>Atenção</button>
                <label className={`btn btn--ghost doc-btn-pequeno${!secao || enviandoImagem ? ' doc-desativado' : ''}`} title={secao ? 'Inserir imagem' : 'Preencha o endereço antes'}>
                  <ImagePlus size={15} /> {enviandoImagem ? 'Enviando…' : 'Imagem'}
                  <input type="file" accept="image/png,image/jpeg,image/gif,image/webp" hidden disabled={!secao || enviandoImagem} onChange={(e) => void aoEscolherImagem(e)} />
                </label>
              </div>
              <textarea
                ref={texto}
                className="doc-markdown"
                spellCheck
                aria-label="Texto da página em Markdown"
                placeholder={'## Título da seção\n\nTexto da página…'}
                value={form.markdown}
                onChange={campo('markdown')}
                onKeyDown={aoTeclar}
              />
              {erros.markdown && <span className="field-error">{erros.markdown}</span>}
              <span className="field-hint">
                Use ## e ### para os títulos. Links para outras páginas: <code>[texto](../secao/pagina.md)</code>; para telas:{' '}
                <code>[Agenda](/agenda)</code>. Formato completo em "Documentação › Usar a documentação › Editar a documentação".
              </span>
            </section>

            <section className="doc-editor-painel doc-previa" aria-label="Pré-visualização">
              <div className="doc-editor-barra">
                <strong>Pré-visualização</strong>
                <span className="doc-espaco" />
                {previsualizando && <small>Atualizando…</small>}
              </div>
              <h1 className="doc-previa-titulo">{form.titulo || 'Sem título'}</h1>
              {form.resumo && <p className="doc-resumo">{form.resumo}</p>}
              <DocConteudo html={previa.html} />
              {previa.avisos.length > 0 && (
                <ul className="doc-avisos-previa">{previa.avisos.map((a) => <li key={a}>{a}</li>)}</ul>
              )}
            </section>
          </div>
        </>
      )}
    </div>
  )
}
