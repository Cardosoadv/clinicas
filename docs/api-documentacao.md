---
title: API da documentação interna
nav_order: 10
description: "Contrato dos endpoints de leitura e de edição do manual interno (api/v1/documentacao)."
---

# API da documentação interna
{: .no_toc }

Endpoints do manual lido dentro do sistema, consumidos por `frontend/src/features/documentacao/`. O formato das páginas está em [Documentação interna](documentacao-interna).
{: .fs-5 .fw-300 }

<details open markdown="block">
  <summary>Nesta página</summary>
  {: .text-delta }
1. TOC
{:toc}
</details>

---

## Convenções

Base: `/api/v1/documentacao`. Mesmo envelope do restante da API (`{status, data, message}`) e mesma sessão (cookie).

| Código | Quando | Corpo |
|:--|:--|:--|
| 200 | sucesso | `{"status": "success", "data": …}` |
| 304 | imagem sem alteração (`If-None-Match` igual ao `ETag`) | vazio |
| 401 | sem login | `{"status": "error", "message": "Não autenticado."}` |
| 403 | rota de edição sem `documentacao.editar` | `{"status": "error", "message": "…"}` |
| 404 | página, tela ou imagem inexistente, **ou invisível** para o usuário | `{"status": "error", "message": "…"}` |
| 422 | edição recusada | `{"status": "error", "message": "Dados inválidos.", "errors": {campo: mensagem}}` |

## Visibilidade

A leitura exige só login. Uma página que declara `permissao` (própria ou herdada da capa) só aparece para quem tem a permissão; para os demais, ela responde **404**, como se não existisse, e some do índice, da busca e da ajuda por tela.

## Índice

`GET /documentacao`: seções e páginas visíveis, ordenadas (`ordem`, depois o título).

```json
{
  "status": "success",
  "data": [
    {
      "slug": "primeiros-passos",
      "titulo": "Primeiros passos",
      "resumo": "O que é o Clínicas, como entrar no sistema e onde encontrar cada tela.",
      "capa": true,
      "paginas": [
        { "slug": "primeiros-passos/painel", "titulo": "Dashboard", "resumo": "…", "status": "publicado" }
      ]
    }
  ]
}
```

`capa: true` indica que o próprio `slug` da seção abre a página de capa. `paginas` não inclui a capa. Rascunhos (`status: "rascunho"`) só aparecem para quem tem `documentacao.editar`.

## Página

`GET /documentacao/paginas/{slug}` (o slug pode ter `/`: `paginas/agenda/faturar`).

| Campo | Descrição |
|:--|:--|
| `slug`, `secao`, `capa` | endereço da página, seção e se é a capa |
| `origem`, `status`, `id` | `arquivo` (app/Docs) ou `banco`; `publicado` ou `rascunho`; `id` no banco, ou `null` |
| `titulo`, `resumo` | o `html` não traz o título: exiba como título da página |
| `html` | corpo já **sanitizado** pelo backend |
| `sumario` | títulos `h2` e `h3`: `{nivel, texto, ancora}`; `ancora` é o `id` do título no `html` |
| `anterior`, `proxima` | vizinhas na ordem de leitura do índice (`{slug, titulo}` ou `null`) |
| `atualizado_em` | ISO 8601 |

## Busca

`GET /documentacao/busca?q=vacina`: até 20 resultados `{slug, titulo, secao, trecho}`.

- Não diferencia maiúsculas nem acentos; todos os termos (de 2 caracteres ou mais) precisam aparecer.
- Relevância: título (peso 10) > palavras-chave (5) > resumo (3) > corpo (1).
- `secao` é o **título** da seção; `trecho` é **texto puro** de até ~160 caracteres. O frontend escapa e destaca os termos.

## Ajuda por tela

`GET /documentacao/tela/{tela}`: a primeira página visível, na ordem do índice, que declara a tela em `telas`. Resposta igual à de [Página](#página); 404 quando nenhuma página atende (o frontend esconde o botão **?**).

## Imagens

`GET /documentacao/imagens/{secao}/img/{arquivo}`: arquivo de `app/Docs/<secao>/img/` ou das imagens enviadas pela interface. Só `png`, `jpg`, `jpeg`, `gif`, `webp` e `avif`; `Cache-Control: private, max-age=86400` e `ETag`.

No `html` das páginas, o `src` vem **relativo à URL da API** (`documentacao/imagens/agenda/img/tela.webp`), com `width` e `height` lidos do arquivo. O frontend completa com `API_BASE_URL` (`prepararHtml` em `features/documentacao/api.ts`).

## HTML das páginas

Tags que podem aparecer: `p br hr h1–h6 strong em del code pre blockquote aside ul ol li a img table thead tbody tr th td input`. Nenhum atributo de evento, `style` ou `class` arbitrário.

| Seletor | Origem |
|:--|:--|
| `aside.doc-aviso.doc-aviso--nota` / `--dica` / `--atencao` | avisos `> [!NOTA]`, `> [!DICA]`, `> [!ATENCAO]` |
| `p.doc-aviso-titulo` | primeira linha do aviso ("Nota", "Dica" ou "Atenção") |
| `li > input[type=checkbox][disabled]` | listas de tarefas |
| `th[align]`, `td[align]` | alinhamento de colunas |
| `h1`–`h6[id]` | todos os títulos têm âncora |

## Links dentro do conteúdo

| `href` no HTML | O que o frontend faz |
|:--|:--|
| `documentacao/{slug}` | abre a página sem recarregar |
| `documentacao/{slug}#{ancora}` | abre a página e rola até o título |
| `agenda`, `relatorios/dre`, `./` (tela do sistema) | navega para `/agenda`, `/relatorios/dre`, `/` |
| `https://…`, `mailto:` | já vêm com `target="_blank" rel="noopener noreferrer"` |
| `<a>` sem `href` | link recusado pelo backend; é só texto |

Os links internos são relativos de propósito: o `<base href>` do `index.html` resolve o subcaminho de deploy, e o clique é interceptado (`DocConteudo.tsx`) para navegar pelo React Router.

## Edição

Base: `/api/v1/documentacao/admin`, com o filtro `apipermission:documentacao.editar`.

| Método | Rota | Corpo | Resposta |
|:--|:--|:--|:--|
| GET | `paginas` | — | lista das duas fontes (abaixo) |
| GET | `paginas/{id}` | — | página do banco completa |
| POST | `paginas` | página | 201, `{data, avisos}`; nasce como **rascunho** |
| PUT | `paginas/{id}` | página | `{data, avisos}`; **não muda o status** |
| POST | `paginas/{id}/publicar` / `despublicar` | — | `{data}` |
| DELETE | `paginas/{id}` | — | exclusão definitiva; volta a valer o arquivo de mesmo slug, se houver |
| POST | `paginas/de-arquivo/{slug}` | — | 201, cópia editável (rascunho) de uma página de arquivo |
| POST | `preview` | `{markdown, slug}` | `{data: {html, sumario, avisos}}`, sem gravar |
| POST | `imagens` | multipart: `arquivo`, `secao` | 201, `{data: {caminho, markdown, largura, altura}}` |

**Item da lista**: `{id, slug, titulo, secao, origem, status, tem_arquivo, atualizado_em}`. Páginas de arquivo sem cópia no banco vêm com `origem: "arquivo"`, `id: null` e `status: "publicado"`; para editá-las, crie a cópia com `de-arquivo`.

**Página** (corpo do POST/PUT): `slug` (`secao` ou `secao/pagina`, único), `titulo`, `resumo`, `ordem`, `permissao`, `telas`, `palavras_chave` (mesmas regras do cabeçalho) e `markdown` (até 200 mil caracteres). A resposta acrescenta `id`, `status`, `tem_arquivo`, `publicado_em`, `criado_em`, `atualizado_em`, `criado_por` e `atualizado_por`.

**Avisos** não impedem salvar: link ou imagem descartados, imagem sem texto alternativo e tela de `telas` já usada por outra página.

**Imagens**: PNG, JPG, GIF ou WebP de até 5 MB; mais largas que 1600 px são reduzidas. Ficam em `writable/uploads/documentacao/<secao>/img/`.

## Permissões no `auth/me`

`GET /auth/me` e `POST /auth/login` devolvem também `permissoes`: a lista das permissões efetivas do usuário (grupos + diretas). O frontend usa `documentacao.editar` para mostrar os botões e as telas de edição.
