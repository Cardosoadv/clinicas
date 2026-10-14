<?php

declare(strict_types=1);

namespace App\Libraries\Documentacao;

/**
 * Cabeçalho (frontmatter) das páginas da documentação interna (manual do
 * sistema, lido dentro do próprio Clínicas).
 *
 * Formato descrito em docs/documentacao-interna.md. Esta classe é a fonte de
 * verdade; docs/documentacao-pagina.schema.json é só a descrição para editores.
 *
 * O cabeçalho usa um subconjunto pequeno de YAML, lido aqui sem dependência:
 * `chave: valor`, com valor texto (com ou sem aspas), inteiro, lista em linha
 * (`[a, b]`) ou lista em bloco (linhas `  - item`). Linhas que começam com `#`
 * são comentários.
 */
final class Frontmatter
{
    public const CAMPOS = ['titulo', 'resumo', 'ordem', 'permissao', 'telas', 'palavras_chave'];

    public const ORDEM_PADRAO = 100;

    private const RE_PERMISSAO = '/^[a-z_]+(?:\.[a-z_]+)+$/';
    private const RE_TELA      = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
    private const RE_CHAVE     = '/^([a-z_]+):(?:\s+(.*))?$/';

    /**
     * Separa o cabeçalho do corpo Markdown.
     *
     * @return array{0: array<string, mixed>, 1: string} [cabeçalho, corpo]
     *
     * @throws FrontmatterInvalido Sem cabeçalho ou com linha fora do formato.
     */
    public static function separar(string $conteudo): array
    {
        $conteudo = str_replace("\r\n", "\n", $conteudo);
        $conteudo = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo) ?? $conteudo;

        if (!preg_match('/\A---\n(.*?)\n---(?:\n|\z)/s', $conteudo, $m)) {
            throw new FrontmatterInvalido(['frontmatter' => 'A página deve começar com o cabeçalho entre linhas "---".']);
        }

        return [self::ler($m[1]), (string) substr($conteudo, strlen($m[0]))];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws FrontmatterInvalido
     */
    public static function ler(string $yaml): array
    {
        $dados = [];
        $erros = [];
        $listaAberta = null;

        foreach (explode("\n", $yaml) as $i => $linha) {
            $n = $i + 1;
            if (trim($linha) === '' || str_starts_with(ltrim($linha), '#')) {
                continue;
            }

            if ($listaAberta !== null && preg_match('/^\s+-\s+(.*)$/', $linha, $m)) {
                $dados[$listaAberta][] = self::escalar($m[1]);
                continue;
            }
            $listaAberta = null;

            if (!preg_match(self::RE_CHAVE, $linha, $m)) {
                $erros["linha {$n}"] = 'Linha fora do formato "chave: valor".';
                continue;
            }

            $chave = $m[1];
            $valor = trim($m[2] ?? '');
            if (array_key_exists($chave, $dados)) {
                $erros[$chave] = 'Campo repetido.';
                continue;
            }

            if ($valor === '') {
                $dados[$chave] = [];
                $listaAberta = $chave;
            } elseif (str_starts_with($valor, '[')) {
                if (!str_ends_with($valor, ']')) {
                    $erros[$chave] = 'Lista sem "]" de fechamento.';
                    continue;
                }
                $itens = trim(substr($valor, 1, -1));
                $dados[$chave] = $itens === '' ? [] : array_map(self::escalar(...), str_getcsv($itens, ',', '"', ''));
            } else {
                $dados[$chave] = self::escalar($valor);
            }
        }

        if ($erros !== []) {
            throw new FrontmatterInvalido($erros);
        }

        return $dados;
    }

    private static function escalar(string $valor): int|string
    {
        $valor = trim($valor);
        if (preg_match('/^-?\d+$/', $valor)) {
            return (int) $valor;
        }
        if (strlen($valor) >= 2 && ($valor[0] === '"' || $valor[0] === "'") && $valor[-1] === $valor[0]) {
            $interno = substr($valor, 1, -1);

            return $valor[0] === '"' ? stripcslashes($interno) : str_replace("''", "'", $interno);
        }

        return $valor;
    }

    /**
     * Valida um cabeçalho já lido.
     *
     * @param array<string, mixed> $dados
     *
     * @return array<string, string> Erros por campo; vazio quando válido.
     */
    public static function validar(array $dados): array
    {
        $erros = [];

        foreach (array_diff(array_keys($dados), self::CAMPOS) as $desconhecido) {
            $erros[$desconhecido] = 'Campo desconhecido.';
        }

        $titulo = $dados['titulo'] ?? null;
        if (!is_string($titulo) || trim($titulo) === '') {
            $erros['titulo'] = 'Obrigatório.';
        } elseif (mb_strlen($titulo) > 120) {
            $erros['titulo'] = 'Até 120 caracteres.';
        }

        if (array_key_exists('resumo', $dados) && (!is_string($dados['resumo']) || mb_strlen($dados['resumo']) > 300)) {
            $erros['resumo'] = 'Texto de até 300 caracteres.';
        }

        if (array_key_exists('ordem', $dados) && (!is_int($dados['ordem']) || $dados['ordem'] < 0 || $dados['ordem'] > 9999)) {
            $erros['ordem'] = 'Inteiro entre 0 e 9999.';
        }

        if (array_key_exists('permissao', $dados) && (!is_string($dados['permissao']) || !preg_match(self::RE_PERMISSAO, $dados['permissao']))) {
            $erros['permissao'] = 'Permissão no formato "escopo.acao" (ex.: documentacao.editar).';
        }

        self::lista($dados, 'telas', self::RE_TELA, 'Cada tela é um identificador de tela do frontend (minúsculas, números e hífen; ver telas.ts).', $erros);
        self::lista($dados, 'palavras_chave', null, 'Cada palavra-chave é um texto de até 60 caracteres.', $erros);

        return $erros;
    }

    /**
     * @param array<string, mixed>  $dados
     * @param array<string, string> $erros
     */
    private static function lista(array $dados, string $campo, ?string $regex, string $mensagem, array &$erros): void
    {
        if (!array_key_exists($campo, $dados)) {
            return;
        }
        if (!is_array($dados[$campo]) || !array_is_list($dados[$campo])) {
            $erros[$campo] = 'Deve ser uma lista.';

            return;
        }
        foreach ($dados[$campo] as $i => $item) {
            $valido = is_string($item) && $item !== '' && mb_strlen($item) <= 60
                && ($regex === null || preg_match($regex, $item));
            if (!$valido) {
                $erros["{$campo}.{$i}"] = $mensagem;
            }
        }
        if (count(array_unique($dados[$campo])) !== count($dados[$campo])) {
            $erros[$campo] = 'Itens repetidos.';
        }
    }
}
