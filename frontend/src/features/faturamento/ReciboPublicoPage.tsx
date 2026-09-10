import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { ApiError } from '../../lib/api'
import { fetchReciboPublico } from './api'
import type { ReciboPublicoData } from './types'
import './ReciboPublico.css'

function formatCurrency(value: number): string {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(value: string): string {
  try {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(value))
  } catch {
    return value
  }
}

export function ReciboPublicoPage() {
  const { id, hash } = useParams<{ id: string; hash: string }>()
  const [recibo, setRecibo] = useState<ReciboPublicoData | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (!id || !hash) {
      setError('Link de recibo inválido.')
      return
    }
    fetchReciboPublico(Number(id), hash)
      .then((res) => setRecibo(res.data ?? null))
      .catch((err) => setError(err instanceof ApiError ? err.message : 'Não foi possível carregar o recibo.'))
  }, [id, hash])

  if (error) {
    return (
      <div className="recibo-publico recibo-publico--center">
        <p className="empty-state empty-state--error">{error}</p>
      </div>
    )
  }

  if (!recibo) {
    return (
      <div className="recibo-publico recibo-publico--center">
        <p className="empty-state">Carregando recibo...</p>
      </div>
    )
  }

  const { nota, cobranca, paciente, tutor_nome, tutor_telefone, loja, qr_code_base64 } = recibo

  return (
    <div className="recibo-publico">
      <div className="recibo-publico__actions no-print">
        <button type="button" className="btn btn--primary" onClick={() => window.print()}>
          Imprimir
        </button>
      </div>

      <div className="recibo-publico__document">
        <header className="recibo-publico__header">
          <div>
            <h1>{loja?.nome ?? 'Clínica Veterinária'}</h1>
            <p>
              {[loja?.endereco, [loja?.cidade, loja?.estado].filter(Boolean).join('/')].filter(Boolean).join(' — ')}
            </p>
            <p>
              {[loja?.telefone && `Tel: ${loja.telefone}`, loja?.cnpj && `CNPJ: ${loja.cnpj}`]
                .filter(Boolean)
                .join(' · ')}
            </p>
          </div>
          <div className="recibo-publico__tipo">{nota.tipo}</div>
        </header>

        <h2 className="recibo-publico__titulo">
          {nota.tipo} Nº {String(nota.id).padStart(3, '0')}
        </h2>

        <section className="recibo-publico__dados">
          <div>
            <span className="recibo-publico__label">Paciente</span>
            <span>{paciente?.paciente_nome ?? 'Não informado'}</span>
          </div>
          <div>
            <span className="recibo-publico__label">Tutor(a)</span>
            <span>{tutor_nome}</span>
          </div>
          <div>
            <span className="recibo-publico__label">Telefone</span>
            <span>{tutor_telefone}</span>
          </div>
          {nota.cpf_responsavel && (
            <div>
              <span className="recibo-publico__label">CPF</span>
              <span>{nota.cpf_responsavel}</span>
            </div>
          )}
          <div>
            <span className="recibo-publico__label">Data de emissão</span>
            <span>{formatDate(nota.data_emissao)}</span>
          </div>
        </section>

        <section className="recibo-publico__descricao">
          <span className="recibo-publico__label">Referente a</span>
          <p>{nota.descricao}</p>
        </section>

        <section className="recibo-publico__valores">
          {cobranca && cobranca.desconto > 0 && (
            <div>
              <span>Desconto</span>
              <span>{formatCurrency(cobranca.desconto)}</span>
            </div>
          )}
          <div className="recibo-publico__total">
            <span>Valor total</span>
            <span>{formatCurrency(nota.valor)}</span>
          </div>
        </section>

        <footer className="recibo-publico__footer">
          <div className="recibo-publico__qrcode">
            {qr_code_base64 && <img src={qr_code_base64} alt="QR Code de verificação do recibo" />}
            <span>Escaneie para verificar a autenticidade deste recibo</span>
          </div>
          <p className="recibo-publico__hash">Autenticação: {nota.hash}</p>
        </footer>
      </div>
    </div>
  )
}
