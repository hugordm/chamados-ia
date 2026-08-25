<?php

require_once __DIR__ . '/env.php';

date_default_timezone_set('America/Recife');

function conectar_banco(): PDO
{
    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT');
    $dbname = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $password = getenv('DB_PASSWORD');

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

    if (getenv('DB_SSL') === 'true') {
        $dsn .= ';sslmode=require';
    }

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("SET TIME ZONE 'America/Recife'");

    return $pdo;
}
