---
title: Agenda
parent: Módulos
nav_order: 2
---

# Agenda
{: .no_toc }

Marcação de atendimentos com um ou mais serviços, recorrência, controle de status e faturamento direto do agendamento.
{: .fs-5 .fw-300 }

<details open markdown="block">
  <summary>Nesta página</summary>
  {: .text-delta }
1. TOC
{:toc}
</details>

---

## Telas

### Agenda do dia (`/agenda`)

- **Mini calendário** — os dias do mês que têm agendamento aparecem marcados (`GET agendamentos/dias-do-mes`). Clicar num dia carrega a linha do tempo daquele dia.
- **Linha do tempo do dia** — cartões com horário, paciente (com foto), serviços com ícone, veterinário e status.
- **Painéis laterais** — agendamentos de **hoje**, **pendentes** e **próximas 48 h**.
- **Ações no cartão** — editar, mudar o status, **faturar** e abrir o prontuário do paciente.

### Todos os agendamentos (`/agenda/todos`)

Lista com filtros combináveis:

| Filtro | Parâmetro |
|:--|:--|
| Status | `status` |
| Período | `data_inicio`, `data_fim` |
| Serviço | `servico_id` |
| Profissional | `veterinario_id` |
| Busca livre (paciente, tutor ou serviço) | `search` |

## Cadastro do agendamento

| Campo | Descrição |
|:--|:--|
| Paciente * | Busca por nome do pet ou do tutor (`GET agendamentos/buscar-pacientes?term=`). Mostra o endereço do tutor e um atalho "Ver Prontuário". |
| Serviços * | Um ou mais serviços do catálogo (tabela `agendamento_servicos`). |
| Data e hora | `age_data`, `age_hora` |
| Duração | `age_duracao`, em minutos |
| Veterinário | Membro da [Equipe](equipe) marcado como veterinário |
| Observações | `age_obs` |
| Lembrete | `age_lembrete` |
| Recorrência | `nenhuma`, `semanal`, `quinzenal` ou `mensal`, com data final opcional. Na semanal e na quinzenal, dá para escolher os dias da semana. |

### Recorrência

Quando a recorrência não é `nenhuma`, o sistema cria **uma série de agendamentos** numa única transação:

- todas as ocorrências compartilham o mesmo `age_grupo_id`;
- o intervalo é de 1 semana, 2 semanas ou 1 mês;
- sem data final, a série vai até **1 ano** à frente;
- o limite é de **52 ocorrências**, ou de **366** quando há dias da semana escolhidos;
- os serviços são copiados para cada ocorrência.

#### Dias da semana

Na recorrência **semanal** ou **quinzenal**, o formulário mostra os botões **Dom** a **Sáb** para escolher em quais dias o agendamento se repete. Exemplo: toda semana às segundas e terças.

- Ao escolher Semanal ou Quinzenal, o dia da semana da data informada já vem marcado.
- A série começa no primeiro dia marcado a partir da data informada. Dias marcados que caem antes dela, na mesma semana, ficam de fora.
- Na quinzenal, os dias marcados se repetem a cada duas semanas.
- Sem nenhum dia marcado, a série se repete no mesmo dia da semana da data informada.
- Se nenhuma data cair entre o início e o fim informados, o sistema mostra um erro e não cria nada.

Na API, os dias vão no campo `age_recorrencia_dias`, uma lista de números de `0` (domingo) a `6` (sábado). Esse campo só serve para gerar a série e não é gravado no banco.

```json
{
  "age_data": "2026-08-03",
  "age_recorrencia": "semanal",
  "age_recorrencia_fim": "2026-08-31",
  "age_recorrencia_dias": [1, 2]
}
```

## Status

| Status | Significado |
|:--|:--|
| `pendente` | Marcado, ainda não confirmado com o tutor |
| `confirmado` | Tutor confirmou |
| `concluido` | Atendimento feito (definido automaticamente ao faturar) |
| `cancelado` | Cancelado |

## Faturar um agendamento

O botão **Faturar** abre um modal com valor, desconto, forma de pagamento (Pix, Dinheiro, Cartão de Crédito, Cartão de Débito, Boleto ou **Pacote**), parcelas, vencimento e status (Pendente, Pago ou Cancelado).

Ao confirmar (`POST agendamentos/{id}/faturar`):

1. se a forma for **Pacote**, o sistema consome uma sessão (ou o valor, se o pacote for de crédito) do pacote escolhido, usando o **primeiro serviço** do agendamento — veja [Pacotes](pacotes);
2. cria a cobrança no [Faturamento](faturamento), ligada ao agendamento (`agendamento_id`), ao paciente e ao serviço principal;
3. se a cobrança nasce **Paga**, dá baixa no estoque dos insumos do serviço (ficha técnica/BOM — veja [Serviços](servicos));
4. marca o agendamento como `age_faturado = 1` e status `concluido`.

Faturar de novo um agendamento já faturado **edita a cobrança existente** em vez de criar outra, e **não consome o pacote outra vez**.

## Comunicação

Pelo backend é possível gerar links de WhatsApp de **lembrete do agendamento** e de **pós-consulta** — veja [Comunicação](comunicacao).

## API

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/agendamentos` | Lista com filtros (`status`, `data_inicio`, `data_fim`, `servico_id`, `veterinario_id`, `search`) |
| GET | `/agendamentos/dia?date=AAAA-MM-DD` | Dados do dia (padrão: hoje) |
| GET | `/agendamentos/proximos` | Próximos agendamentos |
| GET | `/agendamentos/buscar-pacientes?term=` | Busca de pacientes para o formulário |
| GET | `/agendamentos/dias-do-mes?year=&month=` | Dias com agendamento no mês |
| POST | `/agendamentos` | Cria (envie `age_servico` como lista de IDs de serviço) |
| PUT | `/agendamentos/{id}` | Edita (e sincroniza os serviços, se `age_servico` vier) |
| PATCH | `/agendamentos/{id}/status` | Muda só o status |
| POST | `/agendamentos/{id}/faturar` | Fatura (cria ou atualiza a cobrança) |
| GET | `/agendamentos/{id}/faturamento` | Cobrança ligada ao agendamento |
