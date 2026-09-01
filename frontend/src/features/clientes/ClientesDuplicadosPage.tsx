import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { AlertCircle, CheckCircle2, ChevronLeft, Merge } from 'lucide-react'
import { fetchClientesDuplicados, mergeClientesDuplicados } from './api'
import type { ClienteDuplicadoGrupo } from './types'

export function ClientesDuplicadosPage() {
  const [grupos, setGrupos] = useState<ClienteDuplicadoGrupo[]>([])
  const [loading, setLoading] = useState(true)
  const [mergingIdx, setMergingIdx] = useState<number | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [success, setSuccess] = useState<string | null>(null)

  const loadDuplicados = async () => {
    setLoading(true)
    setError(null)
    try {
      const { data } = await fetchClientesDuplicados()
      setGrupos(data || [])
    } catch (err: any) {
      setError(err.message || 'Erro ao carregar clientes duplicados.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    loadDuplicados()
  }, [])

  const handleMerge = async (index: number) => {
    const grupo = grupos[index]
    const ids = grupo.clientes.map((c) => c.id)

    if (!window.confirm(`Tem certeza que deseja mesclar estes ${ids.length} clientes? Apenas o menor ID será mantido.`)) {
      return
    }

    setMergingIdx(index)
    setError(null)
    setSuccess(null)

    try {
      const response = await mergeClientesDuplicados(ids)
      setSuccess(response.message || 'Clientes mesclados com sucesso!')
      
      // Remove o grupo mesclado da lista
      setGrupos((prev) => prev.filter((_, i) => i !== index))
    } catch (err: any) {
      setError(err.message || 'Erro ao mesclar clientes.')
    } finally {
      setMergingIdx(null)
    }
  }

  return (
    <div className="flex h-full flex-col">
      <div className="flex items-center gap-4 border-b border-white/5 p-4 sm:p-6 sm:px-8">
        <Link
          to="/clientes"
          className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/5 text-slate-400 transition-colors hover:bg-white/10 hover:text-white"
        >
          <ChevronLeft className="h-5 w-5" />
        </Link>
        <div>
          <h1 className="text-xl font-bold text-white sm:text-2xl">Clientes Duplicados</h1>
          <p className="text-sm text-slate-400">Mescle registros de clientes com o mesmo nome e telefone.</p>
        </div>
      </div>

      <div className="flex-1 overflow-auto p-4 sm:p-6 sm:px-8">
        {error && (
          <div className="mb-6 flex items-center gap-2 rounded-xl bg-red-500/10 p-4 text-red-500">
            <AlertCircle className="h-5 w-5" />
            <p className="text-sm">{error}</p>
          </div>
        )}

        {success && (
          <div className="mb-6 flex items-center gap-2 rounded-xl bg-emerald-500/10 p-4 text-emerald-500">
            <CheckCircle2 className="h-5 w-5" />
            <p className="text-sm">{success}</p>
          </div>
        )}

        {loading ? (
          <div className="flex h-32 items-center justify-center">
            <div className="h-8 w-8 animate-spin rounded-full border-4 border-emerald-500 border-t-transparent" />
          </div>
        ) : grupos.length === 0 ? (
          <div className="flex flex-col items-center justify-center rounded-2xl border border-white/5 bg-white/5 py-12 text-center">
            <Merge className="mb-4 h-12 w-12 text-slate-600" />
            <h3 className="text-lg font-medium text-white">Nenhum cliente duplicado</h3>
            <p className="mt-1 text-sm text-slate-400">Não foram encontrados clientes com nome e telefone idênticos.</p>
          </div>
        ) : (
          <div className="space-y-6">
            {grupos.map((grupo, index) => (
              <div key={index} className="rounded-2xl border border-white/10 bg-slate-900/50 p-6">
                <div className="mb-4 flex items-start justify-between">
                  <div>
                    <h3 className="text-lg font-medium text-white">{grupo.nome}</h3>
                    <p className="text-sm text-slate-400">
                      Telefone: {grupo.telefones ? grupo.telefones : 'Não informado'}
                    </p>
                  </div>
                  <button
                    onClick={() => handleMerge(index)}
                    disabled={mergingIdx !== null}
                    className="flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 disabled:opacity-50"
                  >
                    {mergingIdx === index ? (
                      <div className="h-4 w-4 animate-spin rounded-full border-2 border-white/20 border-t-white" />
                    ) : (
                      <Merge className="h-4 w-4" />
                    )}
                    <span>Mesclar Todos ({grupo.quantidade})</span>
                  </button>
                </div>
                
                <div className="overflow-x-auto rounded-xl border border-white/5 bg-black/20">
                  <table className="w-full text-left text-sm text-slate-400">
                    <thead className="bg-white/5 text-xs font-medium uppercase text-slate-300">
                      <tr>
                        <th className="px-4 py-3">ID</th>
                        <th className="px-4 py-3">Nome / Apelido</th>
                        <th className="px-4 py-3">CPF</th>
                        <th className="px-4 py-3">E-mail</th>
                        <th className="px-4 py-3 text-right">Ação Esperada</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-white/5">
                      {grupo.clientes.map((cliente, i) => (
                        <tr key={cliente.id} className="hover:bg-white/5">
                          <td className="px-4 py-3 font-medium text-white">#{cliente.id}</td>
                          <td className="px-4 py-3">
                            <div className="text-white">{cliente.nome}</div>
                            {cliente.apelido && <div className="text-xs">{cliente.apelido}</div>}
                          </td>
                          <td className="px-4 py-3">{cliente.cpf || '-'}</td>
                          <td className="px-4 py-3">{cliente.emails || '-'}</td>
                          <td className="px-4 py-3 text-right font-medium">
                            {i === 0 ? (
                              <span className="text-emerald-500">Manter</span>
                            ) : (
                              <span className="text-red-400">Remover</span>
                            )}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
