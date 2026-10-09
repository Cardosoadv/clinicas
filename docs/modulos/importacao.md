---
title: Importação CSV
parent: Módulos
nav_order: 14
---

# Importação CSV
{: .no_toc }

Endpoints para migrar dados de outro sistema — o formato segue a exportação do **Sispet**. A ordem importa: **clientes → pets → receitas/despesas**.
{: .fs-5 .fw-300 }

<details open markdown="block">
  <summary>Nesta página</summary>
  {: .text-delta }
1. TOC
{:toc}
</details>

---

## Regras gerais

- Envio por `POST` em `multipart/form-data`, com o arquivo no campo **`csv_file`**, estando logado.
- A primeira linha é o **cabeçalho**. As colunas são identificadas pelo **nome**, sem diferenciar maiúsculas/minúsculas; a ordem não importa e colunas desconhecidas são ignoradas.
- Datas em `dd/mm/aaaa`; valores em `1.234,56` ou `1234.56`.
- A resposta traz um resumo:

```json
{
  "status": "success",
  "message": "Processamento concluído.",
  "total_processado": 120,
  "importados": 115,
  "ignorados": [ { "nome": "Maria", "motivo": "CPF já cadastrado (123.456.789-00)" } ],
  "erros": [ "Linha 7: Nome não informado." ]
}
```

{: .nota }
Ainda **não há tela** de importação no frontend. Use uma ferramenta de API (Insomnia, Postman) com o cookie da sessão, ou `curl` — veja o exemplo no fim da página.

## 1. Clientes — `POST /api/v1/clientes/import`

Delimitador detectado automaticamente: `;`, `,` ou tabulação.

| Coluna do CSV | Campo |
|:--|:--|
| Nome | `nome` (obrigatório) |
| Razão | `razao_social` |
| Apelido | `apelido` |
| Obs. cliente | `observacoes` |
| RG / CPF / CNPJ | `rg` / `cpf` / `cnpj` |
| Nascimento | `nascimento` |
| Telefones / Emails | `telefones` / `emails` |
| Rua / Número / Complemento / Bairro / Cep / Cidade / Estado | endereço |
| Vendedor / Empresa / Origem cliente / Última compra | dados comerciais |
| **ID Cliente** | `exportacao` — **guarde esta coluna**: é por ela que os pets acham o tutor |

Ignorado: linhas cujo **CPF já está cadastrado**. Erro: linha sem nome.

## 2. Pets — `POST /api/v1/pacientes/import`

Delimitador `;`.

| Coluna do CSV | Campo |
|:--|:--|
| **ID Cliente** | tutor — procurado em `clientes.exportacao` (obrigatório) |
| Nome Pet | `paciente_nome` |
| Espécie / Raça / Gênero / Porte / Pelagem | dados do pet |
| Nascimento | `paciente_nascimento` |
| Características | `paciente_caracteristicas` |
| Ativo | `paciente_status` — "ativo" vira `Ativo`; qualquer outro valor, `Inativo` |

Ignorado: tutor não encontrado pelo ID de exportação; pet com o **mesmo nome para o mesmo tutor**. Erro: linha sem ID Cliente.

## 3. Receitas — `POST /api/v1/receitas/import`

Delimitador `;`. Cria **cobranças**.

| Coluna do CSV | Campo |
|:--|:--|
| Cliente | nome do tutor (obrigatório) |
| Pet | nome do pet (obrigatório) |
| Situação | `status` — "pago" vira `Pago`; o resto, `Pendente` |
| Planos de contas III | `servico` ("Receita c/ venda de serviços" vira "Banho/Tosa") |
| Valor documento / Desconto | `valor` / `desconto` |
| Vencimento | `vencimento` |
| Pagamento | `data_servico` (padrão: hoje) |
| Forma | `forma_pagamento` ("pix/ transf./ depós." vira "pix") |
| Detalhes | `observacoes` |

- O cliente é procurado pelo **nome**, e o pet pelo nome **dentro daquele cliente**.
- Ignorado: cliente ou pet não encontrado; provável duplicado (mesmo pet, data, valor e serviço).
- Para cada receita **paga**, também é criado um **Recibo** em Notas, para entrar no financeiro.

## 4. Despesas — `POST /api/v1/faturamento/despesas/import`

Delimitador `;`. Cabeçalho do Sispet:

```text
Situação;Conta;Tipo documento;Vencimento;Competência;Pagamento;Contato;Razão;CPF;CNPJ;Fones;Emails;
Valor documento;Acréscimo;Desconto;Valor pago;Detalhes;Origem;Incluído por;Unidade;
Planos de contas I;Planos de contas II;Planos de contas III;Tipo de despesa
```

| Coluna do CSV | Campo |
|:--|:--|
| Situação | `status` — "pago" vira `Pago`; o resto, `Pendente` |
| Tipo documento | `forma_pagamento` |
| Vencimento / Pagamento | data da despesa (para as pagas, usa a data de pagamento quando existir) |
| Razão | `fornecedor` |
| Valor documento | `valor` |
| Detalhes | `descricao` (sem detalhes: "Importado do Sispet — {categoria}") |
| Planos de contas III → II → Tipo de despesa | `categoria` (o primeiro preenchido; padrão "Geral") |

Ignorado: **provável duplicado** (mesmo fornecedor, valor e data), tanto contra o banco quanto dentro do próprio arquivo. A checagem roda em lotes para aguentar arquivos grandes.

## Exemplo com `curl`

```bash
API=http://localhost:8080/api/v1

# 1. login (guarda o cookie da sessão)
curl -c cookies.txt -H 'Content-Type: application/json' \
     -d '{"email":"admin@suaclinica.com.br","password":"sua-senha"}' \
     $API/auth/login

# 2. importações, na ordem
curl -b cookies.txt -F csv_file=@clientes.csv  $API/clientes/import
curl -b cookies.txt -F csv_file=@pets.csv      $API/pacientes/import
curl -b cookies.txt -F csv_file=@receitas.csv  $API/receitas/import
curl -b cookies.txt -F csv_file=@despesas.csv  $API/faturamento/despesas/import
```
