# Sistema de Gestão para Clínicas (Clinicas) - v1.1.0

Sistema de gestão para clínicas: agenda, prontuários, faturamento e relatórios financeiros. Desenvolvido em CodeIgniter 4 com padrão MVCRS e frontend em React + TypeScript + Vite.

📖 **Documentação completa:** [cardosoadv.github.io/clinicas](https://cardosoadv.github.io/clinicas/) — módulos, fluxos, API e banco de dados.

## Tecnologias

- **Frontend**: React + TypeScript + Vite
- **Backend**: CodeIgniter 4 (PHP)
- **Gerenciamento**: NPM e Composer

## Estrutura do Projeto

- `frontend/`: Aplicação React
- `backend/`: API em CodeIgniter 4
- `docs/`: Documentação técnica publicada no [GitHub Pages](https://cardosoadv.github.io/clinicas/)
- `backend/app/Docs/`: Manual do usuário, lido dentro do sistema (menu **Documentação** e botão **?** de cada tela) — veja [Documentação interna](https://cardosoadv.github.io/clinicas/documentacao-interna)

## Como rodar o projeto localmente

Pré-requisitos:
- Node.js e NPM
- PHP 8.1+ e Composer

1. Instale as dependências (frontend e backend):
```bash
npm run setup
```

2. Inicie os servidores de desenvolvimento:
```bash
npm run dev
```

Este comando inicia, simultaneamente, o servidor Vite (frontend) e o servidor Spark (backend CodeIgniter).
