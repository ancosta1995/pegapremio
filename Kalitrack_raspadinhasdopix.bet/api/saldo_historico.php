<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit('Usuário não logado');
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT saldo FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$saldo = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT valor_aposta, valor_ganho, data_hora FROM jogadas WHERE user_id = ? ORDER BY data_hora DESC");
$stmt->execute([$user_id]);
$jogadas = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode([
    'saldo' => $saldo,
    'historico' => $jogadas
]);
