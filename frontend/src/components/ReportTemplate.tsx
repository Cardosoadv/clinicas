import { useEffect, useState } from 'react'
import { fetchTemplates } from '../features/configuracoes/api'
import './ReportTemplate.css'

interface ReportTemplateProps {
  title?: string
  children: React.ReactNode
  showPrintButton?: boolean
  className?: string
}

function processTemplate(html: string | undefined | null): string {
  if (!html) return ''
  // Para fins de simplificação, replace básico de placeholders
  let processed = html.replace(/{data}/g, new Intl.DateTimeFormat('pt-BR').format(new Date()))
  return processed
}

export function ReportTemplate({ title, children, showPrintButton = true, className = '' }: ReportTemplateProps) {
  const [header, setHeader] = useState('')
  const [footer, setFooter] = useState('')
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    fetchTemplates()
      .then((res) => {
        const data = res.data ?? []
        const headerTpl = data.find((d) => d.meta_key === 'report_header')?.meta_value
        const footerTpl = data.find((d) => d.meta_key === 'report_footer')?.meta_value
        setHeader(processTemplate(headerTpl))
        setFooter(processTemplate(footerTpl))
      })
      .finally(() => setIsLoading(false))
  }, [])

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
          <header className="report-header" dangerouslySetInnerHTML={{ __html: header || '<b>Sua Clínica Veterinária</b><br>Endereço - Telefone' }} />
          
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
