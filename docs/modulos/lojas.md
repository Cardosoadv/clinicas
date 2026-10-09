---
title: Lojas
parent: Módulos
nav_order: 10
---

# Lojas
{: .no_toc }

Unidades e filiais da clínica. A **loja principal** define a identidade visual do sistema.
{: .fs-5 .fw-300 }

## Campos

| Campo | Regra |
|:--|:--|
| Nome * | 3 a 255 caracteres |
| CNPJ | 14 a 20 caracteres |
| E-mail | E-mail válido |
| Telefone | 8 a 20 caracteres |
| Endereço, cidade, CEP | Texto livre |
| Estado | Sigla de 2 letras (UF) |
| Logo | PNG, JPG ou WebP — guardada em `writable/imagens/loja/` |
| Situação * | Ativo / Inativo |

## Loja principal

A loja principal é a **loja ativa mais antiga** (menor ID com status Ativo). Os dados dela são usados em:

- topo do sistema (nome e logo);
- **cabeçalho e rodapé das impressões** (receitas, relatórios) — variáveis `{logo}`, `{clinica}`, `{cnpj}`, `{endereco}`, `{telefone}`, `{email}`, `{cidade}`, `{estado}`, `{cep}`;
- variável `{clinica}` das mensagens de WhatsApp.

Para trocar a loja principal, inative a atual ou cadastre as lojas na ordem desejada.

{: .nota }
A edição usa `POST /lojas/{id}` (e não `PUT`) porque o formulário envia a logo em `multipart/form-data`, e o PHP só preenche `$_FILES` em requisições POST.

## API

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/lojas` | Lista |
| GET | `/lojas/principal` | Loja principal |
| GET | `/lojas/{id}` | Detalhe |
| GET | `/lojas/{id}/logo/{arquivo}` | Logo |
| POST | `/lojas` | Cria (multipart; arquivo em `logo_file`) |
| POST | `/lojas/{id}` | Edita (multipart; arquivo em `logo_file`) |
| DELETE | `/lojas/{id}` | Exclui (*soft delete*) |
