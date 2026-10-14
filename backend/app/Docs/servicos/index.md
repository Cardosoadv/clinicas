---
titulo: Serviços
resumo: Catálogo de procedimentos, com preço, duração e ficha técnica de insumos.
ordem: 50
telas: [servicos]
palavras_chave: [serviço, procedimento, preço, ficha técnica, insumos, BOM]
---

A tela [Serviços](/servicos) é o catálogo dos procedimentos oferecidos pela clínica. Só os serviços
**ativos** aparecem para agendar.

## Cadastro

| Campo | Observação |
|---|---|
| **Nome** * | nome do procedimento |
| **Ícone** | um emoji, exibido na agenda e nos relatórios (padrão 🐾) |
| **Valor** | preço de referência |
| **Tempo estimado** | duração em minutos |
| **Descrição** | texto livre |
| **Situação** | Ativo ou Inativo |

## Ficha técnica

Na ficha técnica, ligue ao serviço os produtos do [Estoque](../estoque/index.md) e a quantidade gasta em
cada atendimento. Exemplo: **Banho** → 30 ml de shampoo + 1 laço.

Quando uma cobrança desse serviço fica **Paga**, cada produto da ficha sai do estoque automaticamente.

> [!NOTA]
> Se um produto da ficha não tiver saldo, só a saída dele falha; os demais saem normalmente. Acompanhe os
> **alertas de estoque** no [Dashboard](../primeiros-passos/painel.md).

Ao salvar a ficha, a lista nova substitui a anterior.
