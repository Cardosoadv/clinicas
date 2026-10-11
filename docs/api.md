---
title: Referência da API
nav_order: 5
description: "Todos os endpoints REST da API v1."
---

# Referência da API
{: .no_toc }

Todas as rotas ficam sob **`/api/v1`**, aceitam e devolvem JSON (salvo uploads em `multipart/form-data`) e exigem sessão — exceto `auth/*` e `publico/*`. O formato das respostas e os códigos HTTP estão em [Arquitetura → Envelope de resposta]({{ site.baseurl }}/arquitetura#envelope-de-resposta).
{: .fs-5 .fw-300 }

<details open markdown="block">
  <summary>Nesta página</summary>
  {: .text-delta }
1. TOC
{:toc}
</details>

---

## Como chamar

```bash
API=http://localhost:8080/api/v1

curl -c cookies.txt -H 'Content-Type: application/json' \
     -d '{"email":"admin@suaclinica.com.br","password":"sua-senha","remember":true}' \
     $API/auth/login

curl -b cookies.txt $API/dashboard
```

No navegador, use `fetch(url, { credentials: 'include' })` a partir de uma origem liberada em `cors.allowedOrigins`.

{: .nota }
As rotas são definidas em dois arquivos: `app/Config/Routes.php` (principal) e `app/Routes/Routes.php` (carregado via `Config/Routing::$routeFiles`). O segundo repete parte das rotas e acrescenta `GET lojas/principal`, `GET agendamentos/{id}/faturamento` e as versões `PUT` de `lojas/{id}` e `prontuarios/pacientes/{id}/anamnese`. Rode `php backend/spark routes` para ver a tabela final.

## Autenticação (sem login)

| Método | Rota | Corpo / retorno |
|:--|:--|:--|
| POST | `/auth/login` | `{email, password, remember?}` → `{user: {id, username, email}}` |
| POST | `/auth/logout` | — |
| GET | `/auth/me` | `data`: usuário logado ou `null` |

## Público (sem login)

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/publico/recibos/{id}/{hash}` | Dados do recibo: nota, cobrança, paciente, tutor, loja principal e QR Code em base64 |

## Dashboard

| Método | Rota |
|:--|:--|
| GET | `/dashboard` |

## Pacientes

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/pacientes` | `?status=&especie=&search=` |
| GET | `/pacientes/painel` | Estatísticas, recentes, aniversariantes, espécies |
| GET | `/pacientes/busca` | `?term=` |
| POST | `/pacientes/mix-create` | Cliente + pet + agendamento numa transação |
| POST | `/pacientes/import` | CSV (`csv_file`) |
| GET | `/pacientes/{id}/pacotes-disponiveis` | Pacotes ativos |
| GET | `/pacientes/{id}/avatar/{arquivo}` | Foto |
| GET | `/pacientes/{id}` | Detalhe |
| POST | `/pacientes` | Cria |
| PUT | `/pacientes/{id}` | Edita |
| DELETE | `/pacientes/{id}` | Exclui |

## Agendamentos

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/agendamentos` | `?status=&data_inicio=&data_fim=&servico_id=&veterinario_id=&search=` |
| GET | `/agendamentos/dia` | `?date=AAAA-MM-DD` |
| GET | `/agendamentos/proximos` | Próximos |
| GET | `/agendamentos/buscar-pacientes` | `?term=` |
| GET | `/agendamentos/dias-do-mes` | `?year=&month=` |
| POST | `/agendamentos` | Cria (com `age_servico[]`, `age_recorrencia` e, na semanal ou quinzenal, `age_recorrencia_dias[]` com os dias da semana de 0 a 6) |
| PUT | `/agendamentos/{id}` | Edita |
| PATCH | `/agendamentos/{id}/status` | `{age_status}` |
| POST | `/agendamentos/{id}/faturar` | Cria/atualiza a cobrança |
| GET | `/agendamentos/{id}/faturamento` | Cobrança ligada |

## Clientes

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/clientes` | `?status=` (ativos, inativos, todos), `tipo=` (fisica, juridica), `search=` |
| GET | `/clientes/painel` | Estatísticas |
| GET | `/clientes/duplicados` | Grupos por nome + telefone |
| POST | `/clientes/merge` | `{ids: []}` |
| POST | `/clientes/import` | CSV (`csv_file`) |
| GET | `/clientes/{id}` | Detalhe |
| POST | `/clientes` | Cria |
| PUT | `/clientes/{id}` | Edita |
| DELETE | `/clientes/{id}` | Inativa |
| GET | `/clientes/{id}/pacientes` | Pets do cliente |
| GET | `/clientes/{id}/notas` | Notas |
| POST | `/clientes/{id}/notas` | `{texto, autor?}` |
| DELETE | `/clientes/{id}/notas/{notaId}` | Exclui nota |
| GET | `/clientes/{id}/comunicacoes` | Histórico |
| POST | `/clientes/{id}/comunicacoes` | `{mensagem}` → `{link}` do WhatsApp |

## Comunicação (links de WhatsApp)

| Método | Rota |
|:--|:--|
| GET | `/comunicacao/agendamentos/{id}/whatsapp` |
| GET | `/comunicacao/cobrancas/{id}/whatsapp` |
| GET | `/comunicacao/pos-consulta/{id}/whatsapp` |
| GET | `/comunicacao/vacinas/{id}/whatsapp` |
| GET | `/comunicacao/pacientes/{id}/aniversario/whatsapp` |

## Configurações

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/configuracoes/templates` | Modelos de mensagem e de impressão |
| PUT | `/configuracoes/templates` | `{meta_key, meta_value}` (só chaves permitidas) |

## Equipe

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/equipe` | Lista |
| GET | `/equipe/nao-vinculados` | Usuários sem membro |
| GET | `/equipe/{id}` | Detalhe |
| POST | `/equipe` | Cria (com `user_id` ou `email` + `password`) |
| PUT | `/equipe/{id}` | Edita |
| DELETE | `/equipe/{id}` | Exclui |

## Estoque

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/estoque/produtos` | Lista |
| GET | `/estoque/alertas` | Abaixo do mínimo |
| GET | `/estoque/produtos/{id}` | Detalhe |
| POST | `/estoque/produtos` | Cria |
| PUT | `/estoque/produtos/{id}` | Edita |
| DELETE | `/estoque/produtos/{id}` | Exclui |
| POST | `/estoque/movimentacoes/entrada` | Entrada (opcionalmente lança despesa) |
| POST | `/estoque/movimentacoes/saida` | Saída |

## Faturamento

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/faturamento/dashboard` | Painel financeiro |
| GET | `/faturamento/lancamentos` | Cobranças e despesas |
| POST | `/faturamento/cobrancas` | Cria cobrança |
| PUT | `/faturamento/cobrancas/{id}` | Edita cobrança |
| POST | `/faturamento/despesas` | Cria despesa (simples, parcelada, recorrente) |
| POST | `/faturamento/despesas/import` | CSV (`csv_file`) |
| PUT | `/faturamento/despesas/{id}` | Edita despesa |
| POST | `/faturamento/despesas/{id}/comprovante` | Upload (`comprovante`) |
| GET | `/faturamento/despesas/{id}/comprovante/{arquivo}` | Download |
| GET | `/faturamento/notas` | Notas e recibos |
| POST | `/faturamento/notas` | Emite |
| POST | `/receitas/import` | CSV de receitas (`csv_file`) |

## Lojas

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/lojas` | Lista |
| GET | `/lojas/principal` | Loja principal |
| GET | `/lojas/{id}/logo/{arquivo}` | Logo |
| GET | `/lojas/{id}` | Detalhe |
| POST | `/lojas` | Cria (multipart, `logo_file`) |
| POST | `/lojas/{id}` | Edita (multipart, `logo_file`) |
| DELETE | `/lojas/{id}` | Exclui |

## Pacotes

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/pacotes` | Lista |
| GET | `/pacotes/{id}` | Detalhe com itens e usos |
| POST | `/pacotes` | Cria pacote + cobrança |
| PUT | `/pacotes/{id}` | Edita |
| POST | `/pacotes/{id}/preagendar` | Pré-agenda na Agenda as sessões restantes |
| DELETE | `/pacotes/{id}` | Exclui (só sem uso) |

## Perfil

| Método | Rota | Corpo |
|:--|:--|:--|
| PUT | `/perfil/senha` | `{current_password, new_password, new_password_confirm}` |

## Prontuários

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/prontuarios` | `?search=` |
| GET | `/prontuarios/pacientes/{id}` | Prontuário completo |
| POST | `/prontuarios/pacientes/{id}/anamnese` | Multipart (`avatar`) |
| PUT | `/prontuarios/pacientes/{id}/odontograma` | Odontograma |
| POST | `/prontuarios/pacientes/{id}/evolucoes` | Nova evolução |
| PUT | `/prontuarios/evolucoes/{id}` | Edita evolução |
| DELETE | `/prontuarios/evolucoes/{id}` | Exclui evolução |
| POST | `/prontuarios/pacientes/{id}/vacinas` | Nova vacina |
| DELETE | `/prontuarios/vacinas/{id}` | Exclui vacina |
| POST | `/prontuarios/pacientes/{id}/pesos` | Nova pesagem |
| DELETE | `/prontuarios/pesos/{id}` | Exclui pesagem |
| POST | `/prontuarios/pacientes/{id}/imagens` | Multipart (`arquivo`, `titulo`, `data_exame`) |
| GET | `/prontuarios/pacientes/{id}/imagens/{arquivo}` | Imagem |
| POST | `/prontuarios/pacientes/{id}/prescricoes` | Nova prescrição (`itens[]`) |
| GET | `/prontuarios/prescricoes/{id}` | Detalhe |

## Relatórios

| Método | Rota | Parâmetros |
|:--|:--|:--|
| GET | `/relatorios/extrato` | `inicio`, `fim`, `tipo`, `status` |
| GET | `/relatorios/dre` | `inicio`, `fim` |
| GET | `/relatorios/livro-caixa` | `inicio`, `fim` |

## Serviços

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/servicos` | `?search=&status=` |
| GET | `/servicos/{id}` | Detalhe com produtos |
| POST | `/servicos` | Cria |
| PUT | `/servicos/{id}` | Edita |
| DELETE | `/servicos/{id}` | Exclui |
| PUT | `/servicos/{id}/produtos` | Ficha técnica (BOM) |
