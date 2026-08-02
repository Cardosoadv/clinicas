import { api, type ApiEnvelope } from '../../lib/api'
import type { TemplateConfig } from './types'

export function fetchTemplates(): Promise<ApiEnvelope<TemplateConfig[]>> {
  return api.get<TemplateConfig[]>('/configuracoes/templates')
}

export function updateTemplate(metaKey: string, metaValue: string): Promise<ApiEnvelope<null>> {
  return api.put<null>('/configuracoes/templates', { meta_key: metaKey, meta_value: metaValue })
}
