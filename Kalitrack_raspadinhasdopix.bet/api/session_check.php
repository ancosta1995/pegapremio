<?php
header('Content-Type: application/json');

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['success' => false]);
    exit;
}

require_once __DIR__ . 'auth_check.php';

echo json_encode([
    'success' => true,
    'data' => [
        'user_id' => $_SESSION['user_id'],
        'saldo' => $_SESSION['user_data']['saldo'] ?? '0,00'
    ]
]);
?>