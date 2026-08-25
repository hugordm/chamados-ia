<?php

require_once __DIR__ . '/../config/auth.php';

iniciar_sessao();

$usuario = usuario_logado();

header('Location: ' . ($usuario !== null ? '/' . $usuario['papel'] . '/index.php' : '/login.php'));
exit;
