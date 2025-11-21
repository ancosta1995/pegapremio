<?php
header('Content-Type: application/json');
require 'db.php';

$user_id = $_GET['id'] ?? null;

if (!$user_id || !is_numeric($user_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetro id inválido ou ausente']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM transactions WHERE user_id = ? AND status = 'pago'");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['user_id' => (int)$user_id, 'total_deposits' => (int)$result['total']]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao consultar o banco de dados']);
}
