---
titulo: Agenda
resumo: Marcar atendimentos com um ou mais serviços, repetir em série e acompanhar o status.
ordem: 10
telas: [agenda]
palavras_chave: [agendamento, consulta, horário, recorrência, calendário]
---

A tela [Agenda](/agenda) mostra os atendimentos de um dia.

## Agenda do dia

- **Mini calendário**: os dias com agendamento aparecem marcados. Clique num dia para ver os atendimentos dele.
- **Linha do tempo**: um cartão por atendimento, com horário, paciente (com foto), serviços, veterinário e status.
- **Painéis laterais**: agendamentos de **hoje**, **pendentes** e das **próximas 48 horas**.
- **Ações do cartão**: editar, mudar o status, [faturar](faturar.md) e abrir o prontuário do paciente.

Para ver todos os agendamentos, com filtros, use [Todos os agendamentos](todos.md).

## Novo agendamento

| Campo | Como preencher |
|---|---|
| **Paciente** * | digite o nome do pet ou do tutor e escolha na lista |
| **Serviços** * | um ou mais serviços do catálogo |
| **Data e hora** | dia e horário do atendimento |
| **Duração** | em minutos |
| **Veterinário** | membro da [Equipe](../equipe/index.md) marcado como veterinário |
| **Observações** e **Lembrete** | texto livre |
| **Recorrência** | nenhuma, semanal, quinzenal ou mensal, com data final opcional |

## Repetir o agendamento

Com uma recorrência escolhida, o sistema cria **uma série** de agendamentos de uma vez:

- o intervalo é de 1 semana, 2 semanas ou 1 mês;
- na mensal, o dia do mês se mantém; nos meses mais curtos, vale o último dia (31/01 → 28/02 → 31/03);
- sem data final, a série vai até 1 ano à frente;
- na **semanal** e na **quinzenal**, os botões **Dom** a **Sáb** escolhem em quais dias o atendimento se
  repete (ex.: toda segunda e terça). Sem dia marcado, vale o dia da semana da data informada.

> [!NOTA]
> Os serviços escolhidos são copiados para cada agendamento da série. Depois de criada, cada ocorrência é
> editada separadamente.

## Status

| Status | Significado |
|---|---|
| **Pendente** | marcado, ainda não confirmado com o tutor |
| **Confirmado** | o tutor confirmou |
| **Concluído** | atendimento feito; o status muda sozinho ao [faturar](faturar.md) |
| **Cancelado** | o atendimento não vai acontecer |
