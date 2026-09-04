# Relatório de Evolução do Projeto — Petys

Este relatório documenta as principais evoluções e melhorias implementadas no sistema **Petys** — sistema de gestão veterinária, originalmente derivado do projeto Oralys (odontologia), migrado e rebranded para atender clínicas pet.

---
# Relatório de Evolução do Projeto — Petys
## DB: Consolida��o de Migrations (Julho 2026) [v3.4.31]

Consolida��o de todas as migra��es antigas do banco de dados em um �nico arquivo inicial utilizando um dump SQL estruturado, visando simplificar o hist�rico de migra��es e otimizar novas instala��es.

---

Este relatório documenta as principais evoluções e melhorias implementadas no sistema **Petys** — sistema de gestão veterinária, originalmente derivado do projeto Oralys (odontologia), migrado e rebranded para atender clínicas pet.

---

## UI: Conversão de Grids Nativos para Bootstrap (Julho 2026) [v3.4.30]

Substituição das classes de grid CSS customizadas pelo sistema de grid nativo do Bootstrap (`row` e `col-xx-x`) para melhorar a compatibilidade, responsividade e facilidade de manutenção de todas as telas.

### Ações Realizadas:
- **Telas Atualizadas**: `lojas.php`, `index.php`, `agendamentos.php`, `faturamento.php`, componentes do prontuário (`peso.php`, `calculadora.php`, `anamnese.php`, `vacinas.php`) e `cadastro.php`.
- **Classes Substituídas**: `.grid2`, `.grid3`, `.grid-2-1fr`, `.form-grid-p`, `.grid-main`, `.sum-grid`.
- **Limpeza de CSS**: Removidas as regras obsoletas referentes a essas classes antigas do `style.css`.
- **Exceções Mantidas**: Mantidas as estruturas `.cal-grid`, `.time-grid` e `.pat-grid` conforme regra de projeto para manter a integridade de componentes de calendário e grade especializados.

## Fix: Gráfico de Crescimento Repetindo (Julho 2026) [v3.4.30]
Correção de um bug visual onde o gráfico de crescimento do peso era exibido indevidamente em todas as abas do prontuário do pet.

### Ações Realizadas:
- **`app/Views/componentes/prontuario/tabs/peso.php`**: Removida uma `</div>` extra que estava fechando prematuramente o contêiner `tab-panel-peso`, fazendo com que o card do gráfico ficasse fora da aba e visível globalmente.
---
## Fix: Overflow Horizontal em Dispositivos Móveis (Julho 2026) [v3.4.29]

Correção do layout que causava rolagem horizontal indesejada (tela larga) em dispositivos móveis.

### Ações Realizadas:
- **Global CSS**: Adicionado `overflow-x: hidden;`, `max-width: 100%;` e `width: 100%;` nas tags `html` e `body` no arquivo `style.css` para garantir que elementos maiores não excedam os limites do viewport.
- **Tabs Responsivas**: Alterado o container `.tabs` com as propriedades `max-width: 100%;` e `overflow-x: auto;` permitindo que abas de navegação façam rolagem nativa quando ultrapassam a largura da tela sem quebrar o layout global.

---

## UI: Responsividade da Tabela de Pacotes (Julho 2026) [v3.4.28]

Correção de visualização mobile no módulo de Pacotes.

### Ações Realizadas:
- **Frontend (View)**: Removida a div wrapper de scroll horizontal e implementado um layout responsivo nativo via CSS no arquivo `pacotes.php`. No celular (max-width: 768px), a tabela `#tabela-pacotes` agora se transforma em uma visualização baseada em "cards" com `display: block`, utilizando atributos `data-label` nas células (`<td>`) para legibilidade.
- **UI (Ajustes)**: Ajustado o painel de estatísticas (`.summary-strip`) e os filtros (`.filters`) para usarem `flex-wrap` e ocuparem a largura total da tela no mobile, resolvendo completamente o problema de overflow e tela excessivamente larga.

---

## Fix: Dados do Tutor no Recibo Público (Julho 2026) [v3.4.27]

Correção na busca de dados do tutor para exibição correta no recibo público.

### Ações Realizadas:
- **Backend (Controller)**: Atualizado o controlador `Publico.php` para buscar os dados corretamente da tabela de clientes, utilizando os campos `nome` e `telefones` em vez de colunas obsoletas. Implementado fallback para capturar o `pet_id` a partir da cobrança caso a nota fiscal não o possua, melhorando a resiliência da exibição dos dados do tutor.

---

## Feature: Link Público de Recibos (Julho 2026) [v3.4.26]

Implementação de rota pública para verificação e visualização de recibos (comprovantes) sem necessidade de login no painel administrativo, garantindo maior transparência e praticidade para os tutores.

### Ações Realizadas:
- **Banco de Dados**: Criação e execução de migration para adicionar a coluna `hash` na tabela `fat_notas`. Um processo de "rollfill" preencheu automaticamente hashes MD5 únicos para todos os recibos e notas já existentes.
- **Backend (Service & Controller)**: 
  - Geração automática de hash ao registrar uma nova nota no `FatService`.
  - Integração da biblioteca `chillerlan/php-qrcode` para gerar QR Codes dinâmicos com a URL do recibo em Base64.
  - Criação do controlador `Publico.php` responsável por validar o ID e o Hash para exibir a página pública.
- **Frontend (UI & JS)**: 
  - Adição do botão de "Link Público" (🔗) na tabela de notas em Faturamento.
  - O botão de "Imprimir" foi atualizado para abrir diretamente a versão pública de impressão.
  - Remoção do código de barras fictício (`inv-barcode`) do preview.
  - Nova view `recibo_publico.php` otimizada para impressão em A4, com layout "Documento", contendo os dados da clínica, tutor, valor, descontos e o QR Code de autenticidade.

---

## Fix: Previsualização do Recibo (Julho 2026) [v3.4.25]

Correção do erro que impedia a atualização da pré-visualização de recibos ao clicar nas ações (Ver Detalhes, Imprimir) na tabela de Notas e Recibos.

### Ações Realizadas:
- **Frontend (View)**: Adicionado `event.stopPropagation()` e `type="button"` aos botões de ação na view `faturamento.php` para prevenir o borbulhamento de eventos (event bubbling) que estava causando a execução dupla e conflitante da função `showInvoice`.
- **Frontend (JS)**: Refatoração da função `showInvoice` no arquivo `faturamento.js` para adicionar resiliência, com checagem robusta da existência das propriedades no `dataset` e fallback gracefully para evitar interrupção da thread de execução do JavaScript em caso de dados faltantes ou mal formatados.

---

## Fix: Data de Vencimento no Faturamento (Julho 2026) [v3.4.24]

Correção do formato da data de vencimento na aba Lançamentos da página de Faturamento para evitar a exibição de '01/01/1970' quando a cobrança ou despesa não possui data de vencimento preenchida.

### Ações Realizadas:
- **Frontend (View)**: Adicionada verificação `!empty()` nos campos de `vencimento` (para cobranças) e `data_vencimento` (para despesas) em `faturamento.php` antes de usar `strtotime()`. Caso a data esteja vazia, é exibido um traço (`-`).

---

Resolução do erro `400 Bad Request` ao tentar atualizar o status de um agendamento na timeline da Agenda, onde o frontend enviava "Confirmado" (com C maiúsculo) e a API exigia caixa baixa.

### Ações Realizadas:
- **Correção no Controller**: Atualizada a regra de validação `in_list` no controller `Agenda::updateStatus` para aceitar as variações com primeira letra maiúscula (Pendente, Confirmado, Cancelado, Concluido).
- **Normalização de Dados**: O valor do status é agora forçado para lowercase (caixa baixa) no PHP via `strtolower` antes de ser passado para a camada de Service, mantendo a consistência do banco de dados sem alterar o frontend.

---

## Fix: Erro de Migration e Coluna servico_id (Julho 2026) [v3.4.21]

Resolução do erro `Unknown column 'servico_id' in 'field list'` que ocorria durante o faturamento devido à falha na migration `2026-04-25-000001_CreateServicoProdutosTable`.

### Ações Realizadas:
- **Correção de Migration**: Ajustada a ordem e a sintaxe para adicionar a chave estrangeira em tabelas existentes (`fat_cobrancas`) utilizando query raw para evitar conflitos no `CodeIgniter\Database\Forge`.
- **Execução de Migrations Pendentes**: O comando `php spark migrate` foi rodado com sucesso, garantindo que o esquema de banco de dados (`fat_cobrancas.servico_id` e a nova tabela `servico_produtos`) fosse aplicado corretamente.
- **Estabilização de Lançamentos**: Com a coluna criada, o `FatCobrancaModel` e as inserções via sistema (Faturamento Rápido, Agendamentos, Pacotes) voltaram a funcionar normalmente, sem erros 500 no banco.

---

## Migração Oralys → Petys (Abril 2026)

O projeto passou por uma migração completa de domínio: de clínica odontológica para clínica veterinária.

### Principais mudanças da migração:

- **Rebranding**: nome, logo e terminologia alterados de "Oralys / Odontologia" para "Petys / Veterinária".
- **Módulo Paciente → Pet**: entidade `paciente` renomeada para `pet` em models, repositories, services, controllers e views. Tabela `pacientes` substituída por `pets` com campos `pet_nome`, `pet_avatar`, `pet_id`.
- **Módulo Equipe**: adaptado de "dentistas" para "veterinários e auxiliares".
- **Módulo Serviços**: atualizado com serviços veterinários (Vacinação, Banho e Tosa, Consulta, etc.).
- **UI/Branding**: substituição de emojis 🦷 por 🐾, palette de cores ajustada, sidebar e header atualizados.

---

## Módulo de Agenda (Março 2026)

A aba de Agenda foi transformada de uma exibição estática em um módulo de gerenciamento dinâmico.

### Funcionalidades Implementadas:

- **Calendário Dinâmico**: Navegação por meses e seleção de dias com renderização instantânea via JavaScript.
- **Linha do Tempo (AJAX)**: Carregamento em tempo real de agendamentos para o dia selecionado, integrando dados de pets (nome, avatar, serviço).
- **Lista de Próximos Agendamentos**: Exibição automática dos compromissos das próximas 48 horas.
- **Estatísticas em Tempo Real**: Contadores dinâmicos para agendamentos de "Hoje" e "Pendentes".
- **Novo Agendamento**: Modal funcional com busca de pets por autocompletar (AJAX).

### Infraestrutura Backend:

- `AgendamentosRepository` — métodos `getByDate`, `getUpcoming`, `getStats`.
- `AgendaService` — camada de lógica de negócio.
- `Agenda` (Controller) — endpoints RESTful para o frontend.

---

## Módulo de Pets (Abril 2026)

Migração completa do módulo de Pacientes para Pets.

### Funcionalidades:

- Cadastro completo com wizard multi-etapas (dados do pet + tutor).
- Filtros avançados por serviço, status e busca por nome.
- Visualização em Grade (cards com avatar) e Lista.
- Integração com prontuários e faturamento.

### Infraestrutura Backend:

- `PetsModel` / `PetsRepository` / `PetsService` — CRUD completo.
- `Pets` (Controller) — rota `mixCreate` para criação combinada pet + tutor.

---

## Módulo de Prontuários (Março–Abril 2026)

Prontuário digital veterinário fundamentado no padrão MVCRS.

### Funcionalidades Implementadas:

- **Odontograma Interativo**: Gráfico dentário dinâmico com states em JSON para registrar saúde bucal de pets.
- **Anamnese Completa**: Formulários de dados clínicos do pet, queixas e histórico sistêmico.
- **Evolução Clínica Cronológica**: Linha do tempo de procedimentos com autoria do veterinário.
- **Módulo de Imagens e Exames**: Substituiu a antiga "Galeria de Radiografias". Suporte para upload de múltiplos tipos de imagem (exames, fotos clínicas) com armazenamento seguro em `writable/imagens/{pet_id}` e entrega via stream no controller.
- **Histórico Consolidado**: Integração de agendamentos e faturamento em visão 360°.

### Infraestrutura Backend:

- `OdontogramasRepository`, `EvolucoesRepository`, `ImagensRepository`.
- `ProntuariosService` — coordinator central.
- Segurança: Imagens armazenadas fora do diretório público (`writable`), acessíveis apenas via rota autenticada.
- Compatibilidade: tipos `JSON` → `TEXT` para suporte universal MySQL/MariaDB.

---

## Módulo de Faturamento (Março–Abril 2026)

Dashboard financeiro completo com lançamentos de receitas e despesas.

### Funcionalidades:

- **KPIs do Mês**: Receitas, Despesas, Saldo, A Receber, Vencidos.
- **Cobranças (Receitas)**: Registro de atendimentos com valor, forma de pagamento, parcelas, vencimento e status.
- **Despesas**: Controle de saídas por categoria, fornecedor e status de pagamento.
- **Recebíveis**: Listagem de cobranças pendentes e atrasadas com botão de lembrete.
- **Notas e Recibos**: Emissão e visualização de notas fiscais e recibos com preview imprimível.
- **Gráficos**: Receita vs. Despesas (linha), Receita por Serviço (donut), Formas de Pagamento (barras), Top Pets.

### Infraestrutura Backend:

- `FatCobrancaModel` / `FatCobrancaRepository`
- `FatDespesaModel` / `FatDespesaRepository`
- `FatNotaModel` / `FatNotaRepository`
- `FatService` — lógica de dashboard, KPIs e criação de lançamentos.
- `Faturamento` (Controller) — API para criação de cobranças, despesas e notas.

---

## Módulo de Relatórios Financeiros (Abril 2026)

Novo módulo dedicado a relatórios gerenciais, acessível em `/relatorios`.

### Relatórios Disponíveis:

#### 📄 Extrato

- Filtros: período (de/até), tipo (entradas/saídas/todos) e status.
- Tabela cronológica unificada de cobranças e despesas com indicador visual de natureza (▲ Entrada / ▼ Saída).
- Totalizadores: Total Entradas, Total Saídas, Saldo do Período, Nº de Lançamentos.
- Export CSV (BOM UTF-8 compatível com Excel) e Print otimizado.

#### 📊 DRE — Demonstração do Resultado do Exercício

- Filtros: período.
- KPIs com variação percentual vs. mês anterior (▲/▼).
- Estrutura vertical formal: Receita Bruta → Deduções → Receita Líquida → Despesas por Categoria → **Resultado Líquido**.
- Margem líquida calculada automaticamente.
- Gráficos de progresso por serviço e por categoria de despesa.
- Critério: **Regime de Caixa** (apenas lançamentos `status = 'Pago'`).

#### 📒 Livro Caixa

- Filtros: período.
- Saldo Inicial acumulado (calculado a partir de todos os lançamentos anteriores ao período).
- Tabela com colunas: Data | Histórico | Forma Pgto | Entrada | Saída | **Saldo Acumulado**.
- Saldo Final destacado com indicador positivo/negativo.
- Export CSV e Print.

### Infraestrutura Backend:

- `RelatoriosRepository` — queries por período: `getCobrancasExtrato`, `getDespesasExtrato`, `getReceitasBrutasDRE`, `getDespesasPorCategoriaDRE`, `getEntradasLivroCaixa`, `getSaidasLivroCaixa`, `getSaldoAnterior`.
- `RelatoriosService` — lógica de negócio: resolução de período, cálculo de DRE, saldo acumulado do Livro Caixa, variação mês a mês.
- `Relatorios` (Controller) — 4 actions: `index` (redirect), `extrato`, `dre`, `livroCaixa`.
- Rotas: `GET /relatorios`, `/relatorios/extrato`, `/relatorios/dre`, `/relatorios/livro-caixa`.

---

## Módulo de Equipe (Abril 2026)

- Cadastro, edição e exclusão de membros veterinários e auxiliares.
- CRUD completo via modal com API REST.
- `EquipeModel` / `EquipeRepository` / `EquipeService` / `Equipe` (Controller).

---

## Módulo de Serviços (Abril 2026)

- Gerenciamento de serviços veterinários oferecidos pela clínica.
- CRUD via modal com listagem em cards.
- CSS extraído de inline para `style.css` (refatoração de manutenibilidade).
- `ServicosModel` / `ServicosRepository` / `ServicosService` / `Servicos` (Controller).

---

## Módulo de Lojas (Abril 2026)

Implementação do gerenciamento de unidades/lojas da clínica, permitindo o controle de diferentes filiais em um único sistema.

### Funcionalidades:

- CRUD completo de lojas (Nome, CNPJ, Email, Telefone, Endereço, etc.).
- Interface dinâmica com AJAX para criação, edição e exclusão sem refresh.
- Integração com a sidebar através de um novo padrão de **Submenu** sob o item "Configurações".

### Infraestrutura Backend:

- `LojasModel` / `LojasRepository` / `LojasService` — Segue o padrão MVCRS.
- `Lojas` (Controller) — Endpoints API RESTful e renderização de view.
- Rotas: `GET /lojas`, `POST /api/lojas/create`, `POST /api/lojas/update/(:num)`, etc.

---

## Padrão Arquitetural

Todo o sistema segue o padrão **MVCRS** (Model → View → Controller → Repository → Service):

```
Controller (HTTP) → Service (regras de negócio) → Repository (queries) → Model (CI4 ORM)
```

- Controllers recebem requisições e delegam 100% para Services.
- Services orquestram Repositories e aplicam regras de negócio.
- Repositories encapsulam queries e expõem métodos semânticos.
- Models definem campos, validações e configuração do ORM.

---

## Ajustes de UI (Abril 2026)

- **Centralização da Logo**: Logo no sidebar agora centralizada para melhor equilíbrio visual em desktop e mobile.
- **Refatoração de Scripts (Estoque)**: Remoção de scripts inline na view `estoque/index.php` e migração para `script.js`.
- **Relatórios & Serviços (A11y)**: Refatoração completa das views `relatorios.php` e `servicos.php`. Extração de ~250 linhas de CSS para o arquivo global, eliminando estilos inline em DRE, Extrato e Livro Caixa. Adição de atributos ARIA em botões de exportação, impressão e filtragem, garantindo transparência técnica nessas ferramentas críticas de gestão.
- **Skill Palette (Concluída)**: Finalização da aplicação da skill `palette` em todos os módulos principais do sistema (Pets, Agenda, Lojas, Relatórios, Serviços, Faturamento, Estoque, Prontuários).
- **Refatoração de View (Prontuários)**: Remoção completa do bloco `<style>` em `prontuarios/index.php` e substituição de atributos `style="..."` por classes utilitárias semânticas.
- **Módulo de Imagens (Rebranding & Segurança)**: Renomeação global de `radiografias` para `imagens` para suportar diversos tipos de exames. Migração do armazenamento para o diretório `writable` para maior segurança e controle de acesso, com implementação de rota de visualização dedicada.
- **Componentização do Modal de Agendamento**: Extração do modal de agendamento de `pets.php` e `agendamentos.php` para um componente reutilizável em `app/Views/componentes/modal_agendamento.php`. Isso eliminou a duplicação de código e centralizou a lógica de agendamento.
- **Serviços Dinâmicos no Agendamento**: O modal de agendamento agora consome dados reais da tabela `servicos` via `ServicosService`, substituindo opções hardcoded.
- **Preenchimento Automático (UX)**: Implementação da função `openAgendarModal(id, nome)` que permite preencher automaticamente os dados do pet ao clicar em "Agendar" diretamente de um card ou da lista de pets, melhorando significativamente a agilidade operacional.
- **Edição de Agendamentos na Timeline**: Transformação dos blocos de agendamento em links interativos. Agora, clicar em qualquer agendamento na timeline abre o modal pré-preenchido, permitindo a edição rápida de horários, serviços, observações e status (pendente, confirmado, cancelado, concluído).
- **Melhoria Visual (Agenda)**: Realce visual do botão de confirmação (✅) para agendamentos que já possuem status como "Confirmado", melhorando o feedback imediato para o recepcionista.

---

---

## Melhorias no Módulo de Faturamento (Abril 2026)

- **Internacionalização/Tradução**: Tradução de meses e datas para português em todo o dashboard financeiro.
- **Datas Dinâmicas**: Substituição de strings estáticas (ex: "Março 2026") por lógica PHP dinâmica que reflete o mês e ano atuais.
- **Refatoração de CSS (Skill Palette)**: Extração de estilos inline (mais de 50 linhas) para o arquivo global `style.css`, limpando a visualização `faturamento.php` e centralizando o design system.

## Configuração Global de Marca (Abril 2026)

- **Variáveis de Ambiente (.env)**: Centralização dos dados da clínica (`NomeClinica`, `SloganClinica`, `EnderecoClinica`, `CidadeClinica`, `CnpjClinica`) para facilitar o rebranding e personalização sem tocar no código das views.
- **Injeção Global de Views**: Refatoração do `BaseController` para injetar automaticamente essas variáveis em todos os renderizadores de view do CodeIgniter 4. Isso permite o uso direto de `$nome_clinica`, `$endereco`, etc., em qualquer arquivo `.php` de visualização do projeto.
- **Limpeza de .env**: Remoção de trechos de código acidentais e organização do arquivo de configuração.

---

## Centralização e Unificação de UI (Abril 2026)

Refatoração estrutural dos módulos de **Pets** e **Agendamentos** para eliminar a duplicação de código e padronizar o design system.

### Principais Ações:

- **Centralização de CSS**: Migração de ~200 linhas de estilos inline de agenda, calendário e timeline das views para o arquivo global `style.css`.
- **Modularização de JavaScript**: Consolidação de toda a lógica de negócio do frontend (Agenda, Wizard de Cadastro, Filtros, Busca AJAX de Pets, Faturamento Direto) no arquivo global `script.js`.
- **Limpeza de Views**: Redução drástica do tamanho dos arquivos de visualização (`pets.php` e `agendamentos.php`), que agora funcionam apenas como templates HTML e inicialização de dados PHP.
- **Componentização**: O modal de agendamento agora é um componente 100% reutilizável, com lógica centralizada e suporte a preenchimento automático.
- **Deep-Linking**: Implementação de suporte a parâmetros de URL (`?tab=agenda`, `?tab=cadastro`) para navegação direta entre estados do sistema.

---

## Unificação de Migrations e Estruturação de Clientes (Abril 2026)

Consolidação completa do esquema de banco de dados para as entidades `pets` e `clientes`, seguindo as melhores práticas de normalização.

### Ações Realizadas:

- **Unificação de Migrations**: Todas as alterações incrementais da tabela `pets` (incluindo o novo status `Suspenso`) foram consolidadas na migration inicial `CreatePetsTables`.
- **Criação da Tabela `clientes`**: Implementação de uma tabela dedicada para tutores/clientes com 18 campos detalhados (Nome, CPF/CNPJ, Endereço completo, Contatos múltiplos, etc.), substituindo os campos de tutor anteriormente embutidos na tabela `pets`.
- **Relacionamento Relacional**: Estabelecimento de chave estrangeira (`cliente_id`) na tabela `pets` apontando para `clientes`, permitindo que um cliente possua múltiplos pets de forma organizada.
- **Refatoração de Models**:
  - `PetsModel`: Atualizado para suportar `cliente_id` e remoção de campos redundantes de tutor. Ativação de `useTimestamps`.
  - `ClientesModel`: Novo model criado com validações de unicidade (CPF) e suporte a Soft Deletes.
- **Limpeza de Migrations**: Remoção de arquivos de migração redundantes que apenas alteravam colunas, mantendo o histórico de banco de dados limpo e fácil de manter.

---

## Lembretes por WhatsApp Customizáveis (Abril 2026)

Implementação de um sistema de comunicação direta com o tutor via WhatsApp, integrado aos módulos de Agenda e Faturamento.

### Ações Realizadas:

- **Infraestrutura de Configuração**: Criação da tabela `configs` e do `ConfigModel` para armazenamento persistente de metadados e templates de mensagem.
- **Service Layer de Comunicação**: Desenvolvimento do `ComunicacaoService`, responsável por resolver placeholders (`{tutor}`, `{pet}`, `{servico}`) e formatar links `wa.me` com tratamento automático de DDI e limpeza de caracteres não numéricos em telefones.
- **Interface de Personalização**: Adição de um editor de modelos de mensagem na página de **Lojas**, permitindo que a clínica customize o tom de voz dos lembretes de agendamento e cobrança.
- **Integração na Agenda**: Inclusão de gatilhos de WhatsApp (💬) na timeline da agenda para contato rápido com o tutor sobre compromissos confirmados ou pendentes.
- **Automação de Cobrança**: Ativação de botões de lembrete na aba de **Recebíveis**, facilitando a régua de cobrança de valores em aberto.
- **Padronização de API**: Criação de endpoints REST no `ComunicacaoController` para geração dinâmica de links de conversa, desacoplando a lógica de negócio da visualização.

---

## Unificação Total das Migrations Core (Abril 2026)

Consolidação definitiva do esquema de banco de dados para as entidades fundamentais (`clientes`, `pets` e `agendamentos`) na migration inicial.

### Ações Realizadas:

- **Consolidação de Agendamentos**: A tabela `agendamentos` agora nasce com todos os campos necessários (`age_status`, `age_veterinario`, `age_faturado`) e índices de performance (`idx_age_data_hora`, `idx_age_status`) diretamente em `CreatePetsTables`.
- **Rebranding Nativo**: O campo anteriormente conhecido como `age_dentista` foi renomeado para `age_veterinario` na estrutura inicial, eliminando passos de refatoração posteriores.
- **Limpeza de Histórico**: Remoção de 3 migrations incrementais que alteravam a tabela `agendamentos`, mantendo o diretório `Database/Migrations` focado e organizado.
- **Integridade Referencial**: Ajuste na migration `RefactorEquipeToVet` para evitar redundância na renomeação de colunas já unificadas.

## Módulo de Importação de Clientes (Abril 2026)

Implementação do módulo de importação em massa de clientes via arquivos **CSV**, permitindo migração rápida de dados externos.

### Ações Realizadas:

- **Banco de Dados**: Criação da migration `AddExtraFieldsToClientes` para suportar `cnpj`, `vendedor`, `origem_cliente` e `ultima_compra`. Adição posterior do campo `exportacao` em `clientes` e `pets` para rastreabilidade de migrações externas.
- **Backend (MVCRS)**:
  - `ClientesImportService`: Lógica nativa (`fgetcsv`) para parsing de arquivos, mapeamento flexível de cabeçalhos (incluindo o novo campo `Exportação`) e conversão de datas.
  - `ClientesImport`: Controller com ações de visualização e upload assíncrono.
- **Tratamento de Duplicados**: Sistema ignora automaticamente CPFs já existentes e gera um relatório detalhado (Nome + ID original) no final do processo.
- **UI/UX (Palette)**: Nova interface de importação com drag-and-drop, barra de progresso e sumário de resultados integrada ao submenu "Configurações".

## Módulo de Importação de Pets (Abril 2026)

Implementação da importação em massa de pets via CSV, com vínculo inteligente aos tutores existentes através do campo de rastreabilidade `exportacao`.

### Ações Realizadas:

- **Banco de Dados**: Criação de campos `pet_porte` e `pet_caracteristicas` na tabela `pets` via migration.
- **Backend (MVCRS)**:
  - `PetsImportService`: Lógica de busca de tutor via `exportacao` e verificação de duplicidade (mesmo nome por tutor).
  - `PetsImport`: Controller para gestão de uploads assíncronos.
- **UI/UX (Palette)**: Interface dedicada para Pets com relatório de ignorados por tutor não encontrado ou duplicidade.

---

## Módulo de Importação de Receitas (Abril 2026)

Implementação do módulo de importação em massa de receitas via arquivos **CSV do Sispet**, integrando o faturamento ao histórico dos pets.

### Ações Realizadas:

- **Backend (MVCRS)**:
  - `ReceitasImportService`: Lógica de parsing com delimitador `;`, identificação de `pet_id` via busca combinada (Nome Pet + Nome Cliente) e sistema de deduplicação básica.
  - **Dicionários de Mapeamento**: Implementação de lógica de conversão de termos ("Receita c/ venda de serviços" → "Banho/Tosa" e "pix/ transf./ depós." → "pix").
  - `ReceitasImport`: Controller para gestão de uploads financeiros.
- **Repositório**: Adicionado método `findByName` em `ClientesRepository` para suporte à busca durante a importação.
- **UI/UX (Palette)**: Interface dedicada para importação de receitas com drag-and-drop, barra de progresso e relatório de ignorados integrada ao submenu "Configurações".

---

## Correções de Bugs (Abril 2026)

- **Fix: Carregamento de Serviços na Agenda**: Corrigido erro onde os serviços apareciam como "0" na timeline. O problema era causado por um mismatch de tipos (envio de nome string para campo INT) e falta de JOIN no repositório. O modal agora envia o ID correto e o repositório recupera o nome via SQL.
- **Fix: Botão Ver em Pets**: Corrigido link do botão "Ver" e dos cards na listagem de pets que não abriam o prontuário. Agora redirecionam corretamente para a rota `/prontuarios/{id}`.
- **Fix: Undefined array key "pet_resp_nome" em Prontuarios**: Corrigido erro que impedia a exibição da lista de prontuários quando nenhum termo de busca era fornecido. O problema era causado pela falta de `JOIN` com a tabela `clientes` na listagem geral. Implementado `findAllWithClient()` no `PetsRepository` e atualizado o `ProntuariosService` para garantir que o nome do tutor esteja sempre disponível na view. (Corrigido após regressão acidental).
- **Fix: Carregamento de Dados do Prontuário**: Corrigido erro de "recarregamento infinito" ao abrir o prontuário. O problema era causado pela falta de JOIN com a tabela `clientes` na busca por ID do pet, o que impedia o carregamento do nome/telefone do tutor na view. Sobrescrito `findById` no `PetsRepository` e implementado `findByPet` no `AgendamentosRepository` com JOIN de serviços.

## Melhorias de UI/UX (Abril 2026)

- **Edição de Avatar e Dados do Pet no Prontuário**: Incluída a capacidade de gerenciar o form completo de "Dados do Pet" na aba de Anamnese do Prontuário. O avatar do pet pode ser modificado com opções de ícones ou pelo upload de uma **Foto Real**, os dados são salvos pela API de Prontuários (que reflete o update direto na tabela de pets com upload seguro para `writable/avatar/`) e há feedback na tela por via de um Toast com reload automático da UI para atualizar o cabeçalho ("Pet Header") e também as exibições em grade/lista.
- **Hover Responsivo no Avatar (Timeline)**: Adicionado um efeito no módulo de Agendamentos (Timeline) que exibe uma versão ampliada da foto (`scale 2.5x` no CSS) e ganha destaque por sobre o calendário inteiro assim que o usuário passar o mouse por cima do rostinho/ícone do pet, facilitando a identificação visual rápida de cada compromisso.
- **Busca de Pets Aprimorada (Agendamento)**: Atualizada a busca de pets no modal de agendamento para exibir, além do nome do pet, o nome do tutor e o telefone. Isso resolve a ambiguidade em casos de pets com nomes repetidos e agiliza a identificação do cliente correto.

---

## Módulo de Pacotes (Abril 2026)

Implementação do gerenciamento de pacotes de serviços e créditos pré-pagos para pets.

### Funcionalidades:

- **Venda de Pacotes**: Cadastro de pacotes por quantidade de serviços (ex: 10 Banhos) ou por crédito (ex: R$ 500,00).
- **Cobrança Automática**: A criação de um pacote gera automaticamente uma cobrança pendente no financeiro.
- **Ativação por Pagamento**: O pacote é ativado assim que a cobrança correspondente é marcada como "Pago".
- **Consumo Inteligente**: Integração com o faturamento para permitir o uso de saldo/itens de pacotes ativos como forma de pagamento.
- **Dashboard de Pacotes**: Visualização de saldos, itens restantes e validade.

### Refatoração e Correções:

- **Correção de Chave Primária**: Resolvido o erro `Unknown column 'p.pac_id'` no `PacotesRepository`.
- **Historização de Uso**: Implementada a tabela `pacote_uso` e o log de consumo no `PacoteService`.
- **Integração Financeira**: Repositório agora vincula o pacote à sua cobrança original (`fat_cobrancas`), permitindo exibir o valor real e ativar o pacote via pagamento.
- **UI Progressiva**: Modal de detalhes agora exibe botões dinâmicos de pagamento para itens pendentes, automatizando a ativação.
- **Detalhamento Financeiro**: Inclusão de campos de Valor Bruto, Desconto e Valor Líquido.
- **Auditoria de Pagamento**: Exibição da data real em que o pacote foi pago e ativado.
- **Correção de NaN/Undefined**: Ajustados os nomes de campos no JavaScript (`quantidade_total`, `valor`) para refletir o schema do banco.

### Infraestrutura Backend:

- `PacotesModel` / `PacotesRepository` / `PacotesService` — Gestão de saldo e histórico.
- `PacoteUsoModel` / `PacoteUsoRepository` — Registro de auditoria de consumo.
- `Pacotes` (Controller) — Endpoints para criação, listagem e detalhes.

### Melhorias de UI / UX / Fixes:

- **Busca Dinâmica de Pets**: Substituição da seleção estática (`<select>`) no modal de "Novo Pacote" por uma pesquisa dinâmica com autocompletar. A lógica foi generalizada no `script.js` para suportar múltiplos prefixos (`ag-` para agenda e `pac-` para pacotes), permitindo a reutilização do componente de busca em qualquer parte do sistema sem conflitos de IDs.
- **Fix: Rota de Atualização de Cobrança**: Adicionada a rota `api/faturamento/cobranca/update/(:num)` e o respectivo método no controller `Faturamento`, corrigindo o erro 404 ao tentar marcar um pacote como pago no dashboard de pacotes.

---

## Customização Visual Shield (Abril 2026)

Concluí a personalização completa das interfaces de autenticação do CodeIgniter Shield, migrando e adaptando as views padrão para o ecossistema visual do **Petys**.

### Funcionalidades Implementadas:

- **Layout de Autenticação Exclusivo**: Criação de um layout base (`shield/layout.php`) sem sidebar, focado na experiência de login e registro, com cartões premium, fontes Nunito/Playfair e fundo estilizado.
- **Formulários Premium**: Redesign completo de Login, Registro e recuperação de senha (Magic Link) utilizando os componentes e variáveis do design system do projeto (`--pink`, `--mint`, `--shadow`, etc.).
- **Fluxos de Segurança 2FA e Ativação**: Telas de verificação de código com inputs de destaque para melhor usabilidade.
- **Templates de Email Personalizados**: Atualização dos templates de email (2FA, Activation, Magic Link) com as cores e tipografia da marca.

### Infraestrutura Backend:

- **Configuração de Views**: Atualização de `Config\Auth.php` para apontar todas as rotas de view do Shield para os novos arquivos customizados em `app/Views/shield/`.

---

---

## UI: Dropdown no User-Card e Alteração de Senha (Abril 2026)

Implementação de menu interativo no rodapé da sidebar para gestão básica de conta e logout.

### Funcionalidades Implementadas:

**Menu Dropdown:**

- `user-card` agora é clicável e exibe um menu com animação fade-up.
- Opções: **Alterar Senha** (abre modal) e **Logout** (redireciona para `/logout`).
- Fechamento automático ao clicar fora do componente.

**Alteração de Senha:**

- Modal premium com campos para Senha Atual e Nova Senha.
- Validação no backend via Shield: verifica se a senha atual está correta antes de permitir a alteração.
- Feedback via Toast (sucesso) ou mensagem de erro inline no modal.

### Infraestrutura Backend:

- `Perfil` (Controller) — endpoint `POST api/perfil/alterar-senha`.
- Integração com `CodeIgniter\Shield\Auth` para verificação de credenciais e persistência de nova senha.

---

- [ ] No browser pages are currently open.

---

## Melhorias de UX no Faturamento Rápido (Abril 2026)

Implementação de uma interface premium para o modal de faturamento rápido, focada em agilidade e suporte a novas formas de pagamento.

### Ações Realizadas:

- **Seletor de Pagamento Premium**: Substituição do `<select>` convencional por um grid de botões interativos com ícones (Bootstrap Icons), facilitando o uso em dispositivos touch e melhorando a percepção de valor do sistema.
- **Integração com Pacotes**: Inclusão da opção "Pacote" como forma de pagamento direta no faturamento da agenda.
- **Seleção Dinâmica de Pacotes**: Implementação de lógica JavaScript que identifica o pet, busca seus pacotes ativos via API e permite a seleção do pacote desejado para abatimento do valor, garantindo integridade no controle de saldos.
- **Validação de Fluxo**: Adicionada validação no frontend para garantir que um pacote seja selecionado antes de confirmar o faturamento via Pacote.
- **Padronização de API**: Criação da rota `GET /api/pacotes/disponiveis/(:num)` para suporte ao carregamento assíncrono de dados no modal.

### Backend:

- Rota: `GET /api/pacotes/disponiveis/(:num)` -> `Pacotes::getDisponiveis/$1`
- Controller: `Pacotes`
- Service: `PacoteService::getDisponiveisPorPet()`
- `FatService::createCobranca()`: Já preparado para lidar com `forma_pagamento = 'Pacote'` e `pacote_id`.

---

## Correção no Faturamento de Agendamentos (Abril 2026)

- **Fix: Dados de Consumo de Pacote**: Corrigido erro no `AgendaService` onde o sistema tentava acessar `servico_id` e `valor` a partir do objeto de agendamento de forma incorreta. Agora, o sistema utiliza o campo `age_servico` (conforme definido no schema) e o `valor` preenchido no formulário de faturamento.
- **Resiliência de Banco de Dados**: Implementada normalização para o campo `pacote_id`. Caso o campo chegue vazio da view (ex: quando não se usa pacote), ele é convertido para `null`, evitando falhas de integridade referencial (Foreign Key constraint) na tabela de cobranças.

---

## Correção de Backend e UI: Ordenação de Pets (Abril 2026)

Implementação da funcionalidade de ordenação dinâmica no módulo de Pets, que anteriormente possuía as opções na interface mas sem lógica associada.

### Ações Realizadas:

- **Backend (Repositorio)**: Atualização do `PetsRepository::getAllWithLastAppointment()` para incluir a contagem total de agendamentos por pet (`total_consultas`), permitindo a ordenação por popularidade/frequência.
- **Frontend (View)**:
  - Adição de atributos de dados (`data-id`, `data-nome`, `data-date`, `data-consultas`) nos elementos `.pat-item` das visualizações em Grade e Lista.
  - Configuração do seletor de ordenação (`#f-sort`) com valores semânticos e gatilho `onchange`.
- **JavaScript Global**:
  - Refatoração da função `filterAll()` no `script.js` para integrar lógica de ordenação estável (stable sort).
  - Suporte a múltiplos critérios: **Nome A-Z**, **Recentes** (pelo ID), **Mais Consultas** (pela contagem no banco) e **Última Visita** (pela data do último agendamento).
  - Implementação de re-renderização DOM eficiente para manter a ordem em ambas as visualizações (Grid e Table) sem recarregamento de página.
  - Preservação do card "Cadastrar Novo Pet" sempre ao final da grade.

---

_Relatório gerado e mantido pelo assistente Antigravity — última atualização: 05 de Abril de 2026 às 17:45._

---

## Edição de Despesas e Upload de Comprovante (Abril 2026)

Funcionalidade de edição inline de despesas diretamente na `tab-despesas` do módulo de Faturamento, com suporte a upload de documentos comprobatórios.

### Funcionalidades Implementadas:

**Tab-despesas — Linhas Clicáveis:**

- Cada linha da tabela de despesas passou a ser um link interativo (cursor pointer + hover visual).
- Ao clicar, o modal de despesa abre pré-preenchido com todos os dados da linha selecionada (descrição, categoria, fornecedor, valor, data, forma de pagamento, status).
- A coluna `Comprovante` exibe um ícone 📎 com link seguro para visualização quando já existe arquivo uploadado.

**Modal Nova/Editar Despesa (unificado):**

- Título e botão dinâmicos (`Nova Despesa` ou `✏️ Editar Despesa #XXXX`).
- Campo `Status` adicionado (Pendente / Pago).
- Categorias expandidas para cobrir todos os tipos importados do Sispet (Sistema operacional, Impostos, Saúde/Vet, Aluguel, etc.).
- Área de upload de comprovante com drag-and-drop, nome do arquivo exibido após seleção e feedback visual.
- Link "Ver comprovante atual" exibido automaticamente quando existe arquivo já salvo.

**Backend:**

- `FatService::updateDespesa()` — atualiza campos permitidos com whitelist de segurança.
- `FatService::uploadComprovante()` — salva arquivo em `writable/despesas/{id}/` com nome único datado e persiste o caminho no campo `comprovante`.
- `Faturamento::updateDespesa()` — endpoint `POST /api/faturamento/despesa/update/{id}`.
- `Faturamento::uploadComprovantesDespesa()` — endpoint `POST /api/faturamento/despesa/upload-comprovante/{id}`.
- `Faturamento::viewComprovante()` — rota segura `GET /faturamento/comprovante/{id}/{filename}` que serve o arquivo fora do diretório public.

**CSS Global (`style.css`):**

- `.border-dashed-box` e `.des-upload-area` — área de upload estilizada com hover de destaque.
- `.des-upload-status` — feedback de status do upload.
- `tr.cp:hover td` — highlight de linha na tabela de despesas.
- `.hide` e `.mt-5` — utilitários globais.

---

## Módulo de Importação de Despesas (Abril 2026)

Implementação do módulo de importação em massa de despesas via arquivos **CSV do Sispet**, espelhando a arquitetura do módulo de Receitas.

### CSV Suportado (cabeçalho Sispet):

`Situação;Conta;Tipo documento;Vencimento;Competência;Pagamento;Contato;Razão;CPF;CNPJ;Fones;Emails;Valor documento;Acréscimo;Desconto;Valor pago;Detalhes;Origem;Incluído por;Unidade;Planos de contas I;Planos de contas II;Planos de contas III;Tipo de despesa`

### Mapeamento de Colunas:

| CSV (Sispet)           | fat_despesas    |
| ---------------------- | --------------- |
| Situação               | status          |
| Tipo documento         | forma_pagamento |
| Vencimento / Pagamento | data_vencimento |
| Razão                  | fornecedor      |
| Valor documento        | valor           |
| Detalhes               | descricao       |
| Planos de contas III   | categoria       |

### Regras de Negócio:

- **Status**: "Pago" → `Pago`; demais → `Pendente`.
- **Data**: quando `status = Pago`, usa a data de **Pagamento** como `data_vencimento`; caso contrário, usa **Vencimento**.
- **Categoria**: prioridade `Planos de contas III` → `Planos de contas II` → `Tipo de despesa` → `Geral`.
- **Deduplicação**: registros com mesmo fornecedor, valor e data são ignorados automaticamente.
- **Valores zerados**: linhas com `Valor documento <= 0` são descartadas com motivo registrado no relatório.

### Backend (MVCRS):

- `DespesasImportService`: parsing nativo com `fgetcsv`, mapeamento flexível de colunas e deduplicação.
- `DespesasImport`: Controller dedicado com ações `index` (view) e `upload` (API AJAX).

### Rotas:

- `GET  /importar-despesas` → Página de importação.
- `POST /importar-despesas/upload` → Endpoint de upload.

### UI/UX:

- Drag-and-drop com highlight visual coerente com a Palette do sistema.
- **Painel de mapeamento de colunas** exibido antes do upload, informando ao usuário quais campos serão processados.
- Barra de progresso animada durante o processo.
- Sumário final com estatísticas (Total / Importados / Ignorados) e tabela detalhada de itens ignorados.
- Link "💸 Importar Despesas" adicionado ao submenu "Configurações" na sidebar.
- Submenu "Configurações" agora expande automaticamente para todas as rotas de importação (clientes, pets, receitas e despesas).

---

## Estabilização de Segurança — CSRF 403 Fix (Abril 2026)

Resolução definitiva do erro `CodeIgniter\Security\Exceptions\SecurityException #403` que afetava operações AJAX e envios sequenciais.

### Diagnóstico Técnico:

O erro era causado pela regeneração automática do token CSRF a cada requisição (`$regenerate = true`). Em cenários de AJAX rápido ou chamadas concorrentes, o token enviado pelo cliente tornava-se inválido antes mesmo do processamento da requisição seguinte, gerando a exceção 403 (Forbidden).

### Melhorias Implementadas:

#### 🔒 Backend (Security Config):

- Arquivo: `app/Config/Security.php`.
- Configuração: `$regenerate` alterado para `false`. O sistema agora mantém o mesmo token durante a sessão, eliminando condições de corrida (race conditions) em módulos dinâmicos.

#### 🏗️ Frontend (Infraestrutura):

- **Layout Base**: Injeção de Meta Tags CSRF (`csrf-header` e `csrf-token`) no `<head>` do documento em `app/Views/template/layout.php`.
- **Helpers Globais**: Adição das funções `getCsrfToken()` e `getCsrfTokenName()` em `public/dist/js/script.js` para recuperação centralizada dos tokens de segurança via JavaScript.

#### 🚀 Atualização de Módulos (AJAX):

Implementação da inclusão obrigatória do token CSRF nos headers (`X-CSRF-TOKEN`) e no corpo da requisição (`FormData` ou string) em todos os módulos que utilizam `fetch`:

- **Agenda**: Atualização de status e faturamento rápido.
- **Perfil**: Alteração de senha.
- **Pets/Tutores**: Wizard de cadastro e importação CSV.
- **Financeiro**: Lançamento de cobranças, despesas, notas e importações.
- **Equipe e Lojas**: Cadastro, edição e exclusão via API.
- **Serviços e Pacotes**: Gerenciamento completo via AJAX.

### Impacto:

Acesso estabilizado a todas as funcionalidades administrativas e clínicas do sistema, sem interrupções por falha de segurança em rotas protegidas.

---

## Correção de Tipagem — Dashboard TypeError (Abril 2026) [v2.7.1]

Resolução do erro `TypeError` no Dashboard principal (`Home::index`) que impedia o carregamento total dos KPIs financeiros e de agendamento.

### Mudanças:

- **Flexibilidade de Tipagem**: Atualização dos métodos `getTotalMonth`, `getServiceStats` e `getDaysWithAppts` nos repositórios para aceitarem `int|string` como parâmetros de mês e ano.
- **Formatação de Datas**: Implementação de preenchimento automático (padding) para garantir que meses passados como inteiros (ex: `4`) sejam convertidos corretamente para o formato esperado pelo banco de dados (ex: `04`), mantendo a integridade das consultas SQL.
- **Estabilidade do Dashboard**: O controlador `Home` agora pode processar KPIs financeiros e estatísticas de agendamentos sem interrupções por incompatibilidade de tipos sob `strict_types=1`.

---

## Correção de Inicialização — Agenda ReferenceError (Abril 2026) [v2.7.2]

Resolução do erro `ReferenceError: initAgendaPage is not defined` que impedia o carregamento da agenda.

### Mudanças:

- **Ordem de Carregamento**: Remoção do atributo `defer` da inclusão do `script.js` no `layout.php`. Isso garante que as funções globais estejam disponíveis para scripts inline nas views no momento do registro de eventos `DOMContentLoaded`.
- **Estabilidade da Agenda**: A sincronização entre o script global e a inicialização específica da página de agendamentos foi restaurada, garantindo que o calendário e a timeline carreguem corretamente.

---

## Correção de UI — Subitens da Sidebar (Abril 2026) [v2.7.3]

Correção de duplicidade e erros estruturais no submenu de Configurações da sidebar.

### Mudanças:

- **Limpeza de HTML**: Remoção de blocos duplicados para "Lojas", "Importar Receitas" e "Importar Despesas".
- **Integridade da Sidebar**: Correção de tags aninhadas incorretamente e remoção de IDs duplicados que causavam comportamentos inesperados no menu.
- **Estabilização Visual**: Garantia de que cada subitem de configuração seja exibido apenas uma vez e na ordem correta.

---

## Módulo de Vacinas (Abril 2026)

Implementação do sistema de controle e histórico de imunização para os pets.

### Funcionalidades:

- **Histórico Completo**: Registro de vacinas aplicadas com data, lote, fabricante e veterinário responsável.
- **Controle de Ciclos**: Suporte a vacinas multi-doses (V1, V2, V3) com cálculo automático de reforço.
- **Dashboard de Vacinas**: Aba dedicada no prontuário para visualização rápida do status vacinal (Em dia / Pendente).
- **Integração com Agenda**: Possibilidade de gerar agendamentos de reforço diretamente do histórico de vacinas.

---

## Agendamentos Multi-Serviços (Abril 2026)

Evolução do sistema de agenda para permitir que um único compromisso envolva múltiplos procedimentos (ex: Consulta + Vacina + Banho).

### Ações Realizadas:

- **Banco de Dados**: Criação da tabela pivot `agendamento_servicos` e migração de dados legados.
- **Refatoração de Repositório**: Uso de `GROUP_CONCAT` para recuperar todos os serviços de um agendamento em uma única query.
- **UI/UX**: Modal de agendamento atualizado com seletor múltiplo (Multi-select) e exibição consolidada na timeline.
- **Faturamento Automatizado**: Geração de cobranças com descritivo detalhado de todos os itens do agendamento.

---

## Integração Serviço-Estoque (BOM) (Abril 2026)

Implementação do vínculo entre serviços e produtos (Bill of Materials), permitindo o controle automático de insumos.

### Ações Realizadas:

- **Gestão de Itens**: Nova interface na página de Serviços para vincular produtos e quantidades necessárias para cada procedimento.
- **Baixa Automática**: O sistema realiza a dedução direta no estoque assim que um serviço vinculado é marcado como "Pago" no financeiro.
- **Rastreabilidade**: Registro de consumo de produtos vinculado ao ID da cobrança para auditoria.

---

## Otimizações e Segurança (Abril 2026) [v2.8.0]

Série de melhorias estruturais focadas em performance e integridade do sistema.

### Ajustes Implementados:

- **Fix Segurança**: Correção de vulnerabilidade de Cross-Site Scripting (XSS) nos cards de KPIs do Dashboard.
- **Bolt (Performance)**: Eliminação de problemas de N+1 queries no `PacoteService` e no carregamento da Timeline da Agenda, reduzindo o tempo de resposta em cenários de alta carga.
- **Qualidade de Código**: Implementação de testes unitários para o `RelatoriosService`, garantindo a precisão nos cálculos de períodos e DRE.
- **Padronização**: Refatoração de Models para suporte a tipagem estrita (`strict_types=1`).

---

## Módulo de Despesas Parceladas (Abril 2026) [v2.9.1]

Implementação da capacidade de lançar despesas parceladas com valores e periodicidade customizáveis, incluindo um gerador automático.

### Funcionalidades:

- **Lançamento Flexível**: Toggle no modal de despesas para ativar o modo parcelado.
- **Vencimentos Múltiplos**: Interface dinâmica para adicionar parcelas com datas e valores independentes.
- **Gerador Rápido (v2.9.1)**: Adicionada ferramenta de preenchimento automático onde o usuário escolhe o número de parcelas e o intervalo de dias entre elas, facilitando o lançamento de contas recorrentes ou parcelamentos padrão.
- **Sugestão Inteligente**: Preenchimento automático de datas e cálculo em tempo real do total das parcelas.
- **Agrupamento**: Vínculo de parcelas via `des_grupo_id` para rastreabilidade, permitindo controle de status individual por vencimento.

### Infraestrutura Backend:

- **Banco de Dados**: Adição de campos `des_grupo_id`, `des_parcela_num` e `des_parcela_total` na tabela `fat_despesas`.
- **Service Layer**: Implementação de `createDespesaParcelada` no `FatService` para processamento atômico do grupo de parcelas.
- **Model**: Atualização do `FatDespesaModel` para suportar os novos campos de parcelamento.

### UI/UX:

- Campo "Valor" e "Vencimento" únicos são substituídos por uma lista dinâmica ao ativar o parcelamento.
- Barra de "Gerador Rápido" integrada ao container de parcelas.
- Feedback visual do somatório das parcelas antes do salvamento.

---

## Módulo de Agendamentos Recorrentes (Abril 2026) [v3.0.0]

Implementação da capacidade de repetir agendamentos com periodicidade flexível e controle de fim de série.

### Funcionalidades:

- **Recorrência Flexível**: Opções para repetição Semanal, Quinzenal e Mensal.
- **Controle de Fim**: Possibilidade de definir uma data final para a série ou repetir por um período padrão (1 ano / 52 ocorrências).
- **Geração Automática**: Criação atômica de todas as instâncias da série com sincronização automática de múltiplos serviços para cada agendamento.
- **Vínculo de Grupo**: Agrupamento de instâncias via `age_grupo_id` para futura gestão em massa.
- **UI Integrada**: Nova seção de recorrência no modal de agendamento com controle dinâmico (checkbox e campos auxiliares).

### Infraestrutura Backend:

- **Banco de Dados**: Adição de campos `age_grupo_id`, `age_recorrencia` e `age_recorrencia_fim` na tabela `agendamentos`.
- **Service Layer**: Refatoração do `AgendaService::create` para processar loops de recorrência com transação atômica.
- **Model**: Atualização do `AgendamentosModel` para suportar os novos metadados de série.

### UI/UX:

- Toggle "Repetir agendamento" no modal, limpando campos automaticamente ao ser desativado.
- Proteção contra edição de recorrência em agendamentos já criados (foco inicial em criação).
- Feedback visual de sucesso informando o número de ocorrências geradas.

---

## Ícones Dinâmicos em Serviços (Abril 2026) [v3.1.0]

Implementação de ícones/emojis customizáveis para cada serviço, melhorando a identificação visual em todo o sistema.

### Funcionalidades:

- **Personalização de Ícones**: Novo campo "Ícone/Emoji" no cadastro de serviços.
- **Identificação Visual**: Substituição do ícone padrão 🐾 pelo emoji escolhido pelo usuário na listagem de serviços, modal de agendamento e timeline da agenda.
- **Fallback Automático**: Caso nenhum ícone seja definido, o sistema mantém o padrão 🐾 para manter a consistência visual.

### Infraestrutura Backend:

- **Banco de Dados**: Migração `AddIconToServicos` para adicionar a coluna `ser_icone`.
- **Model**: Atualização do `ServicosModel` para suportar o novo campo.
- **Repositório**: Refatoração do `AgendamentosRepository` para incluir o ícone concatenado ao nome do serviço via SQL (`GROUP_CONCAT` com `IFNULL`).

---

## Módulo de Despesas Fixas (Recorrentes) [v3.2.0] (Abril 2026)

Implementação da capacidade de repetir despesas com periodicidade flexível, ideal para o lançamento de contas fixas e contratos.

### Funcionalidades:

- **Recorrência Flexível**: Suporte para repetição Semanal, Quinzenal, Mensal e Anual.
- **Geração Atômica**: O sistema projeta e cria todas as instâncias da série até a data de término definida (ou 1 ano por padrão).
- **Vínculo de Grupo**: Vínculo inteligente via `des_grupo_id` (prefixo `FIX_`) para facilitar a identificação de despesas que fazem parte de uma série recorrente.
- **UI Integrada**: Nova seção de recorrência no modal de despesas com controle dinâmico que evita conflitos com o sistema de parcelamento manual.

### Infraestrutura Backend:

- **Banco de Dados**: Migração `AddRecurrenceToDespesas` para adicionar colunas `des_recorrencia` e `des_recorrencia_fim`.
- **Service Layer**: Implementação de `createDespesaRecorrente` no `FatService` com processamento em loop e transação de banco de dados.
- **Model**: Atualização do `FatDespesaModel` para suportar os novos campos de metadados da série.

---

## Timeline Drag and Drop [v3.3.0] (Maio 2026)

Implementação de interatividade avançada na agenda, permitindo o remanejamento de horários via arraste e soltura.

### Funcionalidades Implementadas:

- **Arraste e Soltura (HTML5 DnD)**: Capacidade de arrastar agendamentos entre diferentes slots de horários na mesma data.
- **Feedback Visual Premium**: Highlight dos slots de destino (`drag-over`) e redução de opacidade do item em movimento (`dragging`), seguindo o design system do Petys.
- **Sincronização Assíncrona**: Atualização automática no banco de dados via AJAX ao soltar o item, com feedback instantâneo via Toast e recarregamento da timeline.
- **Prevenção de Conflitos**: O sistema mantém a integridade dos dados ao utilizar o endpoint centralizado de atualização da agenda.

### Infraestrutura:

- **JS Global**: Nova função `moveAppt` e integração com a `renderTimeline` em `script.js`.
- **CSS Global**: Adição de estados de interação em `style.css`.
- **Backend**: Utilização do método `update` no `AgendaService` para processar as mudanças de horário.

---

## Fix Drag and Drop [v3.3.1] (Maio 2026)

Correção crítica no sistema de remanejamento de agendamentos.

### Melhorias:

- **Tratamento de Dados Parciais**: O controlador `Agenda::update` foi refatorado para ignorar campos não enviados na requisição POST. Isso impede que campos como `pet_id` sejam sobrescritos com `NULL` durante ações de arrastar e soltar, que enviam apenas a nova data e hora.
- **Segurança e Integridade**: Prevenção de erros de chave estrangeira (Foreign Key Constraint) no banco de dados.
- **Validação Aprimorada**: Inclusão de `age_hora` no conjunto de regras de validação para atualizações.

---

## Edição de Lançamentos (Receitas) [v3.4.1] (Maio 2026)

Implementação de funcionalidade completa de edição (CRUD) para as cobranças (receitas) no módulo financeiro, padronizando o comportamento com o módulo de despesas.

### Funcionalidades Implementadas:

- **Edição Direta na Tabela**: As linhas de cobrança na aba "Lançamentos" tornaram-se clicáveis. O clique abre diretamente o formulário de edição pré-preenchido.
- **Unificação de Modais**: O modal `modal-nova-cobranca` foi transformado em um modal dinâmico que suporta tanto a criação de novos registros quanto a atualização de existentes.
- **Persistência em Edição**: Refatoração da lógica JavaScript para suportar a atualização de dados via endpoint `api/faturamento/cobranca/update/{id}`.
- **Consistência Visual**: As despesas na aba de lançamentos gerais também ganharam a interatividade de clique para edição, unificando a experiência de uso.
- **Resolução de Conflitos**: Renomeação da função de "Pagamento Rápido" na aba de Recebíveis para `quickPayCobranca`, evitando conflitos com o novo sistema de edição completa.

### Infraestrutura:

- **JS Global**: Novas funções `openEditCobranca(row)` e `openNovaCobranca()` em `script.js` (dentro da view).
- **View**: Inclusão de atributos `data-` nas linhas das tabelas para transporte de metadados do servidor para o cliente sem requisições extras.
- **Backend**: Ativação do uso dos métodos `updateCobranca` já existentes no controlador `Faturamento` e `FatService`.

---

_Relatório gerado e mantido pelo assistente Antigravity — última atualização: 15 de Maio de 2026 às 17:10._

Melhoria na identificação de agendamentos com a inclusão do nome do tutor.

### Melhorias:

- **Backend (Repositorio)**: Atualização dos métodos `getByDate`, `getUpcoming` e `search` no `AgendamentosRepository` para incluir o JOIN com a tabela `clientes`, permitindo recuperar o campo `tutor_nome` em todas as listagens de agenda.
- **Frontend (Timeline)**: Inclusão do nome do tutor entre parênteses ao lado do nome do pet nos blocos de agendamento da timeline diária.
- **Frontend (Próximos)**: Inclusão do nome do tutor na lista de "Próximos Agendamentos" da sidebar da agenda.
- **UX**: Facilitação da identificação rápida de clientes, especialmente em casos de pets com nomes comuns.

---

_Relatório gerado e mantido pelo assistente Antigravity — última atualização: 15 de Maio de 2026 às 08:26._

---

## Correção de Status no Faturamento da Agenda [v3.4.2] (Maio 2026)

Resolução do bug onde lançamentos financeiros originados da agenda permaneciam como "Pendente" mesmo após o pagamento.

### Melhorias Implementadas:

- **Backend (Controller)**: Atualização do método `faturar` no `Agenda.php` para capturar e validar o campo `status` enviado pelo modal. Anteriormente, este campo era ignorado, resultando no uso do valor padrão do banco de dados.
- **Frontend (UX)**: Refatoração da função `selectPaymentMethod` em `script.js` para automatizar o preenchimento do status. Ao selecionar métodos de pagamento imediatos (Pix, Cartão, Dinheiro), o sistema agora define o status como **Pago** por padrão, reduzindo cliques e erros operacionais.
- **Integridade**: Adição de regras de validação estritas para o campo de status na API de faturamento da agenda.

---

_Relatório gerado e mantido pelo assistente Antigravity — última atualização: 15 de Maio de 2026 às 17:40._

---

## Robustez no Modal de Edição Financeira [v3.4.3] (Maio 2026)

Melhoria na experiência de edição de lançamentos, garantindo que todos os dados sejam preenchidos corretamente, independente da origem ou volume de dados.

### Melhorias Implementadas:

- **Flexibilidade de Serviços**: O seletor de serviço no modal de cobrança foi convertido em um campo de texto com `datalist`. Isso permite que o sistema exiba e edite serviços com nomes personalizados (muito comuns em agendamentos) que não estavam na lista fixa anterior.
- **Seleção Dinâmica de Pets**: Implementação de lógica no JavaScript para adicionar automaticamente o pet do lançamento ao seletor caso ele não esteja no lote inicial de carregamento (aumentado para 100 registros). Isso resolve falhas de preenchimento em clínicas com grande volume de pacientes.
- **Robustez de UI**: Padronização da seleção de Formas de Pagamento e Categorias, utilizando busca por valor ou texto para garantir a marcação correta das opções no modal.
- **Performance**: Otimização do repositório de pets para carregar um lote maior de registros para os seletores de lookup.

---

_Relatório gerado e mantido pelo assistente Antigravity — última atualização: 15 de Maio de 2026 às 17:51._

---

## Controle de Status no Modal de Cobrança [v3.4.4] (Maio 2026)

Inclusão da funcionalidade de gerenciamento de status (Pago/Pendente) diretamente no modal completo de edição de cobranças.

### Melhorias Implementadas:

- **Interface (Modal)**: Adição do seletor de **Status** na seção de pagamento do modal de nova/editar cobrança. Isso permite que o usuário confirme o recebimento de uma conta a receber sem precisar sair do modal de edição.
- **Sincronização de Dados**: Atualização da lógica JavaScript para preencher o status atual do lançamento ao abrir o modal de edição e resetá-lo corretamente ao criar um novo registro.
- **Persistência**: Ajuste na função de salvamento (`saveCobranca`) para garantir que o status selecionado seja enviado e persistido no banco de dados através da API de faturamento.
- **UX de Confirmação**: Resolução da falha onde não era possível "confirmar o pagamento" através do modal de edição detalhada.

---

## Correção e Melhoria no Acesso ao Prontuário [v3.4.5] (Maio 2026)

Resolução de falha crítica no carregamento de prontuários e melhoria na navegabilidade entre Agenda e Clínica.

### Melhorias Implementadas:

- **Fix: Erro ao Abrir Prontuário**: Corrigido o erro que impedia a abertura do prontuário devido à falta da variável `vacinas` no retorno do `ProntuariosService::getFullRecord`. A ausência desse dado causava uma falha de renderização e redirecionamento automático para a página anterior.
- **Integração Agenda-Prontuário**: Adição de um botão de acesso rápido (👁️) diretamente nos blocos de agendamento na timeline da Agenda. Agora, os veterinários podem abrir o prontuário do pet com um único clique a partir do compromisso agendado.
- **UX de Navegação**: Refatoração do `script.js` para gerenciar o redirecionamento seguro para a rota `/prontuarios/{id}`, garantindo que o clique no ícone não dispare o modal de edição do agendamento (event bubbling control).

### Infraestrutura Backend:

- **Service Layer**: Atualização do `ProntuariosService` para incluir a busca de vacinas via `VacinasRepository` no método `getFullRecord`.
- **Frontend**: Inclusão de botão dinâmico na função `renderTimeline` do `script.js`.

---

## Correção de Infraestrutura de Banco de Dados [v3.4.6] (Maio 2026)

Resolução de erro de execução causado por migrações pendentes no ambiente.

### Melhorias Implementadas:

- **Fix: Tabela `pet_pesos` Inexistente**: Identificado através dos logs do sistema que a tabela de pesos (`pet_pesos`) não existia no banco de dados, causando uma exceção fatal ao tentar carregar o prontuário.
- **Execução de Migrações**: Realizada a sincronização completa do banco de dados via `php spark migrate`, garantindo que todas as tabelas necessárias (incluindo `pet_pesos` e estruturas recentes) estejam presentes.
- **Estabilização do Prontuário**: Com a infraestrutura corrigida, o carregamento de dados históricos de peso e evolução do pet foi normalizado, permitindo a abertura do prontuário sem interrupções.

---

## Correção de Renderização e Estabilização [v3.4.8] (Maio 2026)

Correção crítica na renderização do prontuário e limpeza de logs.

### Ajustes Realizados:

- **Correção de Tags PHP**: Corrigido erro de sintaxe na view `prontuarios/index.php` onde a lógica de status estava fora das tags PHP, impedindo a renderização correta da página.
- **Restauração de Variáveis**: Restauradas as variáveis de idade e data de nascimento do pet que foram acidentalmente removidas na iteração anterior.
- **Limpeza de Debug**: Removidos logs temporários do controlador para manter o ambiente de produção limpo.

---

## Correção no Agendamento Recorrente [v3.4.9] (Maio 2026)

Resolução de falha crítica na criação de agendamentos recorrentes. O frontend enviava os dados de recorrência no modal, mas o backend não estava extraindo essas chaves do payload POST.

Implementação de ícones/emojis customizáveis para cada serviço, melhorando a identificação visual em todo o sistema.

### Funcionalidades:

- **Personalização de Ícones**: Novo campo "Ícone/Emoji" no cadastro de serviços.
- **Identificação Visual**: Substituição do ícone padrão 🐾 pelo emoji escolhido pelo usuário na listagem de serviços, modal de agendamento e timeline da agenda.
- **Fallback Automático**: Caso nenhum ícone seja definido, o sistema mantém o padrão 🐾 para manter a consistência visual.

### Infraestrutura Backend:

- **Banco de Dados**: Migração `AddIconToServicos` para adicionar a coluna `ser_icone`.
- **Model**: Atualização do `ServicosModel` para suportar o novo campo.
- **Repositório**: Refatoração do `AgendamentosRepository` para incluir o ícone concatenado ao nome do serviço via SQL (`GROUP_CONCAT` com `IFNULL`).

---

## Módulo de Despesas Fixas (Recorrentes) [v3.2.0] (Abril 2026)

Implementação da capacidade de repetir despesas com periodicidade flexível, ideal para o lançamento de contas fixas e contratos.

### Funcionalidades:

- **Recorrência Flexível**: Suporte para repetição Semanal, Quinzenal, Mensal e Anual.
- **Geração Atômica**: O sistema projeta e cria todas as instâncias da série até a data de término definida (ou 1 ano por padrão).
- **Vínculo de Grupo**: Vínculo inteligente via `des_grupo_id` (prefixo `FIX_`) para facilitar a identificação de despesas que fazem parte de uma série recorrente.
- **UI Integrada**: Nova seção de recorrência no modal de despesas com controle dinâmico que evita conflitos com o sistema de parcelamento manual.

### Infraestrutura Backend:

- **Banco de Dados**: Migração `AddRecurrenceToDespesas` para adicionar colunas `des_recorrencia` e `des_recorrencia_fim`.
- **Service Layer**: Implementação de `createDespesaRecorrente` no `FatService` com processamento em loop e transação de banco de dados.
- **Model**: Atualização do `FatDespesaModel` para suportar os novos campos de metadados da série.

---

## Timeline Drag and Drop [v3.3.0] (Maio 2026)

Implementação de interatividade avançada na agenda, permitindo o remanejamento de horários via arraste e soltura.

### Funcionalidades Implementadas:

- **Arraste e Soltura (HTML5 DnD)**: Capacidade de arrastar agendamentos entre diferentes slots de horários na mesma data.
- **Feedback Visual Premium**: Highlight dos slots de destino (`drag-over`) e redução de opacidade do item em movimento (`dragging`), seguindo o design system do Petys.
- **Sincronização Assíncrona**: Atualização automática no banco de dados via AJAX ao soltar o item, com feedback instantâneo via Toast e recarregamento da timeline.
- **Prevenção de Conflitos**: O sistema mantém a integridade dos dados ao utilizar o endpoint centralizado de atualização da agenda.

### Infraestrutura:

- **JS Global**: Nova função `moveAppt` e integração com a `renderTimeline` em `script.js`.
- **CSS Global**: Adição de estados de interação em `style.css`.
- **Backend**: Utilização do método `update` no `AgendaService` para processar as mudanças de horário.

---

## Fix Drag and Drop [v3.3.1] (Maio 2026)

Correção crítica no sistema de remanejamento de agendamentos.

### Melhorias:

- **Tratamento de Dados Parciais**: O controlador `Agenda::update` foi refatorado para ignorar campos não enviados na requisição POST. Isso impede que campos como `pet_id` sejam sobrescritos com `NULL` durante ações de arrastar e soltar, que enviam apenas a nova data e hora.
- **Segurança e Integridade**: Prevenção de erros de chave estrangeira (Foreign Key Constraint) no banco de dados.
- **Validação Aprimorada**: Inclusão de `age_hora` no conjunto de regras de validação para atualizações.

---

## Edição de Lançamentos (Receitas) [v3.4.1] (Maio 2026)

Implementação de funcionalidade completa de edição (CRUD) para as cobranças (receitas) no módulo financeiro, padronizando o comportamento com o módulo de despesas.

### Funcionalidades Implementadas:

- **Edição Direta na Tabela**: As linhas de cobrança na aba "Lançamentos" tornaram-se clicáveis. O clique abre diretamente o formulário de edição pré-preenchido.
- **Unificação de Modais**: O modal `modal-nova-cobranca` foi transformado em um modal dinâmico que suporta tanto a criação de novos registros quanto a atualização de existentes.
- **Persistência em Edição**: Refatoração da lógica JavaScript para suportar a atualização de dados via endpoint `api/faturamento/cobranca/update/{id}`.
- **Consistência Visual**: As despesas na aba de lançamentos gerais também ganharam a interatividade de clique para edição, unificando a experiência de uso.
- **Resolução de Conflitos**: Renomeação da função de "Pagamento Rápido" na aba de Recebíveis para `quickPayCobranca`, evitando conflitos com o novo sistema de edição completa.

### Infraestrutura:

- **JS Global**: Novas funções `openEditCobranca(row)` e `openNovaCobranca()` em `script.js` (dentro da view).
- **View**: Inclusão de atributos `data-` nas linhas das tabelas para transporte de metadados do servidor para o cliente sem requisições extras.
- **Backend**: Ativação do uso dos métodos `updateCobranca` já existentes no controlador `Faturamento` e `FatService`.

---

_Relatório gerado e mantido pelo assistente Antigravity — última atualização: 15 de Maio de 2026 às 17:10._

Melhoria na identificação de agendamentos com a inclusão do nome do tutor.

### Melhorias:

- **Backend (Repositorio)**: Atualização dos métodos `getByDate`, `getUpcoming` e `search` no `AgendamentosRepository` para incluir o JOIN com a tabela `clientes`, permitindo recuperar o campo `tutor_nome` em todas as listagens de agenda.
- **Frontend (Timeline)**: Inclusão do nome do tutor entre parênteses ao lado do nome do pet nos blocos de agendamento da timeline diária.
- **Frontend (Próximos)**: Inclusão do nome do tutor na lista de "Próximos Agendamentos" da sidebar da agenda.
- **UX**: Facilitação da identificação rápida de clientes, especialmente em casos de pets com nomes comuns.

---

_Relatório gerado e mantido pelo assistente Antigravity — última atualização: 15 de Maio de 2026 às 08:26._

---

## Correção de Status no Faturamento da Agenda [v3.4.2] (Maio 2026)

Resolução do bug onde lançamentos financeiros originados da agenda permaneciam como "Pendente" mesmo após o pagamento.

### Melhorias Implementadas:

- **Backend (Controller)**: Atualização do método `faturar` no `Agenda.php` para capturar e validar o campo `status` enviado pelo modal. Anteriormente, este campo era ignorado, resultando no uso do valor padrão do banco de dados.
- **Frontend (UX)**: Refatoração da função `selectPaymentMethod` em `script.js` para automatizar o preenchimento do status. Ao selecionar métodos de pagamento imediatos (Pix, Cartão, Dinheiro), o sistema agora define o status como **Pago** por padrão, reduzindo cliques e erros operacionais.
- **Integridade**: Adição de regras de validação estritas para o campo de status na API de faturamento da agenda.

---

_Relatório gerado e mantido pelo assistente Antigravity — última atualização: 15 de Maio de 2026 às 17:40._

---

## Robustez no Modal de Edição Financeira [v3.4.3] (Maio 2026)

Melhoria na experiência de edição de lançamentos, garantindo que todos os dados sejam preenchidos corretamente, independente da origem ou volume de dados.

### Melhorias Implementadas:

- **Flexibilidade de Serviços**: O seletor de serviço no modal de cobrança foi convertido em um campo de texto com `datalist`. Isso permite que o sistema exiba e edite serviços com nomes personalizados (muito comuns em agendamentos) que não estavam na lista fixa anterior.
- **Seleção Dinâmica de Pets**: Implementação de lógica no JavaScript para adicionar automaticamente o pet do lançamento ao seletor caso ele não esteja no lote inicial de carregamento (aumentado para 100 registros). Isso resolve falhas de preenchimento em clínicas com grande volume de pacientes.
- **Robustez de UI**: Padronização da seleção de Formas de Pagamento e Categorias, utilizando busca por valor ou texto para garantir a marcação correta das opções no modal.
- **Performance**: Otimização do repositório de pets para carregar um lote maior de registros para os seletores de lookup.

---

_Relatório gerado e mantido pelo assistente Antigravity — última atualização: 15 de Maio de 2026 às 17:51._

---

## Controle de Status no Modal de Cobrança [v3.4.4] (Maio 2026)

Inclusão da funcionalidade de gerenciamento de status (Pago/Pendente) diretamente no modal completo de edição de cobranças.

### Melhorias Implementadas:

- **Interface (Modal)**: Adição do seletor de **Status** na seção de pagamento do modal de nova/editar cobrança. Isso permite que o usuário confirme o recebimento de uma conta a receber sem precisar sair do modal de edição.
- **Sincronização de Dados**: Atualização da lógica JavaScript para preencher o status atual do lançamento ao abrir o modal de edição e resetá-lo corretamente ao criar um novo registro.
- **Persistência**: Ajuste na função de salvamento (`saveCobranca`) para garantir que o status selecionado seja enviado e persistido no banco de dados através da API de faturamento.
- **UX de Confirmação**: Resolução da falha onde não era possível "confirmar o pagamento" através do modal de edição detalhada.

---

## Correção e Melhoria no Acesso ao Prontuário [v3.4.5] (Maio 2026)

Resolução de falha crítica no carregamento de prontuários e melhoria na navegabilidade entre Agenda e Clínica.

### Melhorias Implementadas:

- **Fix: Erro ao Abrir Prontuário**: Corrigido o erro que impedia a abertura do prontuário devido à falta da variável `vacinas` no retorno do `ProntuariosService::getFullRecord`. A ausência desse dado causava uma falha de renderização e redirecionamento automático para a página anterior.
- **Integração Agenda-Prontuário**: Adição de um botão de acesso rápido (👁️) diretamente nos blocos de agendamento na timeline da Agenda. Agora, os veterinários podem abrir o prontuário do pet com um único clique a partir do compromisso agendado.
- **UX de Navegação**: Refatoração do `script.js` para gerenciar o redirecionamento seguro para a rota `/prontuarios/{id}`, garantindo que o clique no ícone não dispare o modal de edição do agendamento (event bubbling control).

### Infraestrutura Backend:

- **Service Layer**: Atualização do `ProntuariosService` para incluir a busca de vacinas via `VacinasRepository` no método `getFullRecord`.
- **Frontend**: Inclusão de botão dinâmico na função `renderTimeline` do `script.js`.

---

## Correção de Infraestrutura de Banco de Dados [v3.4.6] (Maio 2026)

Resolução de erro de execução causado por migrações pendentes no ambiente.

### Melhorias Implementadas:

- **Fix: Tabela `pet_pesos` Inexistente**: Identificado através dos logs do sistema que a tabela de pesos (`pet_pesos`) não existia no banco de dados, causando uma exceção fatal ao tentar carregar o prontuário.
- **Execução de Migrações**: Realizada a sincronização completa do banco de dados via `php spark migrate`, garantindo que todas as tabelas necessárias (incluindo `pet_pesos` e estruturas recentes) estejam presentes.
- **Estabilização do Prontuário**: Com a infraestrutura corrigida, o carregamento de dados históricos de peso e evolução do pet foi normalizado, permitindo a abertura do prontuário sem interrupções.

---

## Correção de Renderização e Estabilização [v3.4.8] (Maio 2026)

Correção crítica na renderização do prontuário e limpeza de logs.

### Ajustes Realizados:

- **Correção de Tags PHP**: Corrigido erro de sintaxe na view `prontuarios/index.php` onde a lógica de status estava fora das tags PHP, impedindo a renderização correta da página.
- **Restauração de Variáveis**: Restauradas as variáveis de idade e data de nascimento do pet que foram acidentalmente removidas na iteração anterior.
- **Limpeza de Debug**: Removidos logs temporários do controlador para manter o ambiente de produção limpo.

---

## Correção no Agendamento Recorrente [v3.4.9] (Maio 2026)

Resolução de falha crítica na criação de agendamentos recorrentes. O frontend enviava os dados de recorrência no modal, mas o backend não estava extraindo essas chaves do payload POST.

### Ajustes Realizados:

- **Extração de Dados**: Inclusão de `age_recorrencia` e `age_recorrencia_fim` na lista de campos recuperados no método `create` do controlador `Agenda.php`. 
- **Geração de Recorrência**: Isso garante que as configurações escolhidas pelo usuário (semanal, mensal, etc.) sejam processadas pelo `AgendaService` e persistidas no banco.

---

## Correção Visual do Widget de Evolução [v3.4.10] (Junho 2026)

Resolução de problema de transbordo e alinhamento responsivo no widget "Nova Evolução".

### Ajustes Realizados:

- **Layout Responsivo**: Alteração do `grid-template-columns` dos botões de templates (Consulta, Vacina, Retorno, Cirurgia) para utilizar `repeat(auto-fit, minmax(120px, 1fr))`.
- **Melhoria UX**: Isso garante que os botões fiquem organizados em uma única linha em telas médias e grandes, enquanto quebram a linha de forma fluida (wrap) em dispositivos móveis menores, evitando que a interface transborde ou corte o texto dos botões.

---

_Relatório gerado e mantido pelo assistente Antigravity — última atualização: 28 de Junho de 2026 às 21:05._

## Extração de Scripts e Estilos do Prontuário (v3.4.11)

- **Refatoração de View (Prontuários)**: Extração do bloco `<style>` e `<script>` inline do arquivo `prontuarios/index.php`.
- **Centralização de CSS e JS**: O CSS foi transferido para `style.css` e as funções JavaScript foram migradas para `script.js` utilizando o objeto global `window.PRONTUARIO_DATA` para injeção de variáveis PHP, garantindo melhor performance e separação de conceitos.

## Corre��o de View do Prontu�rio [v3.4.12] (Junho 2026)

- **Fix**: Corrigido o include da view evolucoes.php no index de prontu�rios que estava referenciando o arquivo incorreto (evolucao). Essa falha causava uma exce��o que o controller capturava, gerando um redirecionamento for�ado para a listagem.

### Vers�o 3.4.13 - 29/06/2026
- Corre��o do ID do input de dose na calculadora do modal do prontu�rio.
- Corre��o da chamada da fun��o de copiar para evolu��o na calculadora.
- Ajuste no controller de prontu�rios para injetar automaticamente o ID do veterin�rio logado ao salvar a evolu��o, prevenindo falhas de restri��o de chave no banco de dados.
- Melhoria no tratamento de mensagens de erro de valida��o ao salvar evolu��o no frontend (script.js).

- Corre��o do erro no formato da data de nascimento no prontu�rio, evitando atribuir '0000-00-00' ao input type="date".

- Resolu��o do conflito de IDs (ID collision) entre a calculadora do modal e a da aba de utilit�rios, que impedia o c�lculo e a c�pia para a evolu��o no modal.
- Refatora��o do arquivo script.js, removendo fun��es duplicadas (calculateDose e copyToEvolution) e separando a l�gica da calculadora do modal (modalCalculateDose e modalCopyToEvolution).


---

## Integra��o Usu�rios Shield e Equipe (Junho 2026)

Foi implementada a op��o de vincular usu�rios do Shield que j� existem (mas ainda n�o possuem associa��o) ao cadastro de um membro da equipe na tela Equipe, em uma rela��o um-para-um.


### Vers�o 3.4.16 - 03/07/2026
- Implementada a funcionalidade de edi��o do registro de evolu��o.
- Adicionada rota de edi��o de evolu��o.
- Atualizados backend e frontend para preenchimento dos dados existentes no formul�rio e requisi��o de atualiza��o.

### Versão 3.4.17 - 03/07/2026
- Adicionado botão de deletar evolução com confirmação.

- Correção: Implementada a função e a rota no backend para o botão de deletar evolução, que não estava definido.

### Versão 3.4.18 - 04/07/2026
- Correção de erro de sintaxe no arquivo faturamento.js (uso indevido de barra invertida em split(\'T\')) que impedia a execução do script e acusava openEditCobranca is not defined.

### Versão 3.4.19 - 04/07/2026
- Correção do erro de `BadRequestException` no CodeIgniter causado pela concatenação incorreta de `window.BASE_URL` dentro de template literals nos scripts em `public/dist/js/pages/`. A string `'+window.BASE_URL+'` estava sendo enviada como parte literal da URI.

### Versão 3.4.20 - 04/07/2026
- Correção do erro `PageNotFoundException` para rotas de API com o prefixo `undefinedapi/`. O erro ocorria porque a variável global `window.BASE_URL` não estava definida no escopo principal. A declaração e leitura da meta tag `base-url` foi adicionada ao início do `script.js`, garantindo que todas as requisições possuam a URL base correta.

---

## Corre��o de Layout Mobile (BS5 Grid) [v3.4.31] (Julho 2026)

Corre��o do tamanho da tela e overflow horizontal em dispositivos m�veis.

### Melhorias Implementadas:

- **Preven��o de Overflow (.row)**: Adicionado `overflow-x: hidden` na classe `.content` para encapsular corretamente as margens negativas do sistema de grids do Bootstrap 5, evitando o surgimento de barra de rolagem horizontal.
- **Limites de Flexbox (.main)**: Adicionado `min-width: 0` no container principal para impedir que elementos internos muito largos forcem a tela al�m de 100vw, garantindo que o layout mobile (celular) fique sempre contido dentro da �rea vis�vel.
- **Ajuste de Largura Mobile**: Definido explicitamente `width: 100%` e `max-width: 100vw` para a tag `.main` no media query de 768px, restaurando a experi�ncia responsiva adequada ap�s a transi��o para o BS5.
- **Minifica��o de Assets**: Atualiza��o e minifica��o do `style.css` para `style-min.css`.

## Itera��o 2026-07-18
- Problema resolvido: Prontu�rio n�o estava abrindo devido � falta da tabela 'prescricoes'. Executada a migra��o de banco de dados (php spark migrate) para criar as tabelas necess�rias.

## Iteraçāo 2026-07-18
- Problema resolvido: Adicionada opção de exibição gráfica de partição de comprimidos (1/2, 3/4, etc) na prescrição médica. Inclui novos campos no banco, interface visual atualizada e representação gráfica css na impressão. Atualização para v3.4.32.

 # #   2 0 2 6 - 0 7 - 2 6   -   C o n s o l i d a � � o   d e   M i g r a t i o n s 
 -   G e r a � � o   d e   1 5   n o v a s   m i g r a t i o n s   a g r u p a n d o   a s   t a b e l a s   p r i n c i p a i s   e   s e u s   p i v o t s .  
 
 # #   2 0 2 6 - 0 7 - 2 7   -   C o r r e � � o   d e   N o m e n c l a t u r a 
 -   R e n o m e a d o   a s   v i n c u l a � � e s   d e   p e t _ i d   p a r a   p a c i e n t e _ i d   e m   t o d a s   a s   m i g r a t i o n s   d e   t a b e l a s   c r i a d a s .  
 
 # #   2 0 2 6 - 0 7 - 2 7   -   I n s e r � � o   d e   � n d i c e s 
 -   I n s e r i d o s   o s   � n d i c e s   ( c h a v e s   p r i m � r i a s ,   c h a v e s   e s t r a n g e i r a s ,   A U T O _ I N C R E M E N T   e   � n d i c e s )   e x t r a � d o s   d o   a r q u i v o   p e t y s . s q l   n a s   n o v a s   m i g r a t i o n s ,   j �   r e f l e t i n d o   a s   m o d i f i c a � � e s   d e   n o m e n c l a t u r a   d e   p a c i e n t e s .  
 
## 2026-08-07 - Configuração de Roteamento (htaccess)
- Criação do arquivo .htaccess na raiz do projeto para direcionar o tráfego adequadamente para o frontend (diretório rontend/dist), ignorando as rotas da API/backend.

## [1.0.1] - 2026-08-08
- Atualiza��o do README.md para refletir a estrutura atual do projeto (React + CodeIgniter 4).
- Vers�o atualizada no package.json e backend/composer.json.

## [1.0.2] - 2026-08-08
- Atualização do arquivo deploy.yml para incluir exclusões baseadas na nova estrutura separada de frontend e backend (backend/vendor, backend/writable, backend/tests, frontend/src e frontend/node_modules).
- Versão atualizada no package.json (root) e backend/composer.json.

## [1.0.3] - 2026-08-08
- Correção das exclusões do deploy.yml para enviar apenas a pasta 'dist' do frontend, bloqueando todas as outras (src, public, arquivos base).
- Versão atualizada no package.json (root) e backend/composer.json.

## [1.0.4] - 2026-08-08
- Cria��o de template global de impress�o (ReportTemplate) e inclus�o nas configura��es de cabe�alho e rodap�.
- Integra��o do template no Modal de Prescri��es para possibilitar a impress�o da Receita Simples.

## [1.0.5] - 2026-08-21
- Inclusão dos campos de endereço (rua, numero, complemento, bairro, cep) e documentos (rg, nascimento) no formulário de clientes React (ClienteFormModal.tsx).
- Integração da API externa ViaCEP (https://viacep.com.br/ws/{cep}/json/) com busca dinâmica por CEP (8 dígitos), tratamento de erros, estado de carregamento e autopreenchimento dos campos de rua, bairro, cidade, estado e complemento.
- Redesign visual do modal de clientes com seções destacadas por ícones (Dados Pessoais, Contato, Endereço, Observações), grid responsivo e melhor experiência de usuário.
- Atualização da tela de detalhes do cliente (ClienteDetailPage.tsx) para exibir o endereço completo formatado.
- Versão atualizada no package.json (root) e backend/composer.json para 1.0.5.

 # #   [ 1 . 0 . 6 ]   -   2 0 2 6 - 0 8 - 2 1 
 -   D u p l i c a c a o   d a   e t a p a   d e   d e p l o y   v i a   F T P   n o   a r q u i v o   . g i t h u b / w o r k f l o w s / d e p l o y . y m l   p a r a   s u p o r t a r   s i n c r o n i z a c a o   e m   d u a s   p a s t a s   d e   d e s t i n o   n a   H o s t i n g e r . 
 -   V e r s a o   a t u a l i z a d a   n o   p a c k a g e . j s o n   e   b a c k e n d / c o m p o s e r . j s o n   p a r a   1 . 0 . 6 .  
 
## [1.0.7] - 2026-09-04
- Inclusao de exibicao do endereco completo do paciente no modal de agendamento (AgendamentoFormModal.tsx) logo abaixo do campo de selecao de Paciente.
- Atualizacao do componente PacientePicker.tsx para repassar os dados completos do paciente selecionado (incluindo endereco) via callback onChange.
- Enriquecimento dos endpoints e repositorios backend (PetsRepository.php e AgendamentosRepository.php) para incluir e formatar os campos de endereco do paciente ou tutor (rua, numero, complemento, bairro, cidade, estado e CEP) nas buscas para autocomplete (findForLookup), busca por id e listagens da agenda.
- Adicao de endpoint frontend fetchPacienteById em pacientes/api.ts para carregar o endereco dinamicamente caso o agendamento ja venha com paciente pre-selecionado sem endereco em cache.
- Estilizacao moderna e responsiva da label de endereco (.paciente-endereco-label) com icone MapPin e animacao de fade-in.
- Versao atualizada no package.json (root) e backend/composer.json para 1.0.7.- Correcao no endpoint de atualizacao de clientes (PUT /clientes/:id): remocao da regra estatica is_unique do ClientesModel que causava falso-positivo de unicidade em updates sem o campo id no payload, sanitizacao de strings vazias para null em colunas opcionais (evitando erros de DATE em nascimento) e validacao explicita de CPF duplicado no ClientesService.