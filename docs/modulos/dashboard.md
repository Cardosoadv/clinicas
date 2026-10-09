---
title: Dashboard
parent: Módulos
nav_order: 1
---

# Dashboard
{: .no_toc }

Tela inicial (`/`). Junta, numa só chamada (`GET /api/v1/dashboard`), o que a recepção e o veterinário precisam ver ao abrir o sistema.
{: .fs-5 .fw-300 }

## Indicadores (KPIs)

| Indicador | Como é calculado |
|:--|:--|
| **Agendamentos hoje** | Agendamentos com data de hoje |
| **Pacientes ativos** | Total de pacientes cadastrados |
| **Receita do mês** | Soma das cobranças **pagas** com data de serviço no mês corrente |
| **Pendentes** | Agendamentos com status `pendente` |

## Blocos

| Bloco | Conteúdo |
|:--|:--|
| **Agenda de hoje** | Atendimentos marcados para o dia |
| **Próximos agendamentos** | Próximos 7 dias (168 horas) |
| **Serviços do mês** | Quantidade de atendimentos por serviço no mês |
| **Atendimentos recentes** | Últimos 3 pacientes cadastrados |
| **Aniversariantes** | Pacientes que fazem aniversário no mês |
| **Alertas de estoque** | Produtos com quantidade igual ou abaixo do estoque mínimo |
| **Vacinas a vencer** | Vacinas com próxima dose nos próximos 7 dias |
| **Pós-consulta** | Até 5 agendamentos **concluídos** nos últimos 2 dias — candidatos a uma mensagem de acompanhamento |

Cada bloco mostra uma mensagem própria quando está vazio (por exemplo, "Estoque em dia." ou "Nenhuma vacina a vencer.").

## API

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/api/v1/dashboard` | Retorna `kpis`, `hoje`, `upcoming`, `servicos`, `recentes`, `aniversariantes`, `alertasEstoque`, `proximasVacinas` e `posConsulta` |
