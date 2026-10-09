---
title: Banco de dados
nav_order: 6
description: "Tabelas, campos e relacionamentos criados pelas migrations."
---

# Banco de dados
{: .no_toc }

Estrutura criada pelas migrations de `backend/app/Database/Migrations/` (MySQL/MariaDB, `utf8mb4`). As tabelas do Shield (`users`, `auth_*`) e do Settings (`settings`) vêm dos próprios pacotes, com `php spark migrate --all`.
{: .fs-5 .fw-300 }

<details open markdown="block">
  <summary>Nesta página</summary>
  {: .text-delta }
1. TOC
{:toc}
</details>

---

## Relacionamentos

```text
clientes 1─┬─N pacientes 1─┬─N agendamentos N─N servicos (agendamento_servicos)
           │               ├─N paciente_pesos
           ├─N cliente_notas├─N vacinas
           └─N comunicacoes ├─N evolucoes ──► equipe (veterinario_id)
                            ├─N prescricoes 1─N prescricao_itens
                            ├─N imagens
                            ├─N odontogramas
                            ├─N pacotes 1─┬─N pacote_itens ──► servicos
                            │             └─N pacote_uso
                            ├─N fat_cobrancas ──► pacotes, servicos, agendamentos
                            └─N fat_notas ──► fat_cobrancas

servicos 1─N servico_produtos N─1 estoque_produtos 1─N estoque_movimentacoes ──► fat_despesas
equipe ──► users (Shield, user_id)
lojas, configs, fat_despesas: independentes
```

Quase todas as tabelas têm `created_at`, `updated_at` e `deleted_at` (*soft delete*: excluir só preenche `deleted_at`).

## Cadastros

### `clientes`

Tutores. `id`, `nome`, `razao_social`, `apelido`, `observacoes`, `rg`, `cpf`, `cnpj`, `nascimento`, `telefones`, `emails`, `rua`, `numero`, `complemento`, `bairro`, `cep`, `cidade`, `estado`, `vendedor`, `empresa`, `origem_cliente`, `ultima_compra`, `exportacao` (ID no sistema de origem).

### `cliente_notas`

`id`, `cliente_id`, `texto`, `autor`.

### `comunicacoes`

Histórico de mensagens. `id`, `cliente_id`, `canal` (ex.: `whatsapp`), `tipo` (`agendamento`, `cobranca`, `pos_consulta`, `vacina`, `aniversario`, `manual`), `paciente_nome`, `mensagem`, `created_at`.

### `pacientes`

Chave `paciente_id`; `cliente_id` → `clientes`.

| Campo | Tipo / valores |
|:--|:--|
| `paciente_nome`, `paciente_avatar`, `paciente_nascimento` | — |
| `paciente_sexo` | ENUM Fêmea, Macho, Outro |
| `paciente_raca`, `paciente_especie` | texto |
| `paciente_pelagem` | ENUM Curto, Médio, Longo, Sem pelo |
| `paciente_porte` | texto |
| `paciente_sangue` | ENUM DEA 1.1+, DEA 1.1-, A, B, AB |
| `paciente_doenca_sist`, `paciente_alergia_med` | ENUM Sim, Não |
| `paciente_endereco`, `paciente_conheceu`, `paciente_condicoes`, `paciente_caracteristicas`, `paciente_medicamentos`, `paciente_saude_obs`, `paciente_queixa`, `paciente_habitos` | texto |
| `paciente_status` | ENUM Ativo, Inativo, Suspenso |
| `exportacao` | ID no sistema de origem |

### `paciente_pesos`

`id`, `paciente_id`, `peso`, `data_pesagem`, `observacoes`.

### `equipe`

Chave `equ_id`. `user_id` (→ `users` do Shield), `equ_pronome`, `equ_nome`, `equ_crmv`, `equ_especialidade`, `equ_is_veterinario`, `equ_status` (ENUM Ativo, Inativo).

### `lojas`

`id`, `nome`, `cnpj`, `email`, `telefone`, `endereco`, `cidade`, `estado`, `cep`, `logo`, `status` (Ativo, Inativo).

### `configs`

Chave/valor: `id`, `meta_key`, `meta_value`, `description`. Guarda os [modelos de mensagem e de impressão]({{ site.baseurl }}/modulos/comunicacao).

## Operação

### `servicos`

Chave `ser_id`. `ser_nome`, `ser_icone`, `ser_valor`, `ser_tempo_estimado`, `pop_id`, `ser_descricao`, `ser_status` (ENUM Ativo, Inativo).

### `servico_produtos` (ficha técnica / BOM)

`id`, `ser_id`, `produto_id`, `quantidade`.

### `agendamentos`

Chave `age_id`.

| Campo | Tipo / valores |
|:--|:--|
| `age_grupo_id` | Agrupa as ocorrências de uma série recorrente |
| `paciente_id` | → `pacientes` |
| `age_data`, `age_hora`, `age_duracao` | Data, hora e duração (min) |
| `age_obs`, `age_lembrete` | — |
| `age_status` | ENUM pendente, confirmado, cancelado, concluido |
| `age_veterinario` | Profissional |
| `age_faturado` | 0/1 |
| `age_recorrencia` | ENUM nenhuma, semanal, quinzenal, mensal |
| `age_recorrencia_fim` | Data final da série |

### `agendamento_servicos`

`id`, `age_id`, `ser_id`, `created_at`.

### `pacotes`

`id`, `paciente_id`, `nome`, `tipo` (ENUM Serviços, Crédito), `saldo_valor`, `data_validade`, `status` (ENUM Pendente, Ativo, Esgotado, Cancelado), `observacoes`.

### `pacote_itens`

`id`, `pacote_id`, `servico_id`, `item_nome`, `quantidade_total`, `quantidade_usada`, `valor_unitario`.

### `pacote_uso`

`id`, `pacote_id`, `servico_id`, `quantidade`, `valor_descontado`, `data_uso`, `observacao`.

### `estoque_produtos`

`id`, `nome`, `descricao`, `unidade_medida`, `quantidade_atual`, `estoque_minimo`, `valor_venda`.

### `estoque_movimentacoes`

`id`, `produto_id`, `tipo` (ENUM Entrada, Saída, Ajuste), `quantidade`, `valor_unitario`, `valor_total`, `despesa_id` (→ `fat_despesas`), `observacao`.

## Prontuário

| Tabela | Campos |
|:--|:--|
| `evolucoes` | `id`, `paciente_id`, `veterinario_id` (→ `equipe.equ_id`), `data_registro`, `titulo`, `descricao` |
| `vacinas` | `id`, `paciente_id`, `vacina_nome`, `data_aplicacao`, `data_proxima_dose`, `lote`, `fabricante`, `observacoes` |
| `prescricoes` | `id`, `paciente_id`, `veterinario_id`, `data_prescricao`, `observacoes` |
| `prescricao_itens` | `id`, `prescricao_id`, `medicamento`, `dosagem`, `frequencia`, `duracao`, `via_administracao`, `particao` |
| `imagens` | `id`, `paciente_id`, `titulo`, `arquivo_url`, `data_exame` |
| `odontogramas` | `id`, `paciente_id`, `data_registro`, `mapa_dentes` (JSON), `observacoes` |

## Financeiro

### `fat_cobrancas` (contas a receber)

| Campo | Tipo / valores |
|:--|:--|
| `paciente_id` | → `pacientes` |
| `pacote_id` | → `pacotes` (pagamento com pacote, ou compra do pacote) |
| `agendamento_id` | → `agendamentos` (faturamento do agendamento) |
| `servico`, `servico_id` | Descrição e serviço do catálogo |
| `valor`, `desconto` | — |
| `data_servico` | Data usada nos relatórios |
| `forma_pagamento`, `parcelas`, `vencimento` | — |
| `status` | ENUM Pendente, Pago, Atrasado, Cancelado |
| `observacoes` | — |

### `fat_despesas` (contas a pagar)

`id`, `descricao`, `categoria`, `fornecedor`, `valor`, `data_vencimento`, `forma_pagamento`, `status` (ENUM Pendente, Pago, Cancelado), `des_grupo_id` (agrupa parcelas/recorrências), `des_parcela_num`, `des_parcela_total`, `des_recorrencia`, `des_recorrencia_fim`, `comprovante` (caminho em `writable/`).

### `fat_notas` (recibos e NFS-e)

`id`, `hash` (token do link público), `cobranca_id`, `paciente_id`, `tipo` (ENUM Recibo, NFS-e), `cpf_responsavel`, `data_emissao`, `descricao`, `valor`.

## Histórico das migrations

| Migration | O que faz |
|:--|:--|
| `2026-07-26-100000` … `100014` | Estrutura inicial (consolidada) de todas as tabelas acima |
| `2026-08-02-120000_FixLojasStatusColumn` | Ajusta a coluna `status` de `lojas` |
| `2026-08-02-130000_CreateClienteExtrasTables` | Cria `cliente_notas` e `comunicacoes` |
| `2026-08-14-090000_AddAgendamentoIdToFatCobrancas` | Acrescenta `agendamento_id` em `fat_cobrancas` |

Comandos úteis:

```bash
php backend/spark migrate --all        # aplica tudo (app + Shield + Settings)
php backend/spark migrate:status       # mostra o que já rodou
php backend/spark migrate:rollback     # desfaz o último lote
php backend/spark make:migration Nome  # cria uma nova migration
```
