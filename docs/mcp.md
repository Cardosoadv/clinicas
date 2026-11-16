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

1. Gere um token e defina no `.env` do backend (mín. 32 caracteres):

   ```bash
   php -r "echo bin2hex(random_bytes(32));"
   ```

   ```ini
   mcp.apiToken = <token gerado>
   ```

   Sem o token o endpoint responde **503** (desabilitado) — nunca fica aberto.

2. Publique o backend em **HTTPS** com URL pública (Claude e Gemini acessam a partir da nuvem deles).

> O token dá acesso total às ferramentas abaixo, sem login de usuário. Trate-o como senha, rotacione-o se vazar e restrinja o acesso por rede/WAF quando possível.

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

Em **Configurações → Conectores → Adicionar conector personalizado** (claude.ai), informe `https://SEU-DOMINIO/mcp` e, nas opções avançadas, o cabeçalho `Authorization: Bearer <token>`. Se a sua conta/plano exigir OAuth para conectores remotos, use o Claude Code ou a API de MCP connectors, que aceitam token Bearer:

```bash
claude mcp add --transport http clinicas https://SEU-DOMINIO/mcp \
  --header "Authorization: Bearer <token>"
```

## Conectar no Gemini

No Gemini CLI, em `~/.gemini/settings.json`:

```json
{
  "mcpServers": {
    "clinicas": {
      "httpUrl": "https://SEU-DOMINIO/mcp",
      "headers": { "Authorization": "Bearer <token>" }
    }
  }
}
```

Os SDKs do Gemini (Google Gen AI) também aceitam sessões MCP remotas com o mesmo cabeçalho.

## Testando com curl

```bash
curl -s https://SEU-DOMINIO/mcp \
  -H "Authorization: Bearer <token>" -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
```

## Código

`backend/app/Libraries/Mcp/` (protocolo em `McpServer`, ferramentas em `ClinicaTools`), `App\Filters\McpAuth` (token) e `App\Controllers\V1\Mcp`. Testes em `backend/tests/unit/Libraries/Mcp` e `tests/unit/Controllers/McpRotasTest.php`.
