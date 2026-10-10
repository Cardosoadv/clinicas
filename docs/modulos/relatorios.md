---
title: Relatórios
parent: Módulos
nav_order: 12
---

# Relatórios financeiros
{: .no_toc }

Extrato, DRE e Livro Caixa de um período, montados a partir das cobranças e despesas do [Faturamento](faturamento).
{: .fs-5 .fw-300 }

<details open markdown="block">
  <summary>Nesta página</summary>
  {: .text-delta }
1. TOC
{:toc}
</details>

---

## Período

Os três relatórios usam os filtros `inicio` e `fim` (`AAAA-MM-DD`). Sem filtro, o período é o **mês corrente**. Se o início vier depois do fim, as datas são trocadas.

| Lançamento | Data considerada |
|:--|:--|
| Cobrança (entrada) | `data_servico` |
| Despesa (saída) | `data_vencimento` |

## Extrato (`/relatorios/extrato`)

Lista **todos** os lançamentos do período, de qualquer status, em ordem de data.

| Coluna | Conteúdo |
|:--|:--|
| Data | Data do lançamento |
| Histórico | `Paciente — Serviço` (entradas) ou `Fornecedor — Descrição` (saídas) |
| Natureza | Entrada / Saída |
| Categoria | Serviço ou categoria da despesa |
| Forma | Forma de pagamento |
| Status | Pendente, Pago, Atrasado, Cancelado |
| Valor | — |

Na tela o filtro é o período; pela API também dá para filtrar por `tipo` (`todos`, `entrada`, `saida`) e `status`. Totais: entradas, saídas e saldo do período.

## DRE (`/relatorios/dre`)

Demonstração do Resultado em **regime de caixa** — só entram lançamentos **Pagos**.

```text
  Receita Bruta              (cobranças pagas, por serviço)
(-) Descontos
= Receita Líquida
(-) Despesas                 (despesas pagas, por categoria)
= Resultado Líquido
  Margem líquida (%)         = Resultado Líquido ÷ Receita Bruta × 100
```

Também mostra a **variação** de receita e despesa (%) em relação ao **mês anterior** ao início do período.

## Livro Caixa (`/relatorios/livro-caixa`)

Movimentos **pagos** do período com **saldo acumulado** linha a linha:

| Campo | Cálculo |
|:--|:--|
| Saldo inicial | Entradas pagas − saídas pagas **antes** do início do período |
| Movimentos | Entradas e saídas pagas, em ordem de data, com saldo acumulado |
| Saldo final | Saldo inicial + entradas − saídas |

## Impressão

As telas de relatório ainda não têm botão nem layout próprio de impressão (o cabeçalho/rodapé de [Configurações](comunicacao#cabecalho-e-rodape) hoje só é aplicado à receita). Para guardar uma cópia, use a impressão do navegador (Ctrl+P → Salvar como PDF).

## API

| Método | Rota | Parâmetros |
|:--|:--|:--|
| GET | `/relatorios/extrato` | `inicio`, `fim`, `tipo`, `status` |
| GET | `/relatorios/dre` | `inicio`, `fim` |
| GET | `/relatorios/livro-caixa` | `inicio`, `fim` |
