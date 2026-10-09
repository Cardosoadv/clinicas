---
title: Prontuários
parent: Módulos
nav_order: 5
---

# Prontuários
{: .no_toc }

Prontuário eletrônico do paciente, com abas para anamnese, histórico, vacinas, peso, prescrições, evolução clínica e imagens.
{: .fs-5 .fw-300 }

<details open markdown="block">
  <summary>Nesta página</summary>
  {: .text-delta }
1. TOC
{:toc}
</details>

---

## Lista de prontuários (`/prontuarios`)

Lista os pacientes com busca (`?search=`). Clicar abre o prontuário em `/prontuarios/:id`, que começa na aba **Anamnese**.

O cabeçalho do prontuário mostra a foto, os dados do pet e três números:

| Indicador | Cálculo |
|:--|:--|
| Consultas | Quantidade de agendamentos do paciente |
| Imagens | Quantidade de imagens/exames anexados |
| Total investido | Soma dos procedimentos faturados para o paciente |

Todos os dados vêm numa só chamada (`GET prontuarios/pacientes/{id}`): pet, odontograma, evoluções, imagens, vacinas, pesos, prescrições, histórico e estatísticas.

## Abas

### Anamnese

Edita os dados do paciente e da saúde dele:

| Campo | Regra |
|:--|:--|
| Nome * | 2 a 255 caracteres |
| Nascimento * | Data `AAAA-MM-DD` |
| Sexo * | Macho ou Fêmea |
| Espécie * | Canina, Felina ou Exóticos |
| Situação | Ativo, Inativo ou Suspenso |
| Endereço, queixa principal, observações de saúde | Texto livre |
| Foto / Avatar | PNG, JPG/JPEG ou WebP, até 2 MB |

A gravação usa `POST` em multipart (`prontuarios/pacientes/{id}/anamnese`), porque o PHP só preenche `$_FILES` em POST.

### Histórico

Duas listas: os **agendamentos** do paciente (data, serviço, status, observações) e os **procedimentos faturados** (data, serviço, valor, status).

### Vacinas

Registro de vacina aplicada:

| Campo | Regra |
|:--|:--|
| Vacina * | 3 a 255 caracteres |
| Data de aplicação * | Data válida |
| Próxima dose | Opcional — alimenta o bloco "Vacinas a vencer" do [Dashboard](dashboard) (7 dias) e o lembrete por WhatsApp |
| Lote, fabricante, observações | Texto livre |

### Peso

Registro de pesagens (peso, data, observações) com **gráfico de evolução do peso** e tabela do histórico. O peso mais recente é usado como valor inicial na calculadora de dosagem.

### Prescrições

Cria uma receita com **vários itens**. Cada item tem medicamento (obrigatório), dosagem, frequência, duração e via de administração.

- O **veterinário** é preenchido automaticamente: o sistema procura o membro da [Equipe](equipe) ligado ao usuário logado.
- **Histórico de prescrições** com data e emitente.
- **Detalhes / impressão**: abre a receita no layout de impressão ("Receita Simples"), com cabeçalho e rodapé da clínica (templates `report_header` / `report_footer` — veja [Configurações](comunicacao#cabecalho-e-rodape)), nome e CRMV do veterinário e data por extenso. O botão imprime pelo navegador (`window.print()`), o que permite também salvar em PDF.

### Evolução

Notas clínicas com título e descrição, autor (veterinário do usuário logado) e data/hora.

- **Modelos rápidos**: 📋 Consulta, 💉 Vacina, 🔄 Retorno e 🏥 Pós-Cirúrgico preenchem título e um texto inicial.
- **Calculadora de dosagem**: informe peso (kg), dose (mg/kg), concentração (mg/ml ou mg/comprimido) e apresentação (líquido em ml ou comprimido). A conta é:

  ```text
  quantidade = peso × dose ÷ concentração
  ex.: 12 kg × 5 mg/kg ÷ 50 mg/ml = 1,20 ml
  ```

  O resultado pode ser copiado para a área de transferência ou **inserido na evolução** como "Cálculo de Dosagem".
- Evoluções podem ser **editadas** e **excluídas**.

### Imagens

Galeria de exames e fotos com título e data do exame. Formatos: PNG, JPG/JPEG e WebP. Os arquivos ficam em `writable/imagens/{paciente_id}/` e só abrem para usuários logados.

### Odontograma (só API)

O backend guarda um odontograma por registro (`mapa_dentes` em JSON + observações) e devolve o mais recente no prontuário (`PUT prontuarios/pacientes/{id}/odontograma`). Ainda **não há tela** para ele no frontend — é herança da versão odontológica do sistema.

## API

| Método | Rota | Descrição |
|:--|:--|:--|
| GET | `/prontuarios?search=` | Lista de pacientes |
| GET | `/prontuarios/pacientes/{id}` | Prontuário completo |
| POST | `/prontuarios/pacientes/{id}/anamnese` | Salva anamnese (multipart, campo de arquivo `avatar`) |
| PUT | `/prontuarios/pacientes/{id}/odontograma` | Salva odontograma |
| POST | `/prontuarios/pacientes/{id}/evolucoes` | Nova evolução (`titulo`, `descricao`, `veterinario_id` opcional) |
| PUT | `/prontuarios/evolucoes/{id}` | Edita evolução |
| DELETE | `/prontuarios/evolucoes/{id}` | Exclui evolução |
| POST | `/prontuarios/pacientes/{id}/vacinas` | Nova vacina |
| DELETE | `/prontuarios/vacinas/{id}` | Exclui vacina |
| POST | `/prontuarios/pacientes/{id}/pesos` | Nova pesagem |
| DELETE | `/prontuarios/pesos/{id}` | Exclui pesagem |
| POST | `/prontuarios/pacientes/{id}/imagens` | Nova imagem (multipart: `arquivo`, `titulo`, `data_exame`) |
| GET | `/prontuarios/pacientes/{id}/imagens/{arquivo}` | Abre a imagem |
| POST | `/prontuarios/pacientes/{id}/prescricoes` | Nova prescrição com `itens[]` |
| GET | `/prontuarios/prescricoes/{id}` | Detalhe da prescrição (com veterinário e CRMV) |
