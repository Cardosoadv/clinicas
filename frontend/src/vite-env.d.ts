/// <reference types="vite/client" />

/** Config de runtime carregada por /config.js (ver public/config.js). */
interface AppConfig {
  readonly API_BASE_URL: string
  readonly BASE_PATH?: string
}

interface Window {
  APP_CONFIG: AppConfig
}
