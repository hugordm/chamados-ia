<?php

function carregar_env(): void
{
    $caminho = dirname(__DIR__) . '/.env';

    if (file_exists($caminho)) {
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
    } else {
        // Comportamento esperado em produção (Render, etc.): não há .env em disco,
        // as variáveis já vêm injetadas no ambiente pela plataforma de hospedagem.
        error_log('config/env.php: .env não encontrado, assumindo variáveis já injetadas pelo ambiente.');
    }

    validar_variaveis_obrigatorias();
}

function validar_variaveis_obrigatorias(): void
{
    $obrigatorias = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
    $faltando = [];

    foreach ($obrigatorias as $variavel) {
        $valor = getenv($variavel);
        if ($valor === false || $valor === '') {
            $faltando[] = $variavel;
        }
    }

    if ($faltando !== []) {
        $mensagem = 'Configuração incompleta: defina as variáveis de ambiente '
            . implode(', ', $faltando) . '.';
        error_log($mensagem);
        exit($mensagem);
    }
}

carregar_env();
