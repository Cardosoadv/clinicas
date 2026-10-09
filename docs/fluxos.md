---
title: Fluxos integrados
nav_order: 7
description: "Como os módulos conversam entre si: atendimento, pacotes, estoque e financeiro."
---

# Fluxos integrados
{: .no_toc }

As regras que atravessam vários módulos, passo a passo.
{: .fs-5 .fw-300 }

<details open markdown="block">
  <summary>Nesta página</summary>
  {: .text-delta }
1. TOC
{:toc}
</details>

---

## 1. Atendimento completo

```text
Cliente ──► Paciente ──► Agendamento ──► Prontuário ──► Faturar ──► Cobrança ──► Recibo
                              │                            │
                              │                            ├─► consome Pacote (se forma = Pacote)
                              │                            └─► baixa Estoque (se Pago e serviço com BOM)
                              └─► WhatsApp: lembrete / pós-consulta
```

1. Cadastre o **cliente** e o **paciente** (ou use `POST pacientes/mix-create`, que faz os dois e já agenda).
2. Crie o **agendamento** com um ou mais serviços e o veterinário.
3. Mude o status para **confirmado** depois de falar com o tutor.
4. No atendimento, o veterinário registra **anamnese, peso, vacinas, prescrições e evolução** no prontuário.
5. **Fature** o agendamento: a cobrança é criada, o agendamento vira **concluído** e `age_faturado = 1`.
6. Emita o **recibo** em Faturamento → Notas e envie o link público ao tutor.
7. Nos 2 dias seguintes, o atendimento aparece em **Pós-consulta** no Dashboard.

## 2. Venda e uso de pacote

```text
Novo Pacote (Pendente) ──► Cobrança "Compra de Pacote" (Pendente)
                                     │ pagamento recebido
                                     ▼
                         Pacote → Ativo  (hoje: manual, ver nota)
                                     │
             Faturar agendamento com forma = Pacote
                                     ▼
            Serviços: +1 em quantidade_usada   |   Crédito: saldo − valor
                                     ▼
                  Registro em pacote_uso   →   Esgotado ao zerar
```

{: .atencao }
O `FatService` dispara o evento `cobranca_paga` quando a cobrança de um pacote muda para **Pago**, mas não há ouvinte registrado. Ative o pacote manualmente em **Pacotes → Editar → Status: Ativo**. Detalhes em [Pacotes]({{ site.baseurl }}/modulos/pacotes#ciclo-de-vida).

Regras de proteção:

- Pacote só é consumido se estiver **Ativo**.
- Pacote de serviços só atende o **mesmo serviço** dos itens, enquanto houver saldo.
- Pacote de crédito recusa valores maiores que o saldo.
- Refaturar um agendamento já faturado **não** consome o pacote de novo.
- Pacote com uso não pode ser excluído, só cancelado.

## 3. Baixa automática de estoque (BOM)

```text
Serviço "Banho" ── ficha técnica ──► Shampoo 30 ml, Laço 1 un
         │
Cobrança do serviço fica Paga
         ▼
Saída de 30 ml de Shampoo + 1 Laço   (observação: "Saída automática via faturamento de serviço (BOM)")
```

- Acontece quando a cobrança **nasce Paga** (inclusive paga com pacote) ou **muda para Pago** a partir de outro status.
- Roda **uma vez** por cobrança — mudar de Pago para Pago não baixa de novo.
- Se um produto não tiver saldo, só a saída dele falha; as demais são feitas.
- Na fatura de agendamento, o serviço usado é o **primeiro** serviço do agendamento.

## 4. Compra de estoque → despesa

Na **entrada de estoque**, marque "Lançar no financeiro":

1. é criada a despesa "Compra de Estoque: {produto}", categoria **Estoque**, com o valor total;
2. a movimentação de entrada guarda o `despesa_id`;
3. a quantidade do produto aumenta;
4. tudo numa transação.

## 5. Do lançamento aos relatórios

| Origem | Vira | Entra em |
|:--|:--|:--|
| Faturar agendamento, cobrança manual, compra de pacote, importação de receitas | `fat_cobrancas` | Dashboard, Extrato; DRE e Livro Caixa **quando Pago** |
| Despesa manual, entrada de estoque, importação de despesas | `fat_despesas` | Extrato; DRE e Livro Caixa **quando Pago** |

Como o DRE e o Livro Caixa são em **regime de caixa**, mantenha o status das cobranças e despesas atualizado — uma cobrança paga e ainda marcada como Pendente não aparece no resultado.

## 6. Comunicação com o tutor

| Momento | Mensagem | Onde está o gatilho |
|:--|:--|:--|
| Antes da consulta | Lembrete de agendamento | Agendamento (API) |
| Depois da consulta | Pós-consulta | Bloco Pós-consulta do Dashboard (API) |
| Cobrança em aberto | Cobrança pendente | Cobrança (API) |
| Reforço de vacina | Lembrete de vacina | Bloco Vacinas a vencer (API) |
| Aniversário do pet | Parabéns | Bloco Aniversariantes (API) |
| Qualquer hora | Mensagem livre | Detalhe do cliente (tela) |

Todas ficam no **histórico de comunicações** do cliente. Os textos são editados em [Configurações]({{ site.baseurl }}/modulos/comunicacao).
