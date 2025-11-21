<?php
session_start();
header('Content-Type: application/json');

require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

$data      = json_decode(file_get_contents('php://input'), true);
$valor     = floatval($data['valor'] ?? 0);
$chave_pix = trim($data['chave_pix'] ?? '');

if ($valor < 10) {
    http_response_code(400);
    echo json_encode(['error' => 'Valor mínimo é R$10,00']);
    exit;
}

if (empty($chave_pix)) {
    http_response_code(400);
    echo json_encode(['error' => 'Chave PIX é obrigatória']);
    exit;
}

$stmt = $pdo->prepare("SELECT saldo, username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || $usuario['saldo'] < $valor) {
    http_response_code(400);
    echo json_encode(['error' => 'Saldo insuficiente']);
    exit;
}

$nomeTitular = $usuario['username'];

$stmt = $pdo->prepare("
    INSERT INTO saques (user_id, valor, chave_pix, nome_titular)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([
    $_SESSION['user_id'],
    $valor,
    $chave_pix,
    $nomeTitular
]);

$stmt = $pdo->prepare("UPDATE users SET saldo = saldo - ? WHERE id = ?");
$stmt->execute([$valor, $_SESSION['user_id']]);

echo json_encode(['success' => true]);
