---
title: Documentação interna
nav_order: 9
description: "O manual lido dentro do sistema: formato das páginas, ajuda por tela, edição pela interface e verificação no CI."
---

# Documentação interna (manual do sistema)
{: .no_toc }

O manual do usuário que aparece **dentro do Clínicas**, no menu **Sistema → Documentação** e no botão **?** da barra superior. O backend lê as páginas em Markdown, converte em HTML seguro e o frontend só exibe o resultado. É o mesmo sistema do Ordo Juris, adaptado ao Clínicas.
{: .fs-5 .fw-300 }

<details open markdown="block">
  <summary>Nesta página</summary>
  {: .text-delta }
1. TOC
{:toc}
</details>

---

{: .nota }
Esta página (GitHub Pages) é a documentação **técnica**, para quem mantém o código. O manual do **usuário** fica em `backend/app/Docs` e é lido no próprio sistema. O contrato dos endpoints está em [API da documentação interna](api-documentacao).

## Onde ficam as páginas

```text
backend/app/Docs/
  primeiros-passos/        # uma pasta por seção
    index.md               # capa da seção (opcional)
    painel.md              # página
    img/                   # imagens da seção
      dashboard.webp
  agenda/
    index.md
    faturar.md
```

- Só existem **dois níveis**: seção (pasta) e página (arquivo `.md`). Subpastas, além de `img/`, não são lidas.
- Nomes de pasta e de arquivo: minúsculas, números e hífen (`livro-caixa.md`).
- O **slug** vem do caminho, sem `.md`: `agenda/faturar.md` vira `agenda/faturar`; a capa `agenda/index.md` vira `agenda`.
- Seção sem `index.md` aparece no índice com o nome da pasta.

**Páginas criadas pela interface**: quem tem a permissão `documentacao.editar` cria e edita páginas na tela **Documentação → Editar documentação**. Elas ficam na tabela `documentacao_paginas`, seguem o mesmo formato e, depois de publicadas, substituem o arquivo de mesmo slug. O arquivo continua sendo a versão oficial versionada: para incorporar uma mudança feita pela interface, copie o texto para o `.md` e exclua a página do banco.

## Cabeçalho (frontmatter)

```markdown
---
titulo: Faturar um atendimento
resumo: Gerar a cobrança do agendamento, lançar os materiais usados e pagar com pacote.
ordem: 30
permissao: documentacao.editar
telas: [agenda, agenda-todos]
palavras_chave:
  - cobrança
  - materiais
---

Texto da página em Markdown...
```

| Campo | Obrigatório | Regra |
|:--|:--|:--|
| `titulo` | sim | até 120 caracteres |
| `resumo` | não | até 300 caracteres; aparece no índice, na busca e na ajuda |
| `ordem` | não | inteiro de 0 a 9999, padrão 100; menor aparece primeiro (empate: título) |
| `permissao` | não | permissão do Shield (`escopo.acao`); sem ela o usuário não vê a página |
| `telas` | não | identificadores de tela (tabela abaixo) que abrem esta página no botão **?** |
| `palavras_chave` | não | termos extras para a busca, até 60 caracteres cada |

Campo fora da tabela é erro. A validação oficial é `App\Libraries\Documentacao\Frontmatter`; [`documentacao-pagina.schema.json`](documentacao-pagina.schema.json) descreve o mesmo formato para editores.

**Herança da capa**: uma página sem `permissao` usa a da capa (`index.md`) da sua seção.

**Sintaxe aceita** (subconjunto de YAML, lido sem biblioteca): `chave: valor` (texto com ou sem aspas, ou inteiro), lista em linha `[a, b]`, lista em bloco (`chave:` seguido de linhas `  - item`) e comentários com `#`.

{: .nota }
Diferente do Ordo Juris, o Clínicas não tem catálogo de módulos ativáveis, então o campo `modulo` não existe. A leitura da documentação exige só login; a edição exige `documentacao.editar` (grupos superadmin, admin e developer).

## Telas da ajuda contextual

O botão **?** aparece nas telas que têm página de ajuda. O identificador de cada tela sai da rota, em `frontend/src/features/documentacao/telas.ts`:

| Rota | Tela |
|:--|:--|
| `/` | `dashboard` |
| `/agenda`, `/agenda/todos` | `agenda`, `agenda-todos` |
| `/pacientes`, `/clientes`, `/prontuarios` | `pacientes`, `clientes`, `prontuarios` |
| `/clientes/duplicados`, `/clientes/:id` | `clientes-duplicados`, `cliente-detalhe` |
| `/prontuarios/:id/{aba}` | `prontuario-anamnese`, `prontuario-historico`, `prontuario-vacinas`, `prontuario-peso`, `prontuario-prescricoes`, `prontuario-evolucao`, `prontuario-imagens` |
| `/servicos`, `/pacotes`, `/estoque`, `/equipe`, `/lojas`, `/configuracoes` | o mesmo nome |
| `/faturamento/{aba}` | `faturamento-dashboard`, `faturamento-cobrancas`, `faturamento-despesas`, `faturamento-notas` |
| `/relatorios/{relatorio}` | `relatorio-extrato`, `relatorio-dre`, `relatorio-livro-caixa` |
| `/documentacao-admin`, `/documentacao-admin/:id` | `documentacao-admin`, `documentacao-editor` |

Ao criar uma tela nova no frontend, acrescente-a em `telas.ts` e declare o identificador em `telas` de uma página. Cada tela deve aparecer em **uma** página só.

## Corpo em Markdown

CommonMark com as extensões do GitHub: tabelas, listas de tarefas, tachado e links automáticos.

- **Títulos**: o título vem do cabeçalho. No corpo, use `##` e `###`; eles formam o "Nesta página".
- **HTML cru não é aceito**: aparece como texto. O HTML gerado passa por uma lista branca de tags e atributos.
- **Links para outra página**: caminho relativo do `.md`, com âncora opcional: `[faturar](../agenda/faturar.md#materiais-utilizados)`. O backend reescreve para `documentacao/agenda/faturar#materiais-utilizados`.
- **Links para telas do sistema**: o caminho do React Router com barra inicial, como `[Agenda](/agenda)`. O backend reescreve para o caminho relativo (`agenda`), que o `<base href>` resolve no subcaminho de deploy.
- **Links externos**: só `https://` e `mailto:`.
- **Imagens**: em `img/` da seção, com caminho relativo e texto alternativo: `![Agenda do dia](img/agenda-dia.webp)`. Prefira WebP com no máximo 1600 px de largura; o backend põe `width` e `height` lidos do arquivo.
- **Avisos**: bloco de citação cuja primeira linha é `> [!NOTA]`, `> [!DICA]` ou `> [!ATENCAO]`.

## Verificação (CI)

`php spark docs:verificar` confere todas as páginas de `app/Docs` e sai com erro se encontrar problema: cabeçalho inválido, nome fora do padrão, `.md` fora de `<secao>/<pagina>.md`, link para página ou âncora inexistente, link em formato não aceito, imagem fora de `<secao>/img/`, inexistente, sem texto alternativo ou com mais de 1600 px, e a mesma tela em mais de uma página.

O workflow `.github/workflows/tests-documentacao.yml` roda os testes PHPUnit da documentação e o `docs:verificar` em cada pull request que mexe no backend.

Use `--raiz <pasta>` para conferir outra pasta. Depois de publicar mudanças nos arquivos, o cache se renova sozinho (a assinatura inclui a data de modificação de cada arquivo); `php spark docs:cache:clear` força a limpeza.

## Capturas de tela

- **Só dados fictícios.** Nunca capture pacientes, tutores ou valores reais, nem com borrão: a imagem fica no repositório para sempre.
- WebP com largura máxima de 1600 px, em `app/Docs/<secao>/img/`, com nome em minúsculas e hífen.
- Capture só a área que interessa (um formulário, um painel), não a tela inteira com o menu.

Imagens enviadas pela interface ficam em `backend/writable/uploads/documentacao/<secao>/img/` (fora do deploy por FTP) e são servidas pela mesma rota.

## Implantar

1. `composer install` no servidor (`backend/`): a documentação usa a biblioteca `league/commonmark`, e a pasta `vendor/` não vai pelo FTP.
2. `php spark migrate` para criar a tabela `documentacao_paginas` (sem ela, o manual funciona só com os arquivos).
3. `npm run build` antes do commit, porque o `frontend/dist/` é versionado.

## Código

| Parte | Onde |
|:--|:--|
| Formato do cabeçalho | `app/Libraries/Documentacao/Frontmatter.php` |
| Markdown → HTML seguro | `app/Libraries/Documentacao/RenderizadorDocumentacao.php` |
| Catálogo (arquivos + banco, cache) | `app/Services/Documentacao/CatalogoDocumentacao.php` |
| Leitura, busca e ajuda por tela | `app/Services/Documentacao/DocumentacaoService.php` |
| Edição pela interface | `app/Services/Documentacao/EdicaoDocumentacaoService.php` |
| Verificação | `app/Services/Documentacao/VerificadorDocumentacao.php`, comando `docs:verificar` |
| API | `app/Controllers/V1/Documentacao.php`, `DocumentacaoAdmin.php`; filtro `apipermission` |
| Frontend | `frontend/src/features/documentacao/` (leitor, busca, ajuda contextual, lista de edição e editor) |
