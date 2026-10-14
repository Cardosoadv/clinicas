---
titulo: Clientes
resumo: Cadastro dos tutores, pessoa física ou jurídica, com documentos, contatos e endereço.
ordem: 30
telas: [clientes]
palavras_chave: [tutor, cliente, CPF, CNPJ, cadastro, CRM]
---

A tela [Clientes](/clientes) reúne os tutores dos pacientes.

## Lista

- **Filtros**: situação (ativos, inativos ou todos), tipo de pessoa (física ou jurídica) e busca.
- **Painel lateral**: totais, últimos clientes cadastrados e aniversariantes do mês.
- **Ações**: ver o [detalhe](detalhe.md), editar e excluir.

> [!NOTA]
> **Excluir** um cliente o torna **inativo**: ele some da lista padrão, mas continua no filtro "inativos" e "todos".

## Cadastro

| Grupo | Campos |
|---|---|
| **Identificação** | nome *, razão social, apelido, RG, CPF, CNPJ e nascimento |
| **Contato** | telefones e e-mails |
| **Endereço** | rua, número, complemento, bairro, CEP, cidade e estado |
| **Comercial** | vendedor, empresa, origem do cliente e última compra |
| **Outros** | observações |

O CPF e o CNPJ são opcionais, mas, se informados, precisam ser válidos. O sistema formata os números enquanto
você digita.

> [!DICA]
> O primeiro telefone válido é usado para as mensagens de WhatsApp. Separe vários números com vírgula.

Encontrou o mesmo tutor cadastrado duas vezes? Veja [Clientes duplicados](duplicados.md).
