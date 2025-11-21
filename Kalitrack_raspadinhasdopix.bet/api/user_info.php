<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Usuário não autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, username, email, saldo, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['erro' => 'Usuário não encontrado']);
    exit;
}

echo json_encode([
    'id' => $user['id'],
    'username' => $user['username'],
    'email' => $user['email'],
    'saldo' => number_format($user['saldo'], 2, ',', '.'),
    'membro_desde' => date('d/m/Y', strtotime($user['created_at']))
]);
?>