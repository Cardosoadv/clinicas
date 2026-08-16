import { useEffect, useState } from 'react'
import { fetchTemplates } from '../features/configuracoes/api'
import { useLojaPrincipal } from '../features/lojas/LojaPrincipalContext'
import type { Loja } from '../features/lojas/types'
import './ReportTemplate.css'

interface ReportTemplateProps {
  title?: string
  children: React.ReactNode
  showPrintButton?: boolean
  className?: string
}

function processTemplate(html: string | undefined | null, loja: Loja | null): string {
  if (!html) return ''
  // Para fins de simplificação, replace básico de placeholders
  let processed = html.replace(/{data}/g, new Intl.DateTimeFormat('pt-BR').format(new Date()))
  processed = processed.replace(/{clinica}/g, loja?.nome ?? '')
  processed = processed.replace(/{telefone}/g, loja?.telefone ?? '')
  processed = processed.replace(/{endereco}/g, loja?.endereco ?? '')
  return processed
}

function defaultHeader(loja: Loja | null): string {
  if (!loja) return '<b>Sua Clínica Veterinária</b><br>Endereço - Telefone'
  const linha2 = [loja.endereco, loja.telefone].filter(Boolean).join(' - ')
  return `<b>${loja.nome}</b>${linha2 ? `<br>${linha2}` : ''}`
}

export function ReportTemplate({ title, children, showPrintButton = true, className = '' }: ReportTemplateProps) {
  const { loja } = useLojaPrincipal()
  const [header, setHeader] = useState('')
  const [footer, setFooter] = useState('')
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    fetchTemplates()
      .then((res) => {
        const data = res.data ?? []
        const headerTpl = data.find((d) => d.meta_key === 'report_header')?.meta_value
        const footerTpl = data.find((d) => d.meta_key === 'report_footer')?.meta_value
        setHeader(processTemplate(headerTpl, loja))
        setFooter(processTemplate(footerTpl, loja))
      })
      .finally(() => setIsLoading(false))
  }, [loja])

  return (
    <div className={`report-container ${className}`.trim()}>
      {showPrintButton && (
        <div className="report-actions no-print">
          <button type="button" className="btn btn--primary" onClick={() => window.print()}>
            Imprimir
          </button>
        </div>
      )}

      {isLoading ? (
        <div className="no-print">Carregando template...</div>
      ) : (
        <div className="report-document">
          <header className="report-header" dangerouslySetInnerHTML={{ __html: header || defaultHeader(loja) }} />
          
          <main className="report-body">
            {title && <h1 className="report-title">{title}</h1>}
            {children}
          </main>

          <footer className="report-footer" dangerouslySetInnerHTML={{ __html: footer || '{data}' }} />
        </div>
      )}
    </div>
  )
}
