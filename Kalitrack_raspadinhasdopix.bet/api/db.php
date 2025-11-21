<?php
$host = 'localhost';
$db = 'cr_raspsdopix';
$user = 'cr_raspsdopix';
$pass = 'KXTy6nCn2kR7E8sF';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('Erro ao conectar: ' . $e->getMessage());
}

date_default_timezone_set('America/Sao_Paulo');
$pdo->exec("SET time_zone = '-03:00'");

function formatarDataBrasil($data)
{
    if (!$data)
        return '-';
    return date('d/m/Y H:i:s', strtotime($data));
}
