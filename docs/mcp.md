---
title: Servidor MCP (Claude e Gemini)
nav_order: 11
description: "Conexão externa de Claude Chat e Gemini Chat ao sistema via Model Context Protocol."
---

# Servidor MCP (Claude e Gemini)
{: .no_toc }

O backend expõe um servidor [MCP](https://modelcontextprotocol.io) (Model Context Protocol) remoto em **`POST /mcp`**, para que assistentes externos — Claude Chat e Gemini Chat — cadastrem clientes e pacientes e criem agendamentos conversando em linguagem natural.

Transporte: *Streamable HTTP* (JSON-RPC 2.0, uma resposta JSON por requisição). Toda escrita passa pelos mesmos Services da API REST, então as regras de negócio (CPF duplicado, validações) valem igualmente.

## Ativação

1. Publique o backend em **HTTPS** com URL pública (Claude e Gemini acessam a partir da nuvem deles) e rode as migrations (`php spark migrate`) — elas criam `mcp_oauth_clients` e `mcp_oauth_codes`.
2. Pronto: a autenticação padrão é **OAuth 2.1** (veja abaixo). Não é preciso configurar nada no `.env`.
3. *(Opcional)* Para clientes sem OAuth, defina um token estático (mín. 32 caracteres) em `mcp.apiToken` no `.env`:

   ```bash
   php -r "echo bin2hex(random_bytes(32));"
   ```

> Qualquer token válido dá acesso às ferramentas abaixo. Trate o token estático como senha e rotacione-o se vazar.

## Autenticação OAuth 2.1

Mesmo desenho do OrdoJuris: o backend é também o *Authorization Server* (`app/Controllers/OAuth.php`).

| Endpoint | Função |
|---|---|
| `GET /.well-known/oauth-protected-resource` | Descoberta do recurso (RFC 9728) — apontado pelo `WWW-Authenticate` dos 401 |
| `GET /.well-known/oauth-authorization-server` | Metadados do servidor de autorização (RFC 8414) |
| `POST /oauth/register` | Registro dinâmico de cliente (RFC 7591) — o Claude usa sozinho |
| `GET/POST /oauth/authorize` | Tela de login (e-mail e senha do sistema) + consentimento |
| `POST /oauth/token` | Troca o código (PKCE S256) por um *Personal Access Token* do Shield com escopo `mcp` |

- O usuário autoriza com o próprio login do sistema; o token emitido pertence a ele e pode ser revogado apagando-o em `auth_identities` (tipo `access_token`).
- Códigos de autorização duram 5 minutos, são de uso único e só o hash é guardado. Tentativas de senha são limitadas (10/min por IP).
- PKCE é obrigatório para clientes públicos; `redirect_uri` exige https (exceto localhost).
- **Clientes que pedem Client ID/Secret prontos** (ex.: Gemini): `php spark mcp:cliente "Gemini" https://<redirect-uri-do-conector>` imprime o par. Guarde o secret: só o hash fica no banco.

## Ferramentas

| Ferramenta | Descrição |
|---|---|
| `incluir_cliente` | Cadastra cliente (tutor). Obrigatório: `nome`. Valida CPF/CNPJ e recusa CPF duplicado. |
| `incluir_paciente` | Cadastra paciente (animal) para um `cliente_id`. Obrigatórios: `cliente_id`, `nome`. |
| `agendar` | Cria agendamento. Obrigatórios: `paciente_id`, `data` (AAAA-MM-DD), `hora` (HH:MM), `servico_ids`. Opcionais: `duracao_minutos` (30), `status` (`pendente`/`confirmado`), `observacoes`. |
| `buscar_clientes` | Busca clientes ativos por nome/CPF/telefone (máx. 20). |
| `buscar_pacientes` | Busca pacientes por nome ou tutor (máx. 20). |
| `listar_servicos` | Lista serviços ativos (`ser_id`, nome, valor) para usar em `agendar`. |

Erros de validação voltam como resultado com `isError: true` e o detalhe por campo, para o assistente corrigir e tentar de novo.

## Conectar no Claude

Em **Configurações → Conectores → Adicionar conector personalizado** (claude.ai), informe só a URL `https://SEU-DOMINIO/mcp` e clique em conectar. O Claude se registra sozinho, abre a tela de login do sistema e, após **Permitir acesso**, passa a usar as ferramentas.

No Claude Code, com OAuth (`/mcp` → autenticar) ou com o token estático:

```bash
claude mcp add --transport http clinicas https://SEU-DOMINIO/mcp \
  --header "Authorization: Bearer <mcp.apiToken>"
```

## Conectar no Gemini

No Gemini CLI, em `~/.gemini/settings.json`, com OAuth (o CLI faz a descoberta e abre o navegador):

```json
{
  "mcpServers": {
    "clinicas": { "httpUrl": "https://SEU-DOMINIO/mcp", "oauth": { "enabled": true } }
  }
}
```

ou com o token estático: `"headers": { "Authorization": "Bearer <mcp.apiToken>" }` no lugar de `oauth`.

## Testando com curl

```bash
curl -s https://SEU-DOMINIO/mcp \
  -H "Authorization: Bearer <token>" -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
```

## Código

`backend/app/Libraries/Mcp/` (protocolo em `McpServer`, ferramentas em `ClinicaTools`), `App\Controllers\OAuth` (servidor de autorização), `App\Filters\McpAuth` (token) e `App\Controllers\V1\Mcp`. Testes em `backend/tests/unit/Libraries/Mcp` e `tests/unit/Controllers/McpRotasTest.php`.
