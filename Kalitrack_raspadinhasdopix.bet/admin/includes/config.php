<?php
$host = 'localhost';
$dbname = 'cr_raspsdopix';
$username = 'cr_raspsdopix';
$password = 'KXTy6nCn2kR7E8sF';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

date_default_timezone_set('America/Sao_Paulo');
$pdo->exec("SET time_zone = '-03:00'");

function formatarDataBrasil($data)
{
    if (!$data)
        return '-';
    return date('d/m/Y H:i:s', strtotime($data));
}
?>