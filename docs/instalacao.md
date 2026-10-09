---
title: Instalação
nav_order: 2
description: "Como instalar o Clínicas em desenvolvimento local, no Apache (XAMPP/WAMP) e em hospedagem compartilhada."
---

# Instalação
{: .no_toc }

Esta página mostra como colocar o sistema para rodar em três cenários: **desenvolvimento local** (servidores do Vite e do Spark), **Apache local** (XAMPP, WAMP, Laragon) e **produção** em hospedagem compartilhada (o projeto já vem pronto para a Hostinger).
{: .fs-5 .fw-300 }

<details open markdown="block">
  <summary>Nesta página</summary>
  {: .text-delta }
1. TOC
{:toc}
</details>

---

## 1. Pré-requisitos

| Ferramenta | Versão | Observação |
|:--|:--|:--|
| PHP | **8.2 ou superior** | O `composer.lock` fixa CodeIgniter 4.7, Shield 1.4 e php-qrcode 6, que exigem PHP 8.2. |
| Extensões PHP | `intl`, `mbstring`, `mysqli`, `json`, `ctype`, `fileinfo` | `intl` e `mbstring` são exigidas pelo CodeIgniter. `fileinfo` é usada no upload de imagens e comprovantes. |
| Composer | 2.x | Instala as dependências do backend. |
| Node.js | **20.19+ ou 22.12+** | Exigência do Vite 8. |
| npm | 10+ | Acompanha o Node. Também há `pnpm-lock.yaml`, se preferir pnpm. |
| MySQL / MariaDB | MySQL 5.7+ / MariaDB 10.3+ | Charset `utf8mb4`. |
| Git | qualquer | Para clonar o repositório. |

{: .nota }
O `README.md` e o `composer.json` citam PHP 8.1, mas as dependências travadas no `composer.lock` só instalam no PHP 8.2+.

## 2. Obter o código

```bash
git clone https://github.com/Cardosoadv/clinicas.git
cd clinicas
```

Estrutura principal:

```text
clinicas/
├── .htaccess            # roteamento Apache: /api → backend, resto → frontend/dist
├── package.json         # scripts que orquestram frontend + backend
├── backend/             # API em CodeIgniter 4
│   ├── app/             # código da aplicação (Controllers, Services, Repositories...)
│   ├── public/          # front controller (index.php)
│   ├── writable/        # cache, logs, sessões e uploads (precisa de escrita)
│   ├── env              # modelo de configuração → copie para .env
│   └── spark            # CLI do CodeIgniter
├── frontend/            # SPA em React + Vite
│   ├── public/config.js # configuração de runtime (URL da API e base path)
│   ├── src/
│   └── dist/            # build de produção (versionado no repositório)
└── docs/                # esta documentação (GitHub Pages)
```

## 3. Instalar as dependências

Na raiz do projeto:

```bash
npm install          # instala o "concurrently" usado pelo script dev
npm run setup        # = npm --prefix frontend install && composer --working-dir=backend install
```

## 4. Criar o banco de dados

```sql
CREATE DATABASE clinicas CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'clinicas'@'localhost' IDENTIFIED BY 'troque-esta-senha';
GRANT ALL PRIVILEGES ON clinicas.* TO 'clinicas'@'localhost';
FLUSH PRIVILEGES;
```

## 5. Configurar o backend (`backend/.env`)

Copie o modelo e edite:

```bash
cp backend/env backend/.env
```

Configuração mínima:

```ini
CI_ENVIRONMENT = development

# URL onde o backend responde (com barra no final)
app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = clinicas
database.default.username = clinicas
database.default.password = troque-esta-senha
database.default.DBDriver = MySQLi
database.default.port = 3306

# Origens do frontend autorizadas pelo CORS (separadas por vírgula).
# Padrão quando ausente: http://localhost:5173,http://localhost:3000
cors.allowedOrigins = 'http://localhost:5173'
```

Gere a chave de criptografia (grava `encryption.key` no `.env`):

```bash
php backend/spark key:generate
```

{: .importante }
O `.env` contém senhas e **não deve ser versionado** — ele já está no `.gitignore` e é excluído do deploy por FTP.

### Permissões de escrita

A pasta `backend/writable/` guarda sessões, cache, logs e todos os arquivos enviados pelos usuários:

| Pasta | Conteúdo |
|:--|:--|
| `writable/session/` | Sessões de login |
| `writable/imagens/{paciente_id}/` | Avatar e imagens/exames de cada paciente |
| `writable/imagens/loja/` | Logos das lojas |
| `writable/despesas/{despesa_id}/` | Comprovantes de despesas |
| `writable/logs/`, `writable/cache/` | Logs e cache do framework |

No Linux:

```bash
chmod -R 775 backend/writable
chown -R www-data:www-data backend/writable   # ajuste para o usuário do seu servidor web
```

## 6. Criar as tabelas (migrations)

```bash
php backend/spark migrate --all
```

O `--all` roda as migrations da aplicação **e** as dos pacotes (Shield cria as tabelas `users`, `auth_identities`, `auth_logins`, `auth_remember_tokens`, `auth_groups_users`...; Settings cria `settings`). Para conferir:

```bash
php backend/spark migrate:status
```

As tabelas da aplicação estão descritas em [Banco de dados]({{ site.baseurl }}/banco-de-dados).

## 7. Criar o primeiro usuário
{: #primeiro-usuario}

Não existe tela de cadastro público. Crie o administrador pela linha de comando do Shield:

```bash
php backend/spark shield:user create
# informe username, e-mail e senha (mínimo de 8 caracteres)

php backend/spark shield:user addgroup -e admin@suaclinica.com.br -g admin
```

Os próximos usuários podem ser criados pela tela [Equipe]({{ site.baseurl }}/modulos/equipe), que cria o login junto com o cadastro do profissional.

## 8. Configurar o frontend (`frontend/public/config.js`)

O frontend lê a URL da API **em tempo de execução**, não no build. O arquivo é copiado como está para `dist/config.js`, então dá para trocar a API de um ambiente já compilado só editando esse arquivo.

```js
window.APP_CONFIG = {
  // URL completa até /api/v1, sem barra no final
  API_BASE_URL: 'http://localhost:8080/api/v1',
  // Caminho onde o frontend é servido (usado na tag <base> e no React Router)
  BASE_PATH: '/',
}
```

| Cenário | `API_BASE_URL` | `BASE_PATH` |
|:--|:--|:--|
| Desenvolvimento (Vite + Spark) | `http://localhost:8080/api/v1` | `/` |
| Apache local em `htdocs/clinicas` (padrão do repositório) | `http://localhost/clinicas/api/v1` | `/clinicas` |
| Produção em `https://seudominio.com.br/` | `https://seudominio.com.br/api/v1` | `/` |
| Produção em subpasta `https://seudominio.com.br/clinica/` | `https://seudominio.com.br/clinica/api/v1` | `/clinica` |

## 9. Rodar em desenvolvimento
{: #rodar-em-desenvolvimento}

Abra dois terminais na raiz do projeto:

```bash
# Terminal 1 — API em http://localhost:8080
npm run serve            # = php backend/spark serve

# Terminal 2 — frontend em http://localhost:5173
npm run dev:frontend     # = npm --prefix frontend run dev
```

Acesse **http://localhost:5173** e entre com o usuário criado no passo 7.

{: .atencao }
O script `npm run dev` da raiz chama `dev:backend`, que não existe no `package.json` (o script do backend se chama `serve`). Até isso ser corrigido, use os dois comandos acima, ou acrescente `"dev:backend": "php backend/spark serve"` aos scripts.

{: .nota }
O Vite roda com `strictPort: true` na porta **5173**. Se a porta estiver ocupada ele não troca de porta sozinho, porque o CORS do backend só libera as origens listadas em `cors.allowedOrigins` — e o cookie de sessão depende disso.

## 10. Instalar no Apache local (XAMPP / WAMP / Laragon)

O `.htaccess` da raiz faz o roteamento completo, então o projeto pode ficar inteiro dentro do `htdocs`:

1. Coloque o projeto em `htdocs/clinicas` (com `mod_rewrite` ligado e `AllowOverride All`).
2. No `backend/.env`, use `app.baseURL = 'http://localhost/clinicas/'`.
3. Em `frontend/public/config.js` (e em `frontend/dist/config.js`, se não for gerar build), use `API_BASE_URL: 'http://localhost/clinicas/api/v1'` e `BASE_PATH: '/clinicas'`.
4. Gere o build: `npm run build`.
5. Acesse **http://localhost/clinicas**.

Como o `.htaccess` resolve as requisições:

| Requisição | Vai para |
|:--|:--|
| `/api/...` | `backend/public/index.php/api/...` (API) |
| `/backend/...` | `backend/public/...` |
| `.../config.js` (de qualquer rota aninhada) | `frontend/dist/config.js` |
| Qualquer outra | `frontend/dist/...` |
| Arquivo inexistente em `dist` | `frontend/dist/index.html` (fallback da SPA) |

Neste cenário frontend e API ficam na mesma origem, então não há CORS envolvido.

## 11. Implantar em produção
{: #producao}

### Build do frontend

```bash
npm run build      # roda tsc -b e vite build → frontend/dist/
```

A pasta `frontend/dist/` é **versionada** no repositório: o deploy envia o build já pronto, sem Node no servidor. Lembre de rodar o build e commitar o `dist/` antes de publicar uma mudança de frontend.

### Deploy automático (GitHub Actions → Hostinger)

O workflow `.github/workflows/deploy.yml` roda a cada push na branch `main` e envia os arquivos por FTP (ação `SamKirkland/FTP-Deploy-Action`) para duas pastas da hospedagem (`/public_html/biacardoso/` e `/public_html/casadospets/`). Ficam de fora: `.git`, `.env`, `node_modules`, `backend/vendor`, `backend/writable`, `backend/tests`, o código-fonte do frontend, `.github`, `.agents` e `docs`.

Para usar o deploy:

1. Em **Settings → Secrets and variables → Actions** do repositório, cadastre `FTP_SERVER`, `FTP_USERNAME` e `FTP_PASSWORD`.
2. Ajuste os `server-dir` no workflow para as suas pastas.
3. **Na primeira instalação**, faça no servidor (por SSH ou pelo gerenciador de arquivos):
   - `composer install --no-dev --optimize-autoloader` em `backend/` (o `vendor/` não é enviado);
   - crie `backend/.env` com `CI_ENVIRONMENT = production`, `app.baseURL`, banco e `encryption.key`;
   - crie `backend/writable/` com as subpastas e permissão de escrita;
   - rode `php spark migrate --all` e crie o primeiro usuário (passo 7);
   - edite `frontend/dist/config.js` com a URL de produção.

{: .importante }
Em produção use **HTTPS** e `CI_ENVIRONMENT = production` — em `development` o CodeIgniter mostra a pilha de erros e a Debug Toolbar.

### Deploy manual

Envie por FTP/SFTP a raiz do projeto com o mesmo conjunto de exclusões listado acima e siga o passo 3 do deploy automático.

## 12. Publicar esta documentação (GitHub Pages)

1. Em **Settings → Pages**, escolha **Source: Deploy from a branch**.
2. Selecione a branch `main` e a pasta **`/docs`** e salve.
3. Em alguns minutos o site fica em `https://cardosoadv.github.io/clinicas/`.

Para pré-visualizar localmente (precisa de Ruby):

```bash
cd docs
bundle install
bundle exec jekyll serve
# http://localhost:4000
```

## 13. Problemas comuns

| Sintoma | Causa provável | Solução |
|:--|:--|:--|
| Login funciona, mas toda chamada seguinte dá **401** | O cookie de sessão não volta para a API (origem diferente sem CORS) | Inclua a origem exata do frontend em `cors.allowedOrigins`; use o mesmo host (`localhost` vs `127.0.0.1` contam como origens diferentes). |
| Erro de **CORS** no console | Origem do frontend fora da lista, ou porta diferente de 5173 | Ajuste `cors.allowedOrigins` no `.env`. |
| Tela branca ou assets 404 em produção | `BASE_PATH` errado no `config.js` | Use o caminho em que o site está publicado. |
| 404 ao recarregar uma rota (ex.: `/agenda`) | `mod_rewrite` desligado ou `.htaccess` ignorado | Ative `mod_rewrite` e `AllowOverride All`. |
| `Class "Locale" not found` / erro de intl | Extensão `intl` ausente | Instale/ative `php-intl`. |
| Upload de foto/comprovante falha | `writable/` sem permissão de escrita | Ajuste as permissões (passo 5). |
| `composer install` falha por versão do PHP | PHP abaixo de 8.2 | Atualize o PHP. |
| Erro ao cadastrar membro da equipe com e-mail | Grupo `equipe` não existe no Shield | Veja [Equipe → Limitações conhecidas]({{ site.baseurl }}/modulos/equipe#limitacoes-conhecidas). |
