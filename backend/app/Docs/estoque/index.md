---
titulo: Estoque
resumo: Produtos e insumos, entradas e saídas, estoque mínimo e baixas automáticas.
ordem: 70
telas: [estoque]
palavras_chave: [produto, insumo, entrada, saída, estoque mínimo, compra]
---

A tela [Estoque](/estoque) lista os produtos e insumos da clínica.

## Produto

| Campo | Observação |
|---|---|
| **Nome** * | nome do produto |
| **Descrição** | texto livre |
| **Unidade de medida** | unidade, caixa, frasco, ml... |
| **Quantidade atual** | atualizada pelas movimentações |
| **Estoque mínimo** | abaixo ou igual a ele, o produto entra nos **alertas** |
| **Valor de venda** | preço de referência; sugerido ao lançar o produto como material de um atendimento |

## Entrada

Informe o produto, a quantidade, o valor e uma observação. Marque **Lançar no financeiro** para criar também
a despesa "Compra de Estoque" no [Faturamento](../faturamento/despesas.md), com fornecedor, forma de
pagamento e vencimento.

## Saída

Informe o produto, a quantidade e uma observação. A saída é recusada se não houver quantidade suficiente.

## Saídas automáticas

- **Ficha técnica**: quando uma cobrança de um serviço fica paga, os insumos da [ficha técnica](../servicos/index.md#ficha-tecnica) saem do estoque.
- **Materiais do atendimento**: os materiais lançados ao [faturar um atendimento](../agenda/faturar.md#materiais-utilizados) saem do estoque.
