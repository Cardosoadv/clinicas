---
title: Início
layout: home
nav_order: 1
description: "Documentação do Clínicas, sistema de gestão para clínicas veterinárias."
permalink: /
---

# Clínicas — Sistema de Gestão Veterinária
{: .fs-9 }

Agenda, prontuário eletrônico, faturamento, pacotes pré-pagos, estoque, equipe e relatórios financeiros numa única aplicação web.
{: .fs-6 .fw-300 }

[Instalar agora]({{ site.baseurl }}/instalacao){: .btn .btn-primary .fs-5 .mb-4 .mb-md-0 .mr-2 }
[Ver os módulos]({{ site.baseurl }}/modulos/){: .btn .fs-5 .mb-4 .mb-md-0 }

---

## O que é

O **Clínicas** é um sistema de gestão para clínicas veterinárias (pet shops e consultórios). Ele cobre o ciclo completo do atendimento:

1. o **tutor** (cliente) e o **paciente** (pet) são cadastrados;
2. a recepção marca o **agendamento** com um ou mais serviços;
3. o veterinário registra anamnese, vacinas, peso, prescrições, evolução e imagens no **prontuário**;
4. o atendimento é **faturado** — com pagamento avulso ou consumindo um **pacote** pré-pago — e os insumos usados saem automaticamente do **estoque**;
5. o financeiro acompanha receitas, despesas, **DRE**, **extrato** e **livro caixa**;
6. o tutor recebe lembretes por **WhatsApp** e pode abrir o **recibo** por um link público com QR Code.

## Tecnologias

| Camada | Tecnologia |
|:--|:--|
| Frontend | React 19, TypeScript 6, Vite 8, React Router 7, ícones Lucide |
| Backend | PHP 8.1+, CodeIgniter 4, padrão **MVCRS** (Model–View–Controller–Repository–Service) |
| Autenticação | CodeIgniter Shield (sessão com cookie) |
| Banco de dados | MySQL / MariaDB (driver MySQLi, `utf8mb4`) |
| Outras bibliotecas | `chillerlan/php-qrcode` (QR Code dos recibos), `firebase/php-jwt` |
| Testes | PHPUnit 10 (backend), oxlint (frontend) |
| Deploy | GitHub Actions → FTP (Hostinger), Apache com `mod_rewrite` |

## Módulos

| Módulo | Para que serve |
|:--|:--|
| [Dashboard]({{ site.baseurl }}/modulos/dashboard) | Visão do dia: KPIs, agenda, aniversariantes, vacinas a vencer, estoque baixo e pós-consulta |
| [Agenda]({{ site.baseurl }}/modulos/agenda) | Agendamentos com vários serviços, recorrência, status e faturamento direto |
| [Clientes (CRM)]({{ site.baseurl }}/modulos/clientes) | Tutores PF/PJ, notas internas, histórico de comunicação e mesclagem de duplicados |
| [Pacientes]({{ site.baseurl }}/modulos/pacientes) | Cadastro dos pets com dados clínicos básicos e foto |
| [Prontuários]({{ site.baseurl }}/modulos/prontuarios) | Anamnese, histórico, vacinas, peso, prescrições (receita impressa), evolução e imagens |
| [Serviços]({{ site.baseurl }}/modulos/servicos) | Catálogo de procedimentos com preço, duração e ficha técnica de insumos (BOM) |
| [Pacotes]({{ site.baseurl }}/modulos/pacotes) | Pacotes pré-pagos de serviços ou de crédito em reais |
| [Estoque]({{ site.baseurl }}/modulos/estoque) | Produtos, entradas/saídas, alertas de estoque mínimo e baixa automática |
| [Equipe]({{ site.baseurl }}/modulos/equipe) | Profissionais, veterinários (CRMV) e acesso ao sistema |
| [Lojas]({{ site.baseurl }}/modulos/lojas) | Unidades da clínica e identidade visual (logo, dados de cabeçalho) |
| [Faturamento]({{ site.baseurl }}/modulos/faturamento) | Cobranças, despesas (parceladas/recorrentes), recibos e NFS-e |
| [Relatórios]({{ site.baseurl }}/modulos/relatorios) | Extrato, DRE e Livro Caixa |
| [Comunicação e Configurações]({{ site.baseurl }}/modulos/comunicacao) | Modelos de mensagens de WhatsApp e cabeçalho/rodapé das impressões |
| [Importação CSV]({{ site.baseurl }}/modulos/importacao) | Migração de clientes, pets, receitas e despesas (formato Sispet) |
| [Autenticação e Perfil]({{ site.baseurl }}/modulos/autenticacao) | Login, sessão, "lembrar-me" e troca de senha |

## Por onde começar

- **Vai instalar?** Leia [Instalação]({{ site.baseurl }}/instalacao).
- **Vai desenvolver?** Leia [Arquitetura]({{ site.baseurl }}/arquitetura) e [Desenvolvimento]({{ site.baseurl }}/desenvolvimento).
- **Vai integrar com a API?** Veja a [Referência da API]({{ site.baseurl }}/api).
- **Quer entender as regras entre módulos?** Veja [Fluxos integrados]({{ site.baseurl }}/fluxos).
