import { Calculator, X } from 'lucide-react'
import { useMemo, useState } from 'react'
import { useEscapeKey } from '../../hooks/useEscapeKey'

interface CalculadoraModalProps {
  pesoAtual: number | null
  onClose: () => void
  onCopyToEvolucao: (texto: string) => void
}

type Apresentacao = 'ml' | 'cp'

export function CalculadoraModal({ pesoAtual, onClose, onCopyToEvolucao }: CalculadoraModalProps) {
  const [peso, setPeso] = useState(pesoAtual ? String(pesoAtual) : '')
  const [dose, setDose] = useState('')
  const [concentracao, setConcentracao] = useState('')
  const [apresentacao, setApresentacao] = useState<Apresentacao>('ml')

  const resultado = useMemo(() => {
    const pesoNum = Number(peso)
    const doseNum = Number(dose)
    const concentracaoNum = Number(concentracao)
    if (!pesoNum || !doseNum || !concentracaoNum) return null
    return (pesoNum * doseNum) / concentracaoNum
  }, [peso, dose, concentracao])

  const resumo =
    resultado !== null
      ? `Cálculo de dosagem: ${peso}kg × ${dose}mg/kg ÷ ${concentracao}mg/${apresentacao} = ${resultado.toFixed(2)}${apresentacao}`
      : ''

  useEscapeKey(onClose)

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal" onClick={(event) => event.stopPropagation()} role="dialog" aria-modal="true">
        <div className="modal__header">
          <h2>
            <Calculator size={18} style={{ verticalAlign: 'text-bottom', marginRight: 6 }} />
            Calculadora de Dosagem
          </h2>
          <button type="button" onClick={onClose} aria-label="Fechar">
            <X size={18} />
          </button>
        </div>

        <div className="modal__form">
          <div className="form-grid">
            <label className="form-field">
              Peso do Pet (kg)
              <input type="number" min="0" step="0.001" value={peso} onChange={(event) => setPeso(event.target.value)} />
            </label>
            <label className="form-field">
              Dosagem (mg/kg)
              <input type="number" min="0" step="0.01" value={dose} onChange={(event) => setDose(event.target.value)} />
            </label>
            <label className="form-field">
              Concentração (mg/{apresentacao})
              <input
                type="number"
                min="0"
                step="0.01"
                value={concentracao}
                onChange={(event) => setConcentracao(event.target.value)}
              />
            </label>
            <label className="form-field">
              Apresentação
              <select value={apresentacao} onChange={(event) => setApresentacao(event.target.value as Apresentacao)}>
                <option value="ml">Líquido (ml)</option>
                <option value="cp">Comprimido (cp)</option>
              </select>
            </label>
          </div>

          <div className="prontuario-calc__result">
            {resultado !== null ? (
              <>
                <strong>
                  {resultado.toFixed(2)} {apresentacao}
                </strong>
                <p>Esta ferramenta é um apoio de cálculo e não substitui o julgamento clínico do profissional.</p>
              </>
            ) : (
              <p className="empty-state">Preencha peso, dosagem e concentração para calcular.</p>
            )}
          </div>

          <div className="modal__footer">
            <button type="button" className="btn btn--ghost" onClick={onClose}>
              Fechar
            </button>
            <button
              type="button"
              className="btn btn--primary"
              disabled={resultado === null}
              onClick={() => onCopyToEvolucao(resumo)}
            >
              Copiar para Evolução
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
