---
title: Desenvolvimento
nav_order: 8
description: "Guia para quem vai manter ou evoluir o código: scripts, testes, convenções, deploy e pontos de atenção."
---

# Desenvolvimento
{: .no_toc }

<details open markdown="block">
  <summary>Nesta página</summary>
  {: .text-delta }
1. TOC
{:toc}
</details>

---

## Scripts

### Raiz (`package.json`)

| Script | O que faz |
|:--|:--|
| `npm run setup` | Instala as dependências do frontend (npm) e do backend (Composer) |
| `npm run serve` | Sobe a API com `php backend/spark serve` (http://localhost:8080) |
| `npm run dev:frontend` | Sobe o Vite (http://localhost:5173) |
| `npm run dev` | Sobe os dois com `concurrently` — **precisa do script `dev:backend`**, que ainda não existe (veja [Instalação](instalacao#rodar-em-desenvolvimento)) |
| `npm run build` | Gera `frontend/dist/` |

### Frontend (`frontend/package.json`)

| Script | O que faz |
|:--|:--|
| `npm run dev` | Servidor de desenvolvimento (porta fixa 5173) |
| `npm run build` | `tsc -b` (checagem de tipos) + `vite build` |
| `npm run lint` | `oxlint` (regras `react/rules-of-hooks` e `react/only-export-components`) |
| `npm run preview` | Serve o build localmente |

### Backend (`backend/composer.json`)

| Script | Comando |
|:--|:--|
| `composer test` | `phpunit` |
| `composer serve` | `php spark serve` |
| `composer migrate` / `migrate:rollback` / `migrate:status` | Migrations |
| `composer routes` | Lista as rotas |
| `composer cache:clear` | Limpa o cache |

## Testes

```bash
cd backend
composer test                                   # todos
vendor/bin/phpunit tests/unit/Services/PacoteServiceTest.php   # um arquivo
vendor/bin/phpunit --filter NomeDoTeste         # um teste
```

- Os testes de `tests/unit/Services/` usam **mocks** dos repositórios (`createMock`) e **não precisam de banco**.
- `tests/database/ExampleDatabaseTest.php` usa banco: configure `database.tests.*` no `.env` ou no `phpunit.xml`.
- A cobertura (`app/`, sem `app/Views`) é configurada em `phpunit.dist.xml`.

No frontend, a verificação é `npm run lint` e `npm run build` (o `tsc -b` falha com erro de tipo).

## Convenções

### Backend

- **Uma camada por responsabilidade** (veja [Arquitetura](arquitetura)): Controller fino → Service com a regra → Repository com a consulta → Model com campos e validação.
- Controllers ficam em `App\Controllers\V1` e devolvem sempre pelo `apiResponse()` / `apiError()` / `apiValidationError()`.
- Services devolvem `['status' => 'success'|'error', 'message' => ..., ...]` via `success()` / `error()`.
- Dependências entram pelo construtor, com valor padrão (`?Repo $repo = null`), o que facilita os mocks nos testes.
- Operações em várias tabelas usam `transStart()` / `transComplete()`.
- Uploads em `multipart/form-data` usam **POST** (o PHP só preenche `$_FILES` em POST).
- Arquivos enviados ficam em `writable/` e são servidos por um endpoint autenticado.
- Comentários e mensagens em **português**.

### Frontend

- Uma pasta por módulo em `src/features/<modulo>/` com `api.ts`, `types.ts`, páginas, modais e CSS.
- Toda chamada passa por `src/lib/api.ts` (`credentials: 'include'`, erros como `ApiError`).
- Nada de URL fixa: use `window.APP_CONFIG` (`config.js`).
- Acessibilidade: `aria-label` em botões só com ícone e em campos sem `<label>`; Enter e Espaço em elementos `role="button"`; padrão combobox ARIA nas buscas.

### Adicionar um módulo novo

1. **Migration** em `backend/app/Database/Migrations/` → `php spark migrate`.
2. **Model** em `app/Models/` (`allowedFields`, `validationRules`, `useSoftDeletes`).
3. **Repository** em `app/Repositories/` estendendo `BaseRepository`.
4. **Service** em `app/Services/` estendendo `BaseService`.
5. **Controller** em `app/Controllers/V1/` estendendo `BaseController`.
6. **Rotas** no grupo `api/v1` de `app/Config/Routes.php` (já protegido pelo filtro `apiauth`).
7. **Teste** em `tests/unit/Services/`.
8. No frontend: `src/features/<modulo>/` (api, types, página), rota em `App.tsx` e item de menu em `layouts/navConfig.ts`.
9. Rode `npm run build` e **commite o `frontend/dist/`** — é ele que vai para o servidor.

## Deploy

- Push na `main` → GitHub Actions (`.github/workflows/deploy.yml`) → FTP para a Hostinger. Detalhes em [Instalação → Implantar em produção](instalacao#producao).
- O `frontend/dist/` é versionado: sem rodar o build, mudanças no frontend não chegam à produção.
- Migrations novas precisam ser aplicadas no servidor (`php spark migrate`).

## Agentes de manutenção (`.agents/`)

A pasta `.agents/` guarda instruções (`skill.md`) e relatórios (`report.md`) de agentes de IA usados na manutenção do projeto:

| Agente | Foco |
|:--|:--|
| ⚡ Bolt | Desempenho (consultas N+1, carregamento de assets) |
| 🎨 Palette | Experiência de uso e acessibilidade |
| 🛡️ Sentinel | Segurança |
| 🧹 CodeHealth | Manutenibilidade |
| 🎨 Davinci | Descoberta de funcionalidades |

`relatorio_evolucao.md` traz o histórico de evolução do sistema (originalmente "Petys", derivado do projeto odontológico "Oralys"). O arquivo `.Jules/palette.md` reúne aprendizados de acessibilidade.

## Pontos de atenção encontrados na análise

Itens observados no código durante a elaboração desta documentação — bons candidatos a correção:

| # | Onde | Situação |
|:--|:--|:--|
| 1 | `app/Config/Events.php` | O evento `cobranca_paga` é disparado, mas não tem ouvinte: **pacotes não são ativados** automaticamente quando a cobrança é paga. |
| 2 | `AgendaService::billAppointment` + `FatService::createCobranca` | Faturar um agendamento com forma **Pacote** chama `consumir()` nos dois métodos: o pacote é **consumido duas vezes** (ou a segunda chamada falha e a cobrança não é criada, se o saldo acabar). |
| 3 | `EquipeService::create` | Adiciona o usuário ao grupo `equipe`, que não existe em `AuthGroups.php` — o Shield lança exceção. |
| 4 | `package.json` (raiz) | `npm run dev` chama `dev:backend`, que não existe. |
| 5 | `Publico::recibo` | O QR Code do recibo aponta para o endpoint JSON da API, não para a página `/recibo/{id}/{hash}`. |
| 6 | `README.md` / `composer.json` | Pedem PHP 8.1, mas o `composer.lock` exige PHP 8.2. |
| 7 | `EquipeService::create` | Senha padrão fixa (`Petys@2026`) para logins criados sem senha. |
| 8 | Frontend | Funções que existem só na API: importação CSV, links automáticos de WhatsApp, troca de senha, odontograma, despesas parceladas/recorrentes, `mix-create`. |
| 9 | `app/Config/Routes.php` + `app/Routes/Routes.php` | Rotas duplicadas em dois arquivos, com pequenas diferenças (veja [API](api#como-chamar)). |
