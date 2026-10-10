---
title: Estoque
parent: Módulos
nav_order: 8
---

# Estoque
{: .no_toc }

Produtos e insumos da clínica, entradas e saídas, alerta de estoque mínimo e baixa automática pelos serviços.
{: .fs-5 .fw-300 }

## Produtos (`/estoque`)

| Campo | Descrição |
|:--|:--|
| Nome * | Pelo menos 3 caracteres |
| Descrição | Texto livre |
| Unidade de medida | Unidade, caixa, frasco, ml... |
| Quantidade atual | Atualizada pelas movimentações |
| Estoque mínimo | Abaixo ou igual a ele, o produto aparece nos **alertas** (tela de estoque e [Dashboard](dashboard)) |
| Valor de venda | Preço de referência |

## Movimentações

### Entrada

Informe produto, quantidade, valor unitário/total e observação. Marcando **Lançar no financeiro**, o sistema também cria uma **despesa** no [Faturamento](faturamento):

| Campo da despesa | Valor |
|:--|:--|
| Descrição | `Compra de Estoque: {produto}` |
| Categoria | `Estoque` |
| Fornecedor, forma de pagamento, vencimento | Informados no formulário |
| Status | Informado (padrão **Pago**) |

A movimentação guarda o `despesa_id`. Tudo acontece numa transação: se a despesa falhar, a entrada também é desfeita.

### Saída

Informe produto, quantidade e observação. A saída é **recusada** se a quantidade em estoque for menor que a pedida.

### Saída automática (BOM)

Quando um serviço com ficha técnica é faturado como **Pago**, cada produto vinculado sai automaticamente do estoque. Veja [Serviços → Ficha técnica](servicos#ficha-tecnica-bom).

Tipos de movimentação gravados em `estoque_movimentacoes`: `Entrada`, `Saída` e `Ajuste`.

## API

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/estoque/produtos` | Lista |
| GET | `/estoque/alertas` | Produtos no estoque mínimo ou abaixo |
| GET | `/estoque/produtos/{id}` | Detalhe |
| POST | `/estoque/produtos` | Cria |
| PUT | `/estoque/produtos/{id}` | Edita |
| DELETE | `/estoque/produtos/{id}` | Exclui (*soft delete*) |
| POST | `/estoque/movimentacoes/entrada` | Entrada (`produto_id`, `quantidade`, `valor_unitario`, `valor_total`, `lancar_financeiro`, `fornecedor`, `forma_pagamento`, `data_vencimento`, `status_pagamento`) |
| POST | `/estoque/movimentacoes/saida` | Saída (`produto_id`, `quantidade`, `observacao`) |
