<?php

require_once __DIR__ . '/../config/auth.php';

iniciar_sessao();
$_SESSION = [];
session_destroy();

header('Location: /login.php');
exit;
