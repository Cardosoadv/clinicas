---
title: Serviços
parent: Módulos
nav_order: 6
---

# Serviços
{: .no_toc }

Catálogo de procedimentos oferecidos pela clínica, com preço, duração e **ficha técnica de insumos** (BOM — *Bill of Materials*).
{: .fs-5 .fw-300 }

## Lista (`/servicos`)

Busca (`?search=`, sincronizada com a busca global da barra superior) e filtro de situação (Ativo / Inativo). Ações: editar, excluir e editar a ficha técnica.

## Campos

| Campo | Descrição |
|:--|:--|
| Nome * | `ser_nome` |
| Ícone | Emoji de até 4 caracteres (`ser_icone`), exibido na agenda e nos relatórios (padrão 🐾) |
| Valor | Preço de referência (`ser_valor`) |
| Tempo estimado | Duração em minutos (`ser_tempo_estimado`) |
| POP | Referência a um procedimento operacional padrão (`pop_id`) |
| Descrição | Texto livre |
| Situação | Ativo / Inativo — só serviços ativos aparecem para agendar |

## Ficha técnica (BOM)
{: #ficha-tecnica-bom}

Na ficha técnica você liga ao serviço os **produtos do [Estoque](estoque)** e a **quantidade** consumida em cada atendimento (ex.: "Banho" → 30 ml de shampoo + 1 laço).

Quando uma cobrança desse serviço fica **Paga** — no ato da criação ou ao mudar de Pendente para Pago —, o sistema registra uma **saída automática** de cada produto vinculado, com a observação *"Saída automática via faturamento de serviço (BOM)"*. Se um produto não tiver saldo suficiente, a saída dele falha e os demais seguem normalmente.

A ficha é salva de uma vez (`PUT servicos/{id}/produtos`): a lista enviada substitui a anterior.

## API

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/servicos?search=&status=` | Lista |
| GET | `/servicos/{id}` | Detalhe com os produtos vinculados |
| POST | `/servicos` | Cria |
| PUT | `/servicos/{id}` | Edita |
| DELETE | `/servicos/{id}` | Exclui (*soft delete*) |
| PUT | `/servicos/{id}/produtos` | Sincroniza a ficha técnica (`produtos: [{produto_id, quantidade}]`) |
