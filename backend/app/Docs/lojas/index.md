---
titulo: Lojas
resumo: Unidades da clínica e a loja principal, que define o nome e a logo do sistema e das impressões.
ordem: 90
telas: [lojas]
palavras_chave: [loja, unidade, filial, logo, CNPJ, endereço]
---

A tela [Lojas](/lojas) cadastra as unidades da clínica.

## Cadastro

| Campo | Observação |
|---|---|
| **Nome** * | nome da unidade |
| **CNPJ**, **e-mail**, **telefone** | dados de contato |
| **Endereço**, **cidade**, **estado**, **CEP** | estado com a sigla de 2 letras |
| **Logo** | PNG, JPG ou WebP |
| **Situação** * | Ativo ou Inativo |

## Loja principal

A loja principal é a **loja ativa mais antiga**. Os dados dela aparecem:

- no topo do sistema (nome e logo);
- no cabeçalho e no rodapé das impressões, como as receitas;
- na variável `{clinica}` das mensagens de WhatsApp.

Para trocar a loja principal, inative a atual.
