---
title: Arquitetura
nav_order: 3
description: "Como o backend (CodeIgniter 4, MVCRS) e o frontend (React + Vite) estão organizados."
---

# Arquitetura
{: .no_toc }

<details open markdown="block">
  <summary>Nesta página</summary>
  {: .text-delta }
1. TOC
{:toc}
</details>

---

## Visão geral

```text
 Navegador
   │
   │  SPA React (frontend/dist)  ── fetch JSON, credentials: 'include' ──┐
   │                                                                     ▼
   │                                               API REST /api/v1 (CodeIgniter 4)
   │                                                 Filtros: cors → apiauth
   │                                                 Controller → Service → Repository → Model
   │                                                                     │
   └──────────── cookie de sessão (Shield) ◄────────────────────────────┤
                                                                         ▼
                                                                MySQL / MariaDB
                                                                writable/ (uploads)
```

- O **frontend** é uma SPA (Single Page Application) que fala só JSON com a API.
- O **backend** não renderiza telas: todas as rotas de negócio ficam em `api/v1/*` e respondem em JSON.
- A **autenticação** é por sessão (cookie) do CodeIgniter Shield. Não há token no `localStorage`.
- **Arquivos enviados** (avatares, exames, logos, comprovantes) ficam em `backend/writable/` e são servidos por endpoints autenticados, nunca por URL pública direta.

## Backend — padrão MVCRS

O backend segue **Model–View–Controller–Repository–Service**. Cada camada tem uma responsabilidade:

| Camada | Pasta | Responsabilidade |
|:--|:--|:--|
| **Controller** | `app/Controllers/V1/` | Lê a requisição, valida a entrada, chama o Service e devolve JSON. Não contém regra de negócio. |
| **Service** | `app/Services/` | Regras de negócio e orquestração: transações, integração entre módulos (faturamento ↔ pacotes ↔ estoque), geração de links, importações. |
| **Repository** | `app/Repositories/` | Consultas ao banco (joins, agregações, filtros). Isola o Service do Query Builder. |
| **Model** | `app/Models/` | Mapeamento da tabela do CodeIgniter: `allowedFields`, regras de validação, *soft delete* e timestamps. |
| **View** | `app/Views/` | Só páginas de erro do framework — a interface fica no React. |

Arquivos de apoio:

| Arquivo | Função |
|:--|:--|
| `app/Controllers/BaseController.php` | Helpers `getRequestData()` (aceita JSON, form ou multipart), `apiResponse()`, `apiError()`, `apiNotFound()`, `apiValidationError()` (HTTP 422). |
| `app/Services/BaseService.php` | CRUD genérico (`findAll`, `findById`, `create`, `update`, `delete`) e o envelope `success()` / `error()`. |
| `app/Repositories/BaseRepository.php` | CRUD genérico sobre o Model, paginação e acesso ao Model. |
| `app/Filters/ApiAuth.php` | Filtro que exige sessão. Responde **401** (não autenticado) ou **403** (banido ou não ativado) em JSON, em vez do redirect HTML padrão do Shield. |
| `app/Validation/DocumentoRules.php` | Regras de validação `valid_cpf` e `valid_cnpj` (dígitos verificadores). |
| `app/Config/Cors.php` | CORS com `supportsCredentials = true`. As origens vêm de `cors.allowedOrigins` no `.env`. |
| `app/Config/Routes.php` e `app/Routes/Routes.php` | Definição das rotas (o segundo é carregado via `Config/Routing::$routeFiles`). |
| `app/Database/Migrations/` | Estrutura do banco. |

### Envelope de resposta

Toda resposta da API segue o mesmo formato:

```json
{ "status": "success", "message": "Cobrança criada com sucesso", "data": { }, "id": 42 }
```

```json
{ "status": "error", "message": "Dados inválidos.", "errors": { "paciente_nome": "O campo é obrigatório." } }
```

| HTTP | Quando |
|:--|:--|
| 200 / 201 | Sucesso |
| 400 | Erro de negócio (ex.: saldo do pacote insuficiente) |
| 401 | Sem sessão |
| 403 | Usuário banido ou não ativado |
| 404 | Registro não encontrado |
| 422 | Validação falhou (`errors` traz os campos) |
| 500 | Erro ao gravar |

### Ciclo de uma requisição

1. **Preflight**: o `OPTIONS (:any)` responde 204 e o filtro global `cors` anexa os headers. Sem essa rota coringa, o preflight de rotas POST/PUT/DELETE cairia em 404.
2. **Filtro `cors`** (global, *before*).
3. **Filtro `apiauth`** no grupo `api/v1` — exceto `api/v1/auth/*` e `api/v1/publico/*`.
4. **Controller** valida e chama o **Service**.
5. O **Service** abre transação quando a operação mexe em várias tabelas (recorrências, pacotes, estoque, mesclagem de clientes, `mix-create`).
6. O **Repository / Model** grava. Models com *soft delete* só marcam `deleted_at`.

### Integrações entre serviços

```text
AgendaService ──faturar──► FatService ──Pago + serviço──► EstoqueService (baixa BOM)
      │                        │
      └──forma "Pacote"──► PacoteService.consumir()
                               ▲
PacoteService.createPacote ────┘ gera a cobrança "Compra de Pacote"

EstoqueService.registrarEntrada ──lançar no financeiro──► FatService.createDespesa
ComunicacaoService ──► ConfigRepository (templates) + LojasService (nome da clínica)
```

Os detalhes estão em [Fluxos integrados]({{ site.baseurl }}/fluxos).

### Testes

Os testes unitários ficam em `backend/tests/unit/Services/` — um arquivo por Service (Agenda, Clientes, Comunicação, Equipe, Estoque, Faturamento, Lojas, Pacotes, Pets, Prescrições, Prontuários, Relatórios, Serviços e as quatro importações). Veja [Desenvolvimento]({{ site.baseurl }}/desenvolvimento#testes).

## Frontend — React + Vite

```text
frontend/src/
├── main.tsx              # BrowserRouter com basename = BASE_PATH
├── App.tsx               # mapa de rotas
├── lib/
│   ├── api.ts            # cliente HTTP (fetch, credentials: 'include', ApiError)
│   ├── document.ts       # helpers de documento (CPF/CNPJ)
│   └── a11y.ts           # helpers de acessibilidade
├── layouts/
│   ├── AdminLayout.tsx   # menu lateral + topbar
│   ├── navConfig.ts      # itens do menu (Principal, Operação, Financeiro, Sistema)
│   └── TopbarSearch.tsx  # busca global (clientes, pacientes, serviços)
├── components/           # ProtectedRoute, PacientePicker, PacienteAvatar, ReportTemplate
├── hooks/                # useClickOutside, useEscapeKey
├── pages/                # Dashboard e placeholder "em breve"
└── features/<módulo>/    # uma pasta por módulo:
    ├── *Page.tsx         #   telas
    ├── *Modal.tsx        #   formulários em modal
    ├── api.ts            #   chamadas à API do módulo
    ├── types.ts          #   tipos TypeScript
    └── *.css             #   estilos do módulo
```

Pontos de projeto:

- **Configuração em runtime**: `public/config.js` define `window.APP_CONFIG` (`API_BASE_URL`, `BASE_PATH`). O `index.html` injeta `<base href>` a partir de `BASE_PATH`, e o Vite usa `base: './'`. Por isso o mesmo build serve em qualquer subpasta.
- **Rotas protegidas**: `ProtectedRoute` consulta `GET /auth/me` (via `AuthContext`) e manda para `/login` quando não há sessão.
- **Contextos**: `AuthContext` (usuário logado), `LojaPrincipalContext` (identidade visual), `ProntuarioContext` e `FaturamentoContext` (dados compartilhados entre as abas).
- **Impressão**: `ReportTemplate` monta cabeçalho e rodapé a partir dos templates `report_header` / `report_footer` e dos dados da loja principal.
- **Acessibilidade**: combobox ARIA na busca global, navegação por teclado (Enter/Espaço em elementos `role="button"`, setas na busca) e `aria-label` em campos de linhas dinâmicas.

### Mapa de telas

| Rota | Tela |
|:--|:--|
| `/login` | Login |
| `/recibo/:id/:hash` | Recibo público (sem login) |
| `/` | Dashboard |
| `/agenda`, `/agenda/todos` | Agenda do dia e lista de todos os agendamentos |
| `/clientes`, `/clientes/:id`, `/clientes/duplicados` | Clientes, detalhe do cliente e mesclagem de duplicados |
| `/pacientes` | Pacientes |
| `/prontuarios`, `/prontuarios/:id/{anamnese,historico,vacinas,peso,prescricoes,evolucao,imagens}` | Prontuários |
| `/servicos`, `/pacotes`, `/estoque`, `/equipe`, `/lojas` | Cadastros operacionais |
| `/faturamento/{dashboard,cobrancas,despesas,notas}` | Faturamento |
| `/relatorios/{extrato,dre,livro-caixa}` | Relatórios |
| `/configuracoes` | Templates de mensagens e de impressão |
