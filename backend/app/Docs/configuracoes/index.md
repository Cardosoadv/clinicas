---
titulo: Configurações
resumo: Modelos das mensagens de WhatsApp e o cabeçalho e rodapé das impressões.
ordem: 120
telas: [configuracoes]
palavras_chave: [configuração, modelo, mensagem, whatsapp, cabeçalho, rodapé, variáveis]
---

A tela [Configurações](/configuracoes) mostra um cartão por modelo. Cada cartão traz a descrição, as
**variáveis disponíveis** (clique numa delas para inseri-la no texto) e o botão **Salvar**.

## Mensagens de WhatsApp

| Modelo | Variáveis |
|---|---|
| **Lembrete de Agendamento** | `{tutor}` `{pet}` `{servico}` `{data}` `{hora}` `{clinica}` |
| **Cobrança Pendente** | `{tutor}` `{pet}` `{valor}` `{data}` `{clinica}` |
| **Pós-Consulta** | `{tutor}` `{pet}` `{servico}` `{clinica}` |
| **Aniversário do Paciente** | `{tutor}` `{pet}` `{clinica}` |
| **Lembrete de Vacina** | `{tutor}` `{pet}` `{vacina}` `{data}` `{clinica}` |

`{tutor}` usa só o primeiro nome do tutor; `{clinica}` é o nome da [loja principal](../lojas/index.md#loja-principal).

> [!NOTA]
> O sistema não envia mensagens sozinho: ele abre o WhatsApp com a conversa e o texto prontos, e quem envia é
> o atendente.

## Cabeçalho e rodapé das impressões

Os modelos **Cabeçalho** e **Rodapé** formatam o topo e o fim das impressões, como a receita. Aceitam HTML
básico e as variáveis `{logo}`, `{clinica}`, `{cnpj}`, `{endereco}`, `{telefone}`, `{email}`, `{veterinario}`
e `{crmv}`; o rodapé aceita também `{data}` e `{data_extenso}`.

Deixe em branco para usar o layout padrão: logo, nome e CNPJ da loja principal no topo; no rodapé, a data
por extenso e a assinatura do veterinário com o CRMV.
