---
titulo: Pacientes
resumo: Cadastro dos pets, sempre ligados a um tutor, com foto, dados de saúde e situação.
ordem: 20
telas: [pacientes]
palavras_chave: [pet, animal, cadastro, espécie, raça, foto]
---

A tela [Pacientes](/pacientes) lista os pets da clínica. Todo paciente pertence a um tutor
([Cliente](../clientes/index.md)).

## Lista

- **Filtros**: situação (Ativo, Inativo, Suspenso), espécie e busca pelo nome.
- **Painel lateral**: totais por situação, últimos cadastrados e aniversariantes do mês.
- **Ações**: ver o prontuário, editar e excluir.

## Cadastro

| Campo | Observação |
|---|---|
| **Tutor** * | cliente já cadastrado; busque pelo nome |
| **Nome** * | nome do pet |
| **Nascimento** | usado nos aniversariantes |
| **Sexo** | Fêmea, Macho ou Outro |
| **Espécie** e **Raça** | texto livre (ex.: Canina, Felina) |
| **Pelagem** | Curto, Médio, Longo ou Sem pelo |
| **Porte** | Mini, Pequeno, Médio, Grande ou Gigante |
| **Tipo sanguíneo** | DEA 1.1+, DEA 1.1-, A, B ou AB |
| **Situação** | Ativo, Inativo ou Suspenso |
| **Foto** | PNG, JPG ou WebP de até 2 MB; aparece na agenda, nas listas e no prontuário |

Os dados de saúde (doenças, alergias, medicamentos em uso, queixa principal) são preenchidos na
[anamnese](../prontuarios/anamnese.md).

> [!DICA]
> Atendimento de um cliente novo? Cadastre primeiro o tutor em [Clientes](../clientes/index.md) e depois o pet.
