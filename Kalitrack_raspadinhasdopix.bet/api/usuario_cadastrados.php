<?php
require 'db.php';
header('Content-Type: application/json');

$sql = "
    SELECT 
        COUNT(*) as total_cadastros, 
        MAX(created_at) as ultimo_cadastro,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as total_cadastros_hoje
    FROM users
";

$stmt = $pdo->query($sql);
$result = $stmt->fetch();

echo json_encode([
    'total_cadastros' => (int)$result['total_cadastros'],
    'ultimo_cadastro' => $result['ultimo_cadastro'],
    'total_cadastros_hoje' => (int)$result['total_cadastros_hoje'],
]);
