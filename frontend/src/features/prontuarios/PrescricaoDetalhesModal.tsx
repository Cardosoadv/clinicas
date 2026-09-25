import { Pill, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { fetchPrescricao } from './api'
import type { PrescricaoDetalhada, PrescricaoItem } from './types'
import { ReportTemplate } from '../../components/ReportTemplate'
import { formatDataExtenso } from '../../components/reportTemplateData'
import { useEscapeKey } from '../../hooks/useEscapeKey'

interface PrescricaoDetalhesModalProps {
  prescricaoId: number
  onClose: () => void
}

function formatDate(value: string): string {
  try {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(value))
  } catch {
    return value
  }
}

/** Datas "YYYY-MM-DD" são interpretadas em horário local (evita voltar um dia por fuso). */
function parseDate(value: string): Date {
  const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value)
  return match ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3])) : new Date(value)
}

function calcIdade(nascimento: string | null): string | null {
  if (!nascimento || nascimento.startsWith('0000')) return null
  const nasc = parseDate(nascimento)
  if (Number.isNaN(nasc.getTime())) return null

  const now = new Date()
  let anos = now.getFullYear() - nasc.getFullYear()
  let meses = now.getMonth() - nasc.getMonth()
  if (now.getDate() < nasc.getDate()) meses -= 1
  if (meses < 0) {
    anos -= 1
    meses += 12
  }

  if (anos <= 0) return `${meses} ${meses === 1 ? 'mês' : 'meses'}`
  return `${anos} ${anos === 1 ? 'ano' : 'anos'}${meses > 0 ? ` e ${meses} ${meses === 1 ? 'mês' : 'meses'}` : ''}`
}

const comecaComNumero = (value: string) => /^\d/.test(value.trim())

/** Monta a posologia em texto corrido: "Dar 1/2 comprimido a cada 24h por 30 dias". */
function formatPosologia(item: PrescricaoItem): string {
  const partes = [
    item.dosagem,
    item.frequencia && (comecaComNumero(item.frequencia) ? `a cada ${item.frequencia}` : item.frequencia),
    item.duracao && (comecaComNumero(item.duracao) ? `por ${item.duracao}` : item.duracao),
  ].filter(Boolean)
  let texto = partes.join(' ')
  if (item.particao) texto += ` (${item.particao})`
  return texto
}

/** Agrupa os itens pela via de administração, mantendo a ordem de cadastro. */
function agruparPorVia(itens: PrescricaoItem[]): Array<{ via: string; itens: PrescricaoItem[] }> {
  const grupos: Array<{ via: string; itens: PrescricaoItem[] }> = []
  for (const item of itens) {
    const via = item.via_administracao?.trim() ? `Uso ${item.via_administracao.trim()}` : 'Uso oral / externo'
    const grupo = grupos.find((g) => g.via.toLowerCase() === via.toLowerCase())
    if (grupo) grupo.itens.push(item)
    else grupos.push({ via, itens: [item] })
  }
  return grupos
}

function Separador() {
  return <span className="receita__box-sep">·</span>
}

function ReceitaImpressao({ prescricao }: { prescricao: PrescricaoDetalhada }) {
  const data = parseDate(prescricao.data_prescricao)
  const idade = calcIdade(prescricao.pet_nascimento)
  const detalhesPet = [
    ['Espécie', prescricao.pet_especie],
    ['Raça', prescricao.pet_raca],
    ['Sexo', prescricao.pet_sexo],
    ['Idade', idade],
  ].filter((entry): entry is [string, string] => Boolean(entry[1]))

  return (
    <ReportTemplate
      printOnly
      showPrintButton={false}
      variables={{
        data: new Intl.DateTimeFormat('pt-BR').format(data),
        data_extenso: formatDataExtenso(data),
        veterinario: prescricao.veterinario_nome,
        crmv: prescricao.veterinario_crmv,
      }}
    >
      <h2 className="receita__titulo">Receita Simples</h2>

      <div className="receita__box">
        Animal: <strong>{prescricao.pet_nome}</strong> (ID: {prescricao.pet_id})
        {detalhesPet.length > 0 && (
          <div>
            {detalhesPet.map(([label, value], index) => (
              <span key={label}>
                {index > 0 && <Separador />}
                {label}: <strong>{value}</strong>
              </span>
            ))}
          </div>
        )}
        <div>
          Responsável: <strong>{prescricao.tutor_nome}</strong>
        </div>
        {prescricao.tutor_endereco && <div>Endereço: {prescricao.tutor_endereco}</div>}
      </div>

      <div className="receita__box">
        Emitente: <strong>{prescricao.veterinario_nome || 'Clínico'}</strong>
        {prescricao.veterinario_crmv && (
          <div>
            CRMV: <strong>{prescricao.veterinario_crmv}</strong>
          </div>
        )}
      </div>

      {agruparPorVia(prescricao.itens).map((grupo) => (
        <section key={grupo.via}>
          <div className="receita__via">{grupo.via}</div>
          {grupo.itens.map((item) => (
            <div className="receita__item" key={item.id}>
              <div className="receita__item-linha">
                <span>{item.medicamento}</span>
                <span className="receita__item-leader" />
              </div>
              <p className="receita__posologia">Posologia: {formatPosologia(item)}</p>
            </div>
          ))}
        </section>
      ))}

      {prescricao.observacoes && (
        <>
          <div className="receita__secao">INSTRUÇÕES DE TRATAMENTO</div>
          <p className="receita__instrucoes">{prescricao.observacoes}</p>
        </>
      )}
    </ReportTemplate>
  )
}

export function PrescricaoDetalhesModal({ prescricaoId, onClose }: PrescricaoDetalhesModalProps) {
  const [prescricao, setPrescricao] = useState<PrescricaoDetalhada | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    fetchPrescricao(prescricaoId)
      .then((res) => setPrescricao(res.data ?? null))
      .finally(() => setIsLoading(false))
  }, [prescricaoId])

  useEscapeKey(onClose)

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal" onClick={(event) => event.stopPropagation()} role="dialog" aria-modal="true">
        <div className="modal__header">
          <h2>
            <Pill size={18} style={{ verticalAlign: 'text-bottom', marginRight: 6 }} />
            Prescrição #{prescricaoId}
          </h2>
          <button type="button" onClick={onClose} aria-label="Fechar">
            <X size={18} />
          </button>
        </div>

        <div className="modal__form">
          {isLoading && <p className="empty-state">Carregando...</p>}

          {!isLoading && prescricao && (
            <>
              <p className="page-subtitle">
                {formatDate(prescricao.data_prescricao)} · {prescricao.veterinario_nome || 'Clínico'}
              </p>

              <div className="prontuario-prescricao__itens">
                {prescricao.itens.map((item) => (
                  <div className="prontuario-prescricao__item-card" key={item.id}>
                    <strong>{item.medicamento}</strong>
                    <span>
                      {[item.dosagem, item.frequencia, item.duracao, item.via_administracao, item.particao]
                        .filter(Boolean)
                        .join(' · ')}
                    </span>
                  </div>
                ))}
              </div>

              {prescricao.observacoes && (
                <div className="cliente-detail__observacoes">
                  <span className="cliente-detail__field-label">Observações</span>
                  <p>{prescricao.observacoes}</p>
                </div>
              )}
            </>
          )}

          <div className="modal__footer">
            <button
              type="button"
              className="btn btn--primary"
              onClick={() => window.print()}
              disabled={isLoading || !prescricao}
            >
              Imprimir
            </button>
            <button type="button" className="btn btn--ghost" onClick={onClose}>
              Fechar
            </button>
          </div>
        </div>
      </div>

      {!isLoading && prescricao && <ReceitaImpressao prescricao={prescricao} />}
    </div>
  )
}
