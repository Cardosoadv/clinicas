---
titulo: Despesas
resumo: Contas a pagar, com parcelamento, categoria, fornecedor e comprovante.
ordem: 20
telas: [faturamento-despesas]
palavras_chave: [despesa, pagar, fornecedor, comprovante, conta, parcelar, parcela, parcelada, parcelamento, cancelar]
---

Na aba **Despesas** ficam as contas a pagar. A lista mostra as despesas **por mês de vencimento**: use as
setas ao lado do mês para navegar, **Todos os meses** para ver tudo e o filtro de status para separar
pendentes, pagas e canceladas. Há paginação no fim da lista.

## Lançar uma despesa

Clique em **Nova Despesa**.

| Campo | Observação |
|---|---|
| **Descrição** * | texto |
| **Categoria** | Infraestrutura, Insumos Pet, Utilidades, Equipamentos, Pessoal, Aluguel, Impostos e taxas, Estoque... |
| **Fornecedor** | texto |
| **Valor** e **vencimento** | — |
| **Forma de pagamento** | Pix, Boleto, Dinheiro, Cartão Empresarial, Transferência ou Débito Automático |
| **Status** | Pendente, Pago ou Cancelado |
| **Comprovante** | PDF, PNG, JPG ou WebP |

Na lista, o status **Atrasado** aparece em despesas pendentes cujo vencimento já passou. Ele não é um status
que você escolhe: some quando a despesa é marcada como paga.

Para registrar o pagamento sem abrir a despesa, use o botão **Marcar como paga** (✓) no cartão.

## Parcelar uma despesa

Use quando a compra será paga em várias vezes, como um equipamento em 6x.

1. Em **Nova Despesa**, marque **Parcelar esta despesa**.
2. Informe o **Valor total** (não o valor da parcela) e o **Vencimento da 1ª parcela**.
3. Escolha o **Nº de parcelas** (de 2 a 60) e a **Periodicidade**: mensal, quinzenal ou semanal.
4. Confira a prévia. Se precisar, ajuste o vencimento ou o valor de qualquer parcela.
5. Clique em **Salvar**.

Cada parcela vira uma despesa própria, com vencimento e status independentes, identificada na lista como
**Parcela 2/6**, por exemplo.

- O valor total é dividido em centavos e a **última parcela fica com a diferença**: R$ 100,00 em 3x resulta em
  33,33, 33,33 e 33,34.
- Na periodicidade mensal o dia se mantém e, em meses mais curtos, vale o último dia do mês: uma compra com
  vencimento em 31/01 gera 31/01, 28/02, 31/03 e 30/04.
- Se você alterar o valor total, a quantidade, a periodicidade ou o primeiro vencimento, a prévia é recalculada
  e os ajustes manuais são descartados.
- O botão **Salvar** só libera quando a soma das parcelas é igual ao valor total. A tela mostra quanto falta ou
  sobra.
- O **Status** informado vale para a **1ª parcela**; as demais nascem **Pendentes**.
- O **comprovante** anexado na criação fica na **1ª parcela**. Para as outras, anexe ao editar cada uma.

> [!NOTA]
> Só dá para parcelar ao **criar** a despesa. Uma despesa já lançada não pode ser transformada em parcelada.

## Acompanhar as parcelas

Clique no botão **Ver todas as parcelas** (lista numerada) para abrir o resumo do parcelamento: número,
vencimento, valor e status de cada parcela, e o total das que não foram canceladas.

## Editar uma parcela

Clique na despesa para abri-la. Em uma parcela aparece o campo **Aplicar a**, que mostra a quantas parcelas a
alteração vai:

| Opção | Atinge |
|---|---|
| **Apenas esta parcela** | só a parcela aberta |
| **Esta e as seguintes** | a parcela aberta e as de número maior |
| **Todas as parcelas** | todo o parcelamento |

O que muda em cada caso:

- **Descrição, categoria e fornecedor** seguem a opção escolhida, inclusive nas parcelas já pagas.
- **Forma de pagamento** segue a opção escolhida, mas **não muda em parcelas já pagas**.
- **Valor, vencimento e status** valem **somente para a parcela aberta**. Os vencimentos das seguintes não
  são deslocados.

Só os campos que você alterou são aplicados às demais parcelas.

## Cancelar

Use o botão **Cancelar despesa** (🚫) no cartão. Em uma parcela, escolha **Apenas esta**, **Esta e as
seguintes** ou **Todas** e confira, antes de confirmar, quantas parcelas serão canceladas.

> [!ATENCAO]
> Só despesas **Pendentes** são canceladas. Parcelas **Pagas** nunca são alteradas pelo cancelamento: o sistema
> avisa quantas foram preservadas. Se não houver nenhuma pendente no escopo escolhido, nada é cancelado.

## Onde as parcelas aparecem

- No **Dashboard**, o indicador **A pagar** soma as despesas pendentes, **Despesas vencidas** soma as pendentes
  com vencimento passado e o cartão **A pagar nos próximos meses** separa o valor por mês. Em **Últimas
  transações**, o parcelamento aparece uma vez só.
- Nos [relatórios](../relatorios/index.md), cada parcela conta **uma vez**, no mês do **seu vencimento**, e só
  depois de marcada como **Paga**. Parcelas canceladas não entram nos totais. Detalhes na [DRE](../relatorios/dre.md).

> [!NOTA]
> A entrada de produtos no [Estoque](../estoque/index.md) com **Lançar no financeiro** cria a despesa
> "Compra de Estoque" automaticamente.
