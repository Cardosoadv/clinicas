const BASE_URL = import.meta.env.VITE_API_BASE_URL

/** Envelope padrão devolvido pela API: {status, message, data, ...}. */
export interface ApiEnvelope<T = unknown> {
  status: 'success' | 'error'
  message?: string
  data?: T
  errors?: Record<string, string>
  [key: string]: unknown
}

export class ApiError extends Error {
  status: number
  body: ApiEnvelope | null

  constructor(message: string, status: number, body: ApiEnvelope | null) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.body = body
  }
}

async function handleResponse<T>(response: Response): Promise<ApiEnvelope<T>> {
  let body: ApiEnvelope<T> | null = null
  try {
    body = (await response.json()) as ApiEnvelope<T>
  } catch {
    // resposta sem corpo JSON (ex.: 204 No Content)
  }

  if (!response.ok) {
    throw new ApiError(body?.message ?? `Erro ${response.status} ao comunicar com o servidor.`, response.status, body)
  }

  return body ?? { status: 'success' }
}

async function request<T>(path: string, init: RequestInit = {}): Promise<ApiEnvelope<T>> {
  let response: Response
  try {
    response = await fetch(`${BASE_URL}${path}`, {
      ...init,
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...init.headers,
      },
    })
  } catch {
    throw new ApiError('Não foi possível conectar ao servidor.', 0, null)
  }

  return handleResponse<T>(response)
}

/**
 * Envio multipart/form-data (upload de arquivo). Sempre via POST — PHP só
 * popula $_POST/$_FILES para multipart em requisições POST, então endpoints
 * que aceitam arquivo usam POST mesmo para atualizar (ver app/Config/Routes.php).
 */
async function requestForm<T>(path: string, formData: FormData): Promise<ApiEnvelope<T>> {
  let response: Response
  try {
    response = await fetch(`${BASE_URL}${path}`, {
      method: 'POST',
      credentials: 'include',
      headers: { Accept: 'application/json' },
      body: formData,
    })
  } catch {
    throw new ApiError('Não foi possível conectar ao servidor.', 0, null)
  }

  return handleResponse<T>(response)
}

export const api = {
  get: <T>(path: string) => request<T>(path, { method: 'GET' }),
  post: <T>(path: string, data?: unknown) =>
    request<T>(path, { method: 'POST', body: data === undefined ? undefined : JSON.stringify(data) }),
  put: <T>(path: string, data?: unknown) =>
    request<T>(path, { method: 'PUT', body: data === undefined ? undefined : JSON.stringify(data) }),
  patch: <T>(path: string, data?: unknown) =>
    request<T>(path, { method: 'PATCH', body: data === undefined ? undefined : JSON.stringify(data) }),
  delete: <T>(path: string) => request<T>(path, { method: 'DELETE' }),
  postForm: <T>(path: string, formData: FormData) => requestForm<T>(path, formData),
}
