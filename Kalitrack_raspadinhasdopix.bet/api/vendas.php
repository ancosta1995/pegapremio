<?php
require 'db.php';
header('Content-Type: application/json');

$sql_pendentes = "SELECT COUNT(*) as total_pendentes FROM transactions WHERE status = 'pendente'";
$stmt = $pdo->query($sql_pendentes);
$total_pendentes = (int)$stmt->fetchColumn();

$sql_pagas = "SELECT COUNT(*) as total_pagas FROM transactions WHERE status = 'pago'";
$stmt = $pdo->query($sql_pagas);
$total_pagas = (int)$stmt->fetchColumn();

$sql_faturamento = "SELECT 
    COUNT(*) as total_vendas_dia, 
    COALESCE(SUM(amount), 0) as valor_faturado_dia
FROM transactions
WHERE status = 'pago' AND DATE(created_at) = CURDATE()";

$stmt = $pdo->query($sql_faturamento);
$faturamento = $stmt->fetch();

$sql_valor_pendente = "SELECT COALESCE(SUM(amount), 0) as valor_pendente_dia
FROM transactions
WHERE status = 'pendente' AND DATE(created_at) = CURDATE()";
$stmt = $pdo->query($sql_valor_pendente);
$valor_pendente_dia = (float)$stmt->fetchColumn();

echo json_encode([
    'total_vendas_pendentes' => $total_pendentes,
    'total_vendas_pagas' => $total_pagas,
    'total_vendas_dia' => (int)$faturamento['total_vendas_dia'],
    'valor_faturado_dia' => (float)$faturamento['valor_faturado_dia'],
    'valor_pendente_dia' => $valor_pendente_dia,
]);
