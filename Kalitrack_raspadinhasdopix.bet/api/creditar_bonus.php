<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Usuário não autenticado']);
    exit;
}

require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$valor = floatval($input['valor'] ?? 0);
$tipo = $input['tipo'] ?? 'bonus_boas_vindas';
$user_id = $_SESSION['user_id'];

if ($valor <= 0) {
    http_response_code(400);
    echo json_encode(['erro' => 'Valor inválido']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("SELECT bonus_recebido FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $ja_recebeu = $stmt->fetchColumn();
    
    if ($ja_recebeu == 1) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['erro' => 'Bônus já foi resgatado anteriormente']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE users SET saldo = saldo + ?, bonus_recebido = 1 WHERE id = ?");
    $stmt->execute([$valor, $user_id]);
    
    $stmt = $pdo->prepare("SELECT saldo FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $novo_saldo = $stmt->fetchColumn();
    
    $pdo->commit();
    
    echo json_encode([
        'sucesso' => true,
        'valor_creditado' => $valor,
        'novo_saldo' => number_format($novo_saldo, 2, '.', ''),
        'mensagem' => 'Bônus creditado com sucesso!'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
?>