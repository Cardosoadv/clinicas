---
title: Faturamento
parent: Módulos
nav_order: 11
---

# Faturamento
{: .no_toc }

Contas a receber (cobranças), contas a pagar (despesas), recibos e notas fiscais, com um painel financeiro do mês.
{: .fs-5 .fw-300 }

<details open markdown="block">
  <summary>Nesta página</summary>
  {: .text-delta }
1. TOC
{:toc}
</details>

---

O módulo fica em `/faturamento` e tem quatro abas: **Dashboard**, **Cobranças**, **Despesas** e **Notas**.

## Dashboard financeiro

| Indicador | Cálculo |
|:--|:--|
| Receitas do mês | Cobranças **pagas** com data de serviço no mês |
| Despesas do mês | Despesas **pagas** do mês |
| Saldo do mês | Receitas − despesas |
| A receber | Cobranças **Pendentes** |
| Vencidos | Cobranças **Atrasadas** |

Também mostra:

- gráfico **Receita vs Despesa** dos últimos 7 meses;
- receita por serviço e por **forma de pagamento** no mês;
- **Top Pacientes** — os que mais faturaram no mês;
- **Últimas transações** (cobranças e despesas misturadas, mais recentes primeiro);
- últimas notas emitidas e a lista de recebíveis.

## Cobranças (contas a receber)

| Campo | Descrição |
|:--|:--|
| Paciente * | Pet atendido |
| Serviço | Um serviço do catálogo **ou** uma descrição livre |
| Valor, desconto | Em reais |
| Data do serviço | Base do regime de caixa nos relatórios |
| Forma de pagamento | Pix, Dinheiro, Cartão de Crédito, Cartão de Débito, Boleto ou **Pacote** |
| Parcelas, vencimento | Condições de pagamento |
| Status | Pendente, Pago, Atrasado, Cancelado |
| Observações | Texto livre |

Regras:

- **Forma "Pacote"**: exige um pacote; consome o pacote na criação e a cobrança já nasce **Paga**. Com outra forma de pagamento, o `pacote_id` é descartado. Veja [Pacotes](pacotes).
- **Baixa de estoque**: quando a cobrança é criada como Paga, ou muda **de outro status para Pago**, o sistema baixa os insumos da ficha técnica do serviço (só uma vez). Veja [Serviços](servicos#ficha-tecnica-bom).
- **Edição**: só os campos serviço, valor, desconto, data do serviço, forma de pagamento, parcelas, vencimento, status e observações podem ser alterados.
- Cobranças também nascem automaticamente ao **faturar um agendamento** ([Agenda](agenda#faturar-um-agendamento)) e ao **vender um pacote**.
- Pelo backend é possível gerar a mensagem de WhatsApp de **cobrança pendente** — veja [Comunicação](comunicacao).

## Despesas (contas a pagar)

| Campo | Descrição |
|:--|:--|
| Descrição * | Texto |
| Categoria | 🏢 Infraestrutura, 💊 Insumos Pet, 💡 Utilidades, 🔧 Equipamentos, 👥 Pessoal, 📞 Comunicação, 💻 Sistema operacional, 📋 Impostos e taxas, 🩺 Saúde/Veterinário, 📦 Estoque, 🚗 Transporte, 🏠 Aluguel, ⚖️ Jurídico, 📝 Outra |
| Fornecedor | Texto |
| Valor, vencimento | — |
| Forma de pagamento | Pix, Boleto, Dinheiro, Cartão Empresarial, Transferência, Débito Automático |
| Status | Pendente, Pago, Cancelado |
| Comprovante | PDF, PNG, JPG ou WebP — guardado em `writable/despesas/{id}/` |

### Parceladas e recorrentes (API)

O backend aceita dois modos extras em `POST faturamento/despesas`. Ainda **não há campos para eles na tela**.

| Modo | Como enviar | Resultado |
|:--|:--|:--|
| **Parcelada** | `is_parcelado: "true"` e `parcelas: [{valor, vencimento}, ...]` (array ou JSON) | Uma despesa por parcela, com o mesmo `des_grupo_id` e `des_parcela_num` / `des_parcela_total` |
| **Recorrente (fixa)** | `is_recorrente: "true"`, `recorrencia` (`semanal`, `quinzenal`, `mensal`, `anual`) e `recorrencia_fim` opcional | Uma despesa por período até a data final (padrão: 1 ano; máximo de 60 ocorrências). A primeira mantém o status enviado; as futuras nascem Pendentes. |

Despesas também são criadas pela **entrada de estoque** com "lançar no financeiro" ([Estoque](estoque)) e pela [importação CSV](importacao).

## Notas e recibos

Na aba **Notas** → **Nova Nota**:

| Campo | Descrição |
|:--|:--|
| Tipo | `Recibo` ou `NFS-e` |
| Cobrança | Cobrança de origem (opcional) |
| Paciente | Pet |
| CPF do responsável | Para o recibo |
| Data de emissão, descrição ("Referente a"), valor | — |

Cada nota recebe um **hash aleatório**. O botão de imprimir abre o **recibo público** em `/recibo/{id}/{hash}`, que **não exige login** e pode ser enviado ao tutor. A página mostra os dados da loja principal, tutor, paciente, valor, descrição e um **QR Code**.

{: .nota }
O registro de "NFS-e" é só **interno**: o sistema não transmite nota fiscal para a prefeitura.

{: .atencao }
O QR Code aponta para o endpoint JSON da API (`/api/v1/publico/recibos/{id}/{hash}`), não para a página `/recibo/{id}/{hash}` do frontend. Quem escaneia vê os dados em JSON, não o recibo formatado.

## API

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/faturamento/dashboard` | Painel financeiro |
| GET | `/faturamento/lancamentos` | Cobranças e despesas para as abas |
| POST | `/faturamento/cobrancas` | Cria cobrança |
| PUT | `/faturamento/cobrancas/{id}` | Edita cobrança |
| POST | `/faturamento/despesas` | Cria despesa (simples, parcelada ou recorrente) |
| PUT | `/faturamento/despesas/{id}` | Edita despesa |
| POST | `/faturamento/despesas/{id}/comprovante` | Envia comprovante (multipart, campo `comprovante`) |
| GET | `/faturamento/despesas/{id}/comprovante/{arquivo}` | Baixa comprovante |
| POST | `/faturamento/despesas/import` | [Importação CSV](importacao) de despesas |
| GET | `/faturamento/notas` | Lista notas/recibos |
| POST | `/faturamento/notas` | Emite nota/recibo |
| POST | `/receitas/import` | [Importação CSV](importacao) de receitas |
| GET | `/publico/recibos/{id}/{hash}` | Recibo público (**sem login**) |
