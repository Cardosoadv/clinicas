---
title: Clientes (CRM)
parent: Módulos
nav_order: 4
---

# Clientes (CRM)
{: .no_toc }

Cadastro dos tutores — pessoa física ou jurídica — com notas internas, histórico de comunicação, envio de WhatsApp e mesclagem de cadastros duplicados.
{: .fs-5 .fw-300 }

<details open markdown="block">
  <summary>Nesta página</summary>
  {: .text-delta }
1. TOC
{:toc}
</details>

---

## Lista de clientes (`/clientes`)

- **Filtros**: situação (`ativos`, `inativos`, `todos`), tipo de pessoa (`fisica` — sem CNPJ; `juridica` — com CNPJ) e busca livre.
- **Painel lateral** (`GET clientes/painel`): total, ativos, pessoas físicas e jurídicas; últimos 5 clientes adicionados; aniversariantes do mês.
- **Ações por linha**: ver detalhes, editar e excluir.

{: .nota }
Clientes usam *soft delete*: **excluir** um cliente o torna **inativo** (`deleted_at` preenchido). Ele continua aparecendo nos filtros "inativos" e "todos".

## Campos do cadastro

| Grupo | Campos |
|:--|:--|
| Identificação | Nome *, razão social, apelido, RG, CPF, CNPJ, data de nascimento |
| Contato | Telefones, e-mails |
| Endereço | Rua, número, complemento, bairro, CEP, cidade, estado |
| Comercial | Vendedor, empresa, origem do cliente, última compra |
| Outros | Observações; `exportacao` (ID do cliente no sistema de origem, usado na [importação](importacao)) |

Validação no servidor: nome com 3 a 255 caracteres; **CPF** e **CNPJ** opcionais, mas, se informados, precisam ter dígitos verificadores válidos (`valid_cpf`, `valid_cnpj`). O frontend formata e valida os documentos enquanto você digita.

## Detalhe do cliente (`/clientes/:id`)
{: #detalhe-do-cliente}

| Aba / bloco | O que faz |
|:--|:--|
| **Dados** | Documento, telefones, e-mails, razão social e observações gerais; botão Editar |
| **Pacientes** | Pets ligados ao tutor (`GET clientes/{id}/pacientes`) |
| **Notas** | Notas internas com autor e data. Adicionar e excluir. |
| **Comunicações** | Histórico de mensagens enviadas (automáticas e manuais) |
| **Enviar WhatsApp** | Escreva a mensagem. O sistema registra no histórico (canal `whatsapp`, tipo `manual`) e abre `wa.me` com o texto. Telefones com 10 ou 11 dígitos recebem o prefixo `55`. |

## Clientes duplicados (`/clientes/duplicados`)

Agrupa clientes ativos com **mesmo nome e mesmo telefone**. Para cada grupo a tela mostra qual registro será **mantido** e quais serão **removidos**.

Ao mesclar (`POST clientes/merge` com a lista de IDs):

1. o registro de **menor ID** é o principal;
2. campos vazios do principal são preenchidos com os dados dos duplicados;
3. pacientes, notas (`cliente_notas`) e comunicações dos duplicados passam para o principal;
4. os duplicados são excluídos (*soft delete*);
5. tudo numa transação.

## API

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/clientes?status=&tipo=&search=` | Lista filtrada |
| GET | `/clientes/painel` | Estatísticas, recentes e aniversariantes |
| GET | `/clientes/duplicados` | Grupos de duplicados |
| POST | `/clientes/merge` | Mescla (`ids: number[]`) |
| POST | `/clientes/import` | [Importação CSV](importacao) (`csv_file`) |
| GET | `/clientes/{id}` | Detalhe |
| POST | `/clientes` | Cria |
| PUT | `/clientes/{id}` | Edita |
| DELETE | `/clientes/{id}` | Inativa (*soft delete*) |
| GET | `/clientes/{id}/pacientes` | Pets do cliente |
| GET / POST | `/clientes/{id}/notas` | Lista / cria nota (`texto`, `autor`) |
| DELETE | `/clientes/{id}/notas/{notaId}` | Exclui nota |
| GET / POST | `/clientes/{id}/comunicacoes` | Histórico / envio manual (`mensagem`) — devolve `link` do WhatsApp |
