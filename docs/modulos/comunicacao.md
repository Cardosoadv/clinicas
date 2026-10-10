---
title: Comunicação e Configurações
parent: Módulos
nav_order: 13
---

# Comunicação e Configurações
{: .no_toc }

Mensagens de WhatsApp geradas a partir de modelos editáveis e o cabeçalho/rodapé usado nas impressões.
{: .fs-5 .fw-300 }

<details open markdown="block">
  <summary>Nesta página</summary>
  {: .text-delta }
1. TOC
{:toc}
</details>

---

## Como funciona o envio por WhatsApp

O sistema **não usa a API oficial do WhatsApp** e não envia nada sozinho. Ele monta um link `https://wa.me/{telefone}/?text={mensagem}` que abre o WhatsApp (web ou app) já com a conversa e o texto prontos — quem envia é o atendente.

1. O modelo da mensagem é lido de `configs` (ou o texto padrão é usado).
2. As variáveis `{...}` são trocadas pelos dados reais. `{tutor}` usa só o **primeiro nome** do tutor.
3. O telefone é o primeiro número válido do campo "Telefones" do cliente (separadores aceitos: `,` `;` `|`). Números com 10 ou 11 dígitos recebem o DDI **55**.
4. A mensagem é registrada no **histórico de comunicações** do cliente (canal `whatsapp`, com o tipo e o nome do pet).

## Mensagens disponíveis

| Tipo | Endpoint (GET) | Variáveis |
|:--|:--|:--|
| Lembrete de agendamento | `/comunicacao/agendamentos/{id}/whatsapp` | `{tutor}` `{pet}` `{servico}` `{data}` `{hora}` `{clinica}` |
| Cobrança pendente | `/comunicacao/cobrancas/{id}/whatsapp` | `{tutor}` `{pet}` `{valor}` `{data}` (vencimento) `{clinica}` |
| Pós-consulta | `/comunicacao/pos-consulta/{id}/whatsapp` (id do agendamento) | `{tutor}` `{pet}` `{servico}` `{clinica}` |
| Lembrete de vacina | `/comunicacao/vacinas/{id}/whatsapp` | `{tutor}` `{pet}` `{vacina}` `{data}` (próxima dose) `{clinica}` |
| Aniversário do paciente | `/comunicacao/pacientes/{id}/aniversario/whatsapp` | `{tutor}` `{pet}` `{clinica}` |
| Mensagem livre | `POST /clientes/{id}/comunicacoes` | — (texto digitado no [detalhe do cliente](clientes#detalhe-do-cliente)) |

`{clinica}` é o nome da [loja principal](lojas#loja-principal).

{: .nota }
Na interface atual, o envio de WhatsApp está disponível no **detalhe do cliente** (mensagem livre). Os cinco endpoints automáticos acima já funcionam no backend e podem ser ligados a botões na agenda, no faturamento, nas vacinas e no dashboard.

## Configurações (`/configuracoes`)

A tela lista um cartão por modelo. Cada cartão mostra a descrição, as **variáveis disponíveis** (clique numa delas para inseri-la no texto) e o botão Salvar.

### Modelos de mensagem

| Chave | Título | Texto padrão |
|:--|:--|:--|
| `whatsapp_template_agendamento` | Lembrete de Agendamento | Olá {tutor}, passando para lembrar do agendamento de {pet} para {servico} em {data} às {hora}. Podemos confirmar? 🐾 |
| `whatsapp_template_cobranca` | Cobrança Pendente | Olá {tutor}, informamos que consta uma pendência de {pet} no valor de R$ {valor} (vencimento em {data}). Favor regularizar. Obrigado! 💰 |
| `whatsapp_template_pos_consulta` | Pós-Consulta | Olá {tutor}, como está o {pet} após o procedimento de {servico}? Esperamos que esteja se recuperando bem! Qualquer dúvida, estamos à disposição. 🐾 |
| `whatsapp_template_aniversario` | Aniversário do Paciente | Olá {tutor}, hoje é um dia especial! Queremos desejar um feliz aniversário para o(a) {pet}! Muita saúde e petiscos! 🎂🎉 🐾 |
| `whatsapp_template_vacina` | Lembrete de Vacina | Olá {tutor}, passando para lembrar que o reforço da vacina {vacina} de {pet} está agendado para {data}. Vamos garantir a proteção do seu amiguinho? 💉🐾 |

### Cabeçalho e rodapé das impressões
{: #cabecalho-e-rodape}

| Chave | Uso | Variáveis |
|:--|:--|:--|
| `report_header` | Topo das impressões (aceita HTML básico) | `{logo}` `{clinica}` `{cnpj}` `{endereco}` `{telefone}` `{email}` `{veterinario}` `{crmv}` |
| `report_footer` | Rodapé das impressões (aceita HTML básico) | as mesmas, mais `{data}` e `{data_extenso}` |

Deixe em branco para usar o **layout padrão**: logo, nome e CNPJ da loja principal no topo; endereço, telefone e e-mail; no rodapé, a data por extenso, a assinatura do veterinário e o CRMV.

Os valores das variáveis são escapados (proteção contra HTML injetado); só `{logo}` vira uma tag `<img>`. Variáveis desconhecidas ficam como estão no texto.

### Segurança

Só as 7 chaves acima podem ser gravadas pela API — qualquer outra `meta_key` é recusada.

## API

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/configuracoes/templates` | Lista os 7 modelos |
| PUT | `/configuracoes/templates` | Grava um modelo (`meta_key`, `meta_value`) |
| GET | `/comunicacao/...` | Links de WhatsApp (tabela acima) — devolvem a URL `wa.me` |
