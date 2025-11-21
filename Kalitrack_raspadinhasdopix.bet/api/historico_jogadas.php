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

$stmt = $pdo->prepare("
    SELECT 
        valor_aposta, 
        valor_ganho, 
        data_hora 
    FROM jogadas 
    WHERE user_id = ? 
    ORDER BY data_hora DESC
    LIMIT 50
");
$stmt->execute([$user_id]);
$jogadas = $stmt->fetchAll();

echo json_encode(['jogadas' => $jogadas]);
