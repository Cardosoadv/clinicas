---
title: Pacientes
parent: Módulos
nav_order: 3
---

# Pacientes
{: .no_toc }

Cadastro dos pets, sempre ligados a um tutor ([Cliente](clientes)).
{: .fs-5 .fw-300 }

## Lista de pacientes (`/pacientes`)

- **Filtros**: situação (`Ativo`, `Inativo`, `Suspenso`), espécie (as espécies já cadastradas viram opções) e busca livre.
- **Painel lateral** (`GET pacientes/painel`): totais por situação, últimos 5 cadastrados, aniversariantes do mês.
- **Ações**: ver prontuário, editar e excluir.

## Campos do cadastro

| Campo | Valores / observação |
|:--|:--|
| Tutor * | Cliente existente (busca por nome) |
| Nome * | 2 a 255 caracteres |
| Nascimento | Usado nos aniversariantes |
| Sexo | Fêmea, Macho, Outro |
| Espécie | Texto livre (ex.: Canina, Felina) — na anamnese: Canina, Felina ou Exóticos |
| Raça | Texto livre |
| Pelagem | Curto, Médio, Longo, Sem pelo |
| Porte | Mini, Pequeno, Médio, Grande, Gigante |
| Tipo sanguíneo | DEA 1.1+, DEA 1.1-, A, B, AB |
| Situação | Ativo, Inativo, Suspenso |
| Foto | Avatar exibido na agenda, nas listas e no prontuário |
| Dados de saúde | Doença sistêmica (Sim/Não), condições, características, alergia a medicamentos (Sim/Não), medicamentos em uso, observações de saúde, queixa principal, hábitos — editados na [anamnese](prontuarios#anamnese) |
| Como conheceu a clínica | `paciente_conheceu` |

## Cadastro rápido (cliente + pet + agendamento)

O endpoint `POST pacientes/mix-create` faz, **numa única transação**:

1. procura o tutor pelo **CPF**; se não achar e houver nome, **cria o cliente**;
2. cria o **pet** ligado a esse tutor;
3. se vierem dados de agendamento, cria o **agendamento**.

Útil para o primeiro atendimento de um cliente novo.

## Fotos

As fotos ficam em `writable/imagens/{paciente_id}/` e só são servidas para usuários logados, por `GET pacientes/{id}/avatar/{arquivo}`. Formatos aceitos: PNG, JPG/JPEG e WebP, até 2 MB.

## API

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/pacientes?status=&especie=&search=` | Lista filtrada (com dados do último agendamento) |
| GET | `/pacientes/painel` | Estatísticas, recentes, aniversariantes e espécies |
| GET | `/pacientes/busca?term=` | Busca rápida |
| POST | `/pacientes/mix-create` | Cliente + pet + agendamento |
| POST | `/pacientes/import` | [Importação CSV](importacao) |
| GET | `/pacientes/{id}/pacotes-disponiveis` | [Pacotes](pacotes) ativos do pet |
| GET | `/pacientes/{id}/avatar/{arquivo}` | Foto |
| GET | `/pacientes/{id}` | Detalhe |
| POST | `/pacientes` | Cria (`paciente_nome` e `cliente_id` obrigatórios) |
| PUT | `/pacientes/{id}` | Edita |
| DELETE | `/pacientes/{id}` | Exclui (*soft delete*) |
