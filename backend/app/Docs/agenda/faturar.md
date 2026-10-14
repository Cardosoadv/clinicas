---
titulo: Faturar um atendimento
resumo: Gerar a cobrança do agendamento, lançar os materiais usados e pagar com pacote.
ordem: 30
palavras_chave: [faturar, cobrança, materiais, pagamento, pacote, concluir]
---

O botão **Faturar**, no cartão do agendamento, abre a cobrança do atendimento.

## Dados da cobrança

| Campo | Observação |
|---|---|
| **Valor dos serviços** | soma dos serviços do agendamento |
| **Desconto** | fica registrado na cobrança |
| **Forma de pagamento** | Pix, Dinheiro, Cartão de Crédito, Cartão de Débito, Boleto ou **Pacote** |
| **Parcelas** e **Vencimento** | condições de pagamento |
| **Status** | Pendente, Pago ou Cancelado |

## Materiais utilizados

Em **+ Adicionar material**, lance os produtos do [Estoque](../estoque/index.md) gastos no atendimento
(medicamentos, seringas, luvas...). Para cada um, informe o produto, a quantidade e o valor unitário, que já
vem com o valor de venda e pode ser alterado ou zerado.

O total da cobrança é **Serviços + Materiais**.

- Os materiais saem do estoque ao faturar, qualquer que seja o status da cobrança.
- Se faltar estoque de algum material, o faturamento é recusado e o produto em falta é informado.
- Ao editar o faturamento, só a diferença é movimentada: reduzir ou remover um material devolve a quantidade ao estoque.

## Pagar com pacote

Com a forma **Pacote**, escolha um dos pacotes **ativos** do paciente. Num pacote de serviços, o atendimento
consome uma sessão; num pacote de crédito, o valor é descontado do saldo. Veja [Pacotes](../pacotes/index.md).

## Depois de faturar

- A cobrança aparece no [Faturamento](../faturamento/cobrancas.md), ligada ao agendamento e ao paciente.
- O agendamento fica **Concluído**.
- Faturar de novo o mesmo agendamento **edita** a cobrança existente, sem criar outra.

> [!DICA]
> Se a cobrança nasce **Paga**, os insumos da ficha técnica do serviço saem do estoque automaticamente.
> Veja [Serviços](../servicos/index.md#ficha-tecnica).
