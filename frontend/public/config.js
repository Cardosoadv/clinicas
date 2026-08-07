// Configuração de ambiente carregada em tempo de execução (runtime), não em
// build time. O Vite copia este arquivo como está para dist/config.js, sem
// processar ou versionar o nome — então é possível trocar a URL da API em
// um ambiente já buildado (inclusive o dist/ versionado neste repositório)
// só editando este arquivo, sem rodar `npm run build` de novo.
window.APP_CONFIG = {
  API_BASE_URL: 'http://localhost/clinicas/api/v1',
}
