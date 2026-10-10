---
title: Pacotes
parent: Módulos
nav_order: 7
---

# Pacotes
{: .no_toc }

Pacotes pré-pagos vendidos ao tutor para um paciente: **pacote de serviços** (ex.: 4 banhos) ou **pacote de crédito** (um saldo em reais).
{: .fs-5 .fw-300 }

<details open markdown="block">
  <summary>Nesta página</summary>
  {: .text-delta }
1. TOC
{:toc}
</details>

---

## Tipos

| Tipo | Como funciona |
|:--|:--|
| **Serviços** | Tem itens (serviço + quantidade + valor unitário). Cada uso consome **1 unidade** do item correspondente ao serviço atendido. |
| **Crédito** | Tem um saldo em reais (`saldo_valor`, igual ao valor total da compra). Cada uso desconta o valor do atendimento. |

## Ciclo de vida

```text
Pendente ──(cobrança paga)──► Ativo ──(consumido por completo)──► Esgotado
    │                           │
    └───────────── Cancelado ◄──┘
```

| Status | Significado |
|:--|:--|
| `Pendente` | Recém-criado, aguardando o pagamento. **Só aqui os itens podem ser alterados.** |
| `Ativo` | Pode ser usado no faturamento |
| `Esgotado` | Todos os itens usados, ou saldo zerado |
| `Cancelado` | Cancelado manualmente |

## Criar um pacote

Na tela `/pacotes` → **Novo Pacote**: paciente, nome, tipo, validade (opcional), observações, itens (para o tipo Serviços) e o bloco **💳 Pagamento e cobrança** (valor total, desconto, forma de pagamento e vencimento).

Ao salvar, numa transação:

1. o pacote é criado com status **Pendente**;
2. os itens são criados com `quantidade_usada = 0`;
3. uma cobrança **"Compra de Pacote: {nome}"** é lançada no [Faturamento](faturamento) como Pendente e ligada ao pacote (`pacote_id`);
4. se a opção **📅 Pré-agendar as sessões na Agenda** estiver marcada, os agendamentos das sessões são criados (veja abaixo). Se algum falhar, nada é salvo — nem o pacote.

## Pré-agendar as sessões

Num pacote de **Serviços**, marque **📅 Pré-agendar as sessões na Agenda** e informe:

| Campo | Uso |
|:--|:--|
| Data inicial | Data da 1ª sessão |
| Periodicidade | **Semanal** ou **Mensal** |
| Horário | Horário da 1ª sessão de cada dia |
| Duração de cada sessão | Padrão de 30 min |
| Veterinário responsável | Opcional |

Como os agendamentos são gerados:

- cada item com **serviço do catálogo** vira uma série com **um agendamento por sessão** (ex.: 4 banhos → 4 agendamentos), repetida toda semana ou todo mês a partir da data inicial;
- na mensal, o dia do mês é mantido (dia 31 vira o último dia dos meses mais curtos);
- com vários itens, cada um fica no **horário seguinte** ao anterior, no mesmo dia, para não se sobreporem. Ex.: banho às 14:00 e tosa às 14:30 (com 30 min de duração). Se os horários passarem da meia-noite, o sistema recusa;
- itens de **nome livre** (sem serviço do catálogo) não são agendados. Se nenhum item tiver serviço, o sistema recusa;
- cada agendamento fica com status **pendente**, com o serviço do item e ligado ao pacote (`agendamentos.pacote_id`). Ao faturar, o modal já sugere a forma **Pacote** com esse pacote, se ele estiver ativo.

### Pelo Editar Pacote

Em **Editar Pacote** (pacotes de serviços que não estejam Cancelados nem Esgotados) há a opção **📅 Pré-agendar na Agenda as sessões ainda não usadas nem agendadas**, com os mesmos campos. Ela agenda, por item:

```text
quantidade_total − quantidade_usada − agendamentos do pacote ainda pendentes (não cancelados e não faturados)
```

Assim, dá para pré-agendar um pacote antigo, ou completar a agenda depois de cancelar algumas sessões, sem duplicar as que já existem. Se não sobrar nenhuma sessão, o sistema avisa e não cria nada.

{: .nota }
Editar ou cancelar em massa as sessões pré-agendadas ainda não é possível: altere cada agendamento na Agenda. Excluir o pacote não apaga os agendamentos; eles só perdem o vínculo (`pacote_id` fica vazio).

{: .atencao }
A intenção é ativar o pacote quando a cobrança ligada a ele for paga: o `FatService` dispara o evento `cobranca_paga`. Porém **nenhum ouvinte está registrado** para esse evento em `app/Config/Events.php`, então hoje a ativação **não é automática**. Depois de receber o pagamento, abra **Editar Pacote** e mude o status para **Ativo**. Para corrigir, basta registrar `Events::on('cobranca_paga', fn (int $id) => (new \App\Services\PacoteService())->activatePacote($id));`.

## Usar um pacote

No faturamento de um agendamento (ou numa cobrança), escolha a forma de pagamento **Pacote** e um dos pacotes **ativos** do paciente (`GET pacientes/{id}/pacotes-disponiveis`).

- **Serviços**: procura um item do mesmo serviço com saldo (`quantidade_total > quantidade_usada`) e soma 1 ao usado. Se não houver, a operação é recusada com *"Serviço não disponível neste pacote ou esgotado"*. Quando a soma de todos os itens chega a zero, o pacote vira **Esgotado**.
- **Crédito**: recusa se o saldo for menor que o valor; senão desconta e, ao chegar a zero, marca **Esgotado**.
- Cada uso grava uma linha em `pacote_uso` (serviço, quantidade, valor descontado, data e observação), mostrada no **Histórico de uso** da tela de detalhes.
- A cobrança paga com pacote já nasce com status **Pago**.

{: .atencao }
No **faturamento de um agendamento** com forma Pacote, o consumo acontece duas vezes: uma no `AgendaService::billAppointment` e outra no `FatService::createCobranca`. Num pacote de serviços isso gasta 2 sessões (ou recusa a cobrança se só restava 1); num pacote de crédito, desconta o valor em dobro. Confira o saldo no **Histórico de uso** até a correção.

## Editar e excluir

- **Editar**: nome, validade, observações e status. Os itens só mudam enquanto o pacote estiver Pendente.
- **Excluir**: só é permitido se o pacote ainda não tiver uso. Pacotes já usados devem ser **cancelados**, para manter o histórico financeiro.

## API

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/pacotes` | Lista com o paciente |
| GET | `/pacotes/{id}` | Detalhe com itens e histórico de uso |
| POST | `/pacotes` | Cria pacote + itens + cobrança (`paciente_id`, `nome`, `tipo`, `valor_total`, `itens[]`...). Com `preagendar: true` e `preagendamento: {data_inicial, periodicidade, hora, duracao, veterinario_id}`, também pré-agenda as sessões |
| PUT | `/pacotes/{id}` | Edita |
| POST | `/pacotes/{id}/preagendar` | Pré-agenda as sessões restantes (`data_inicial`, `periodicidade`, `hora`, `duracao`, `veterinario_id`) |
| DELETE | `/pacotes/{id}` | Exclui (só sem uso) |
| GET | `/pacientes/{id}/pacotes-disponiveis` | Pacotes ativos do paciente, com itens |
