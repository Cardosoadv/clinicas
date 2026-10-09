---
title: Equipe
parent: Módulos
nav_order: 9
---

# Equipe
{: .no_toc }

Profissionais e colaboradores da clínica, veterinários com CRMV e o vínculo de cada um com um login do sistema.
{: .fs-5 .fw-300 }

## Campos

| Campo | Descrição |
|:--|:--|
| Pronome de tratamento | Ex.: Dr., Dra. (`equ_pronome`) |
| Nome * | 3 a 255 caracteres |
| CRMV | Até 50 caracteres — sai na receita impressa |
| Especialidade | Texto livre |
| É veterinário | Marca o profissional como veterinário (aparece na agenda e assina prescrições e evoluções) |
| Situação | Ativo / Inativo |
| Acesso ao sistema | "Sem acesso ao sistema", vincular um **usuário existente** ainda sem membro (`GET equipe/nao-vinculados`) ou **criar um login novo** com e-mail e senha |

## Login do profissional

- Ao informar e-mail (e opcionalmente senha) sem escolher usuário existente, o sistema cria o usuário no Shield, na mesma transação do cadastro do membro.
- Sem senha, o login é criado com a senha padrão **`Petys@2026`**. Peça para o profissional trocá-la no primeiro acesso ([Perfil → trocar senha](autenticacao#trocar-a-senha)).
- O vínculo `equipe.user_id` é o que permite ao sistema saber **qual veterinário** está logado ao registrar prescrições e evoluções.

## Limitações conhecidas
{: #limitacoes-conhecidas}

{: .atencao }
O cadastro com login novo adiciona o usuário ao grupo **`equipe`**, mas esse grupo **não existe** em `app/Config/AuthGroups.php` (há apenas `superadmin`, `admin`, `developer`, `user` e `beta`). O Shield lança uma exceção para grupos desconhecidos, então a operação termina em erro 500 — e o usuário do Shield pode ficar criado sem o membro da equipe. Até ajustar, crie o usuário pela CLI (`php spark shield:user create`) e **vincule** o usuário existente no cadastro do membro — ou acrescente um grupo `equipe` em `$groups` no `AuthGroups.php`.

Não há, por enquanto, controle de permissões por perfil nas telas: todo usuário logado acessa todos os módulos.

## API

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/equipe` | Lista |
| GET | `/equipe/nao-vinculados` | Usuários do Shield ainda sem membro da equipe |
| GET | `/equipe/{id}` | Detalhe |
| POST | `/equipe` | Cria (com `user_id` **ou** `email` + `password`) |
| PUT | `/equipe/{id}` | Edita |
| DELETE | `/equipe/{id}` | Exclui (*soft delete*) |
