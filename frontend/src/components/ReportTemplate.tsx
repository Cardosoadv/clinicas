import { useEffect, useState } from 'react'
import { createPortal } from 'react-dom'
import { useLojaPrincipal, useLojaPrincipalLogoUrl } from '../features/lojas/LojaPrincipalContext'
import type { Loja } from '../features/lojas/types'
import { formatDataExtenso, loadTemplates } from './reportTemplateData'
import './ReportTemplate.css'

/** Variáveis extras que a tela pode informar ({veterinario}, {crmv}, {data}...). */
export type ReportVariables = Record<string, string | null | undefined>

interface ReportTemplateProps {
  title?: string
  children: React.ReactNode
  showPrintButton?: boolean
  className?: string
  /** Sobrescreve/complementa as variáveis usadas no cabeçalho e rodapé. */
  variables?: ReportVariables
  /**
   * Renderiza o documento direto no <body>, fora do layout da aplicação.
   * Use para documentos que existem apenas para impressão (ex.: dentro de modais),
   * evitando páginas em branco extras geradas pelo restante da tela.
   */
  printOnly?: boolean
}

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}

function lojaEnderecoCompleto(loja: Loja | null): string {
  if (!loja) return ''
  const cidadeUf = [loja.cidade, loja.estado].filter(Boolean).join('/')
  const cep = loja.cep ? `CEP: ${loja.cep}` : ''
  return [loja.endereco, cidadeUf, cep].filter(Boolean).join(' - ')
}

function buildVariables(loja: Loja | null, logoUrl: string | null, extra: ReportVariables = {}): Record<string, string> {
  const hoje = new Date()
  const base: ReportVariables = {
    data: new Intl.DateTimeFormat('pt-BR').format(hoje),
    data_extenso: formatDataExtenso(hoje),
    clinica: loja?.nome,
    telefone: loja?.telefone,
    endereco: lojaEnderecoCompleto(loja),
    email: loja?.email,
    cidade: loja?.cidade,
    estado: loja?.estado,
    cep: loja?.cep,
    cnpj: loja?.cnpj,
    veterinario: '',
    crmv: '',
  }

  const merged: Record<string, string> = {}
  for (const [key, value] of Object.entries({ ...base, ...extra })) {
    merged[key] = escapeHtml(value ?? '')
  }
  // {logo} vira a própria imagem (já é HTML, não é escapado)
  merged.logo = logoUrl ? `<img class="report-logo" src="${escapeHtml(logoUrl)}" alt="Logo" />` : ''
  return merged
}

/** Substitui {chave} pelos valores conhecidos; placeholders desconhecidos ficam intactos. */
function processTemplate(html: string, variables: Record<string, string>): string {
  return html.replace(/\{(\w+)\}/g, (match, key: string) => (key in variables ? variables[key] : match))
}

const DEFAULT_HEADER = `
<div class="report-header__default">
  {logo}
  <div class="report-header__identidade">
    <div class="report-header__nome">{clinica}</div>
    <div class="report-header__sub">{cnpj}</div>
  </div>
  <div class="report-header__contato">
    <div>{endereco}</div>
    <div>{telefone}</div>
    <div>{email}</div>
  </div>
</div>`

const DEFAULT_FOOTER = `
<div class="report-footer__default">
  <div class="report-footer__data">{data_extenso}</div>
  <div class="report-footer__assinatura">{veterinario}</div>
  <div>CRMV {crmv}</div>
</div>`

export function ReportTemplate({
  title,
  children,
  showPrintButton = true,
  className = '',
  variables,
  printOnly = false,
}: ReportTemplateProps) {
  const { loja } = useLojaPrincipal()
  const logoUrl = useLojaPrincipalLogoUrl()
  const [templates, setTemplates] = useState<{ header: string; footer: string } | null>(null)

  useEffect(() => {
    let active = true
    loadTemplates().then((result) => {
      if (active) setTemplates(result)
    })
    return () => {
      active = false
    }
  }, [])

  const vars = buildVariables(loja, logoUrl, variables)
  // Remove linhas/campos que ficaram vazios após a substituição (ex.: "CRMV " sem número)
  const header = processTemplate(templates?.header || DEFAULT_HEADER, vars)
  const footer = processTemplate(templates?.footer || DEFAULT_FOOTER, vars).replace(/<div>CRMV\s*<\/div>/, '')

  const content = (
    <div className={`report-container ${printOnly ? 'print-only' : ''} ${className}`.trim()}>
      {showPrintButton && (
        <div className="report-actions no-print">
          <button type="button" className="btn btn--primary" onClick={() => window.print()}>
            Imprimir
          </button>
        </div>
      )}

      {!templates ? (
        <div className="no-print">Carregando template...</div>
      ) : (
        <div className="report-document">
          <header
            className={`report-header ${templates.header ? 'report-header--custom' : ''}`.trim()}
            dangerouslySetInnerHTML={{ __html: header }}
          />

          <main className="report-body">
            {title && <h1 className="report-title">{title}</h1>}
            {children}
          </main>

          <footer className="report-footer" dangerouslySetInnerHTML={{ __html: footer }} />
        </div>
      )}
    </div>
  )

  return printOnly ? createPortal(content, document.body) : content
}
