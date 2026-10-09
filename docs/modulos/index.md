---
title: Módulos
nav_order: 4
has_children: true
permalink: /modulos/
description: "Descrição detalhada de cada módulo e funcionalidade do sistema."
---

# Módulos

O menu lateral do sistema é dividido em quatro grupos. Cada módulo tem uma página própria nesta documentação, com as telas, as regras de negócio, os campos e os endpoints da API que usa.

| Grupo | Módulos |
|:--|:--|
| **Principal** | [Dashboard](dashboard), [Agenda](agenda), [Pacientes](pacientes), [Clientes](clientes), [Prontuários](prontuarios) |
| **Operação** | [Serviços](servicos), [Pacotes](pacotes), [Estoque](estoque), [Equipe](equipe), [Lojas](lojas) |
| **Financeiro** | [Faturamento](faturamento), [Relatórios: Extrato, DRE e Livro Caixa](relatorios) |
| **Sistema** | [Comunicação e Configurações](comunicacao) |
| **Transversais** | [Autenticação e Perfil](autenticacao), [Importação CSV](importacao), busca global da barra superior |

## Busca global

A caixa de busca da barra superior procura ao mesmo tempo em **clientes**, **pacientes** e **serviços** a partir de 2 caracteres digitados (com espera de 300 ms entre teclas). Os resultados aparecem agrupados e dá para navegar com as setas ↑/↓ e abrir com Enter. Na tela de Serviços, a busca também filtra a própria lista pelo parâmetro `?search=` da URL.
