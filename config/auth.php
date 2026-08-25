<?php

function iniciar_sessao(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS'] ?? null),
    ]);

    session_start();
}

function usuario_logado(): ?array
{
    iniciar_sessao();

    return $_SESSION['usuario'] ?? null;
}

function exigir_login(): void
{
    iniciar_sessao();

    if (usuario_logado() === null) {
        header('Location: /login.php');
        exit;
    }
}

function papel_confere(array $usuario, string $papel): bool
{
    return $usuario['papel'] === $papel;
}

function exigir_papel(string $papel): void
{
    exigir_login();

    if (!papel_confere($_SESSION['usuario'], $papel)) {
        http_response_code(403);
        echo 'Acesso negado: esta página é exclusiva para o papel "' . htmlspecialchars($papel) . '".';
        exit;
    }
}
