import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import './index.css'
import './styles/shared.css'
import App from './App.tsx'

// Mesmo subcaminho de deploy do <base href> em index.html e do .htaccess da raiz do projeto.
createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <BrowserRouter basename="/clinicas">
      <App />
    </BrowserRouter>
  </StrictMode>,
)
