---
titulo: Pacotes
resumo: Vender pacotes de serviços ou de crédito, pré-agendar as sessões e acompanhar o uso.
ordem: 60
telas: [pacotes]
palavras_chave: [pacote, sessões, crédito, pré-pago, pré-agendar]
---

Um pacote é vendido ao tutor para um paciente. Há dois tipos:

| Tipo | Como funciona |
|---|---|
| **Serviços** | tem itens (serviço + quantidade). Cada atendimento consome uma sessão do item |
| **Crédito** | tem um saldo em reais. Cada atendimento desconta o valor dele |

## Situação do pacote

| Situação | Significado |
|---|---|
| **Pendente** | recém-criado, aguardando o pagamento. Só nesta situação os itens podem ser alterados |
| **Ativo** | pode ser usado no faturamento |
| **Esgotado** | todas as sessões usadas, ou saldo zerado |
| **Cancelado** | cancelado manualmente |

## Criar um pacote

Em [Pacotes](/pacotes), clique em **Novo Pacote** e informe o paciente, o nome, o tipo, a validade, os itens e
o bloco **Pagamento e cobrança**. Ao salvar, o pacote nasce **Pendente** e uma cobrança "Compra de Pacote"
é lançada no [Faturamento](../faturamento/cobrancas.md).

> [!ATENCAO]
> Depois de receber o pagamento, abra **Editar Pacote** e mude a situação para **Ativo**. Só pacotes
> ativos aparecem na hora de faturar.

## Pré-agendar as sessões

Num pacote de serviços, marque **Pré-agendar as sessões na Agenda** e informe a data inicial, a
periodicidade (semanal ou mensal), o horário, a duração e o veterinário. O sistema cria um agendamento
**pendente** por sessão. Com vários itens, cada um fica no horário seguinte ao anterior, no mesmo dia.

Em **Editar Pacote** há a mesma opção para agendar só as sessões ainda não usadas nem agendadas.

## Usar o pacote

Ao [faturar um atendimento](../agenda/faturar.md), escolha a forma **Pacote** e um pacote ativo do paciente.
Cada uso fica no **Histórico de uso**, nos detalhes do pacote.

## Editar e excluir

- **Editar**: nome, validade, observações e situação.
- **Excluir**: só pacotes sem nenhum uso. Pacotes já usados devem ser **cancelados**.
