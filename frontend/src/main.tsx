import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import './index.css'
import './styles/shared.css'
import App from './App.tsx'

// Mesmo subcaminho de deploy do <base href> em index.html
const basePath = window.APP_CONFIG?.BASE_PATH || '/clinicas';

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <BrowserRouter basename={basePath}>
      <App />
    </BrowserRouter>
  </StrictMode>,
)
