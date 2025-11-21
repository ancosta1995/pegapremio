<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$senha = $data['senha'] ?? '';

if (!$username || !$senha) {
    http_response_code(400);
    echo json_encode(['erro' => 'Preencha todos os campos']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $username]);
$user = $stmt->fetch();

if (!$user || !password_verify($senha, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Usuário ou senha inválidos']);
    exit;
}

$_SESSION['user_id'] = $user['id'];
session_regenerate_id(true);
echo json_encode(['sucesso' => true, 'user_id' => $user['id']]);
?>