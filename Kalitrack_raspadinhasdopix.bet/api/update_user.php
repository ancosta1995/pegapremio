<?php
session_start();
header('Content-Type: application/json');

require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (!$data || !isset($data['username']) || !isset($data['email'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$userId = $_SESSION['user_id'];
$username = trim($data['username']);
$email = trim($data['email']);

try {
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    $stmt->execute([$username, $email, $userId]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
