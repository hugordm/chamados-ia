<?php

function carregar_env(): void
{
    $caminho = dirname(__DIR__) . '/.env';

    if (!file_exists($caminho)) {
        fwrite(STDERR, "Arquivo .env não encontrado. Copie .env.example para .env e preencha as variáveis.\n");
        exit(1);
    }

    foreach (file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
        $linha = trim($linha);

        if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
            continue;
        }

        [$chave, $valor] = explode('=', $linha, 2);
        $valor = trim($valor);

        if (strlen($valor) >= 2 && (
            ($valor[0] === '"' && $valor[-1] === '"') ||
            ($valor[0] === "'" && $valor[-1] === "'")
        )) {
            $valor = substr($valor, 1, -1);
        }

        putenv(trim($chave) . '=' . $valor);
    }
}

carregar_env();
