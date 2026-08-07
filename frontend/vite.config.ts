import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  // Usar base relativa para que os assets sejam resolvidos em relação
  // à tag <base href="..."> do index.html (que pode ser editada em prod).
  base: './',
  plugins: [react()],
  server: {
    // Porta fixa: o backend (Cors.php) autoriza um allowlist explícito de
    // origens (exigido junto com supportsCredentials=true para o cookie de
    // sessão funcionar), então a porta do dev server não pode ficar variando
    // silenciosamente quando 5173 já estiver ocupada.
    port: 5173,
    strictPort: true,
  },
})
