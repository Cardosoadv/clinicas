---
titulo: Editar a documentação
resumo: Criar páginas, editar cópias do manual oficial, pré-visualizar, publicar e enviar imagens.
ordem: 10
permissao: documentacao.editar
telas: [documentacao-admin, documentacao-editor]
palavras_chave: [editar, manual, rascunho, publicar, markdown]
---

Quem tem a permissão de editar a documentação (por padrão, os grupos Super Admin, Admin e Developer) vê os
botões **Editar esta página** e **Editar documentação** na tela [Documentação](/documentacao).

## Manual oficial, cópia e página nova

| Tipo | O que é |
|---|---|
| **Manual oficial** | as páginas que vêm com o sistema. Não são alteradas diretamente |
| **Cópia editada** | uma cópia de uma página oficial. Depois de publicada, aparece **no lugar** da oficial |
| **Criada aqui** | página nova, que não existe no manual oficial |

**Editar esta página**, numa página oficial, cria a cópia como rascunho e abre o editor.

## Rascunho e publicação

- Toda página nova ou cópia nasce como **rascunho**. Só quem edita vê o rascunho (com o selo **Rascunho**);
  os demais continuam vendo a versão publicada, ou não veem nada se a página for nova.
- **Salvar rascunho** (ou **Ctrl+S**) grava sem publicar.
- **Publicar** coloca a página no ar para todos. Se houver alterações não salvas, ela salva antes.
- **Despublicar** volta a página para rascunho; numa cópia, os leitores voltam a ver a versão oficial.
- **Excluir** apaga a página. Numa cópia, a versão oficial volta a valer.

## Dados da página

| Campo | Para que serve |
|---|---|
| **Título** e **Resumo** | aparecem no índice, na busca e na ajuda |
| **Endereço** | `secao` para a capa de uma seção, `secao/pagina` para uma página. Ex.: `agenda/encaixes` |
| **Ordem** | posição no índice (menor primeiro; padrão 100) |
| **Permissão** | só quem tem a permissão vê a página (ex.: `documentacao.editar`) |
| **Telas da ajuda** | o botão **?** dessas telas abre esta página (ex.: `agenda, agenda-todos`) |
| **Palavras-chave** | termos extras para a busca |

## Escrever

O texto usa **Markdown**, e a **Pré-visualização** ao lado mostra o resultado:

- `## Título` e `### Subtítulo` formam o sumário da página;
- `**negrito**`, listas com `-` ou `1.`, tabelas com `|`;
- link para outra página: `[texto](../secao/pagina.md)`; para uma tela do sistema: `[Agenda](/agenda)`;
- os botões **Dica** e **Atenção** inserem os quadros de aviso.

## Imagens

Preencha o **Endereço** e use **Imagem**: o arquivo é enviado e o código da imagem entra no texto. Troque
"Descreva a imagem" por uma descrição do que ela mostra. Imagens com mais de 1600 px de largura são reduzidas.

> [!ATENCAO]
> Use só dados fictícios nas capturas de tela. Nunca mostre pacientes, tutores ou valores reais.

## Avisos

Ao salvar, o sistema pode mostrar **avisos** que não impedem a gravação: um link em formato não aceito, uma
imagem sem descrição ou uma tela que já abre outra página na ajuda. Vale corrigir antes de publicar.
