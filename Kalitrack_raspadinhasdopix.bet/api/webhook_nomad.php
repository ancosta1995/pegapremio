<?php

$dados = file_get_contents("php://input");
file_put_contents("logs_post.txt", date("Y-m-d H:i:s") . " - " . $dados . "\n", FILE_APPEND);


require 'db.php';
require '../dino/track.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');



$timestamp = date('Y-m-d H:i:s');
$method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'NO-USER-AGENT';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'NO-IP';

error_log("=== WEBHOOK DEBUG [$timestamp] ===");
error_log("Método: $method | IP: $ip | User-Agent: $userAgent");

$rawPayload = file_get_contents("php://input");
error_log("Payload RAW: " . $rawPayload);

error_log("Headers recebidos:");
foreach (getallheaders() as $name => $value) {
    error_log("  $name: $value");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("ERRO: Método $method não permitido");
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido', 'method' => $method]);
    exit;
}

if (!$rawPayload) {
    error_log("ERRO: Payload vazio");
    http_response_code(400);
    echo json_encode(['error' => 'Payload vazio']);
    exit;
}

$data = json_decode($rawPayload, true);
if (!$data) {
    error_log("ERRO: JSON inválido - " . json_last_error_msg());
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido: ' . json_last_error_msg()]);
    exit;
}

error_log("JSON decodificado com sucesso: " . print_r($data, true));

$paymentId = $data['chargeId'] ?? null;
if (!$paymentId) {
    error_log("ERRO: chargeId ausente no payload");
    error_log("Chaves disponíveis: " . implode(', ', array_keys($data)));
    http_response_code(400);
    echo json_encode(['error' => 'chargeId ausente']);
    exit;
}

error_log("PaymentId encontrado: $paymentId");

$status = $data['status'] ?? null;
error_log("Status recebido: $status");

if ($status !== 'PAID' && $status !== 'REFUNDED') {
    error_log("INFO: Status '$status' não é PAID ou REFUNDED - webhook ignorado");
    echo json_encode(['success' => true, 'message' => "Webhook processado, status é '$status'"]);
    exit;
}

error_log("Status $status confirmado - iniciando processamento...");

try {
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE transaction_id = ?");
    $stmt->execute([$paymentId]);
    $transacao = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Busca no BD para transaction_id '$paymentId': " . ($transacao ? 'ENCONTRADA' : 'NÃO ENCONTRADA'));

    if (!$transacao) {
        error_log("ERRO: Transação '$paymentId' não encontrada no banco");

        $stmt = $pdo->prepare("SELECT transaction_id, status FROM transactions ORDER BY created_at DESC LIMIT 5");
        $stmt->execute();
        $ultimasTransacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Últimas 5 transações no BD: " . print_r($ultimasTransacoes, true));

        http_response_code(404);
        echo json_encode(['error' => 'Transação não encontrada']);
        exit;
    }

    error_log("Transação encontrada: " . print_r($transacao, true));

    if ($status === 'PAID') {

        if ($transacao['status'] === 'pago') {
            error_log("INFO: Transação $paymentId já foi processada anteriormente");
            echo json_encode(['success' => true, 'message' => 'Transação já processada']);
            exit;
        }

        $stmt = $pdo->prepare("SHOW COLUMNS FROM transactions LIKE 'updated_at'");
        $stmt->execute();
        $hasUpdatedAt = $stmt->fetch();

        error_log("Campo updated_at existe na tabela: " . ($hasUpdatedAt ? 'SIM' : 'NÃO'));

        $valorDeposito = floatval($transacao['amount']);
        $valorBonus = floatval($transacao['bonus_amount'] ?? 0);
        $valorTotal = $valorDeposito + $valorBonus;

        error_log("PROCESSAMENTO INICIADO:");
        error_log("  PaymentID: $paymentId");
        error_log("  User ID: {$transacao['user_id']}");
        error_log("  Valor Depósito: R$ $valorDeposito");
        error_log("  Valor Bônus: R$ $valorBonus");
        error_log("  Valor Total: R$ $valorTotal");
        error_log("  Status Atual: {$transacao['status']}");

        $pdo->beginTransaction();
        error_log("Transação do banco iniciada");

        if ($hasUpdatedAt) {
            $stmt = $pdo->prepare("UPDATE transactions SET status = 'pago', updated_at = NOW() WHERE transaction_id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE transactions SET status = 'pago' WHERE transaction_id = ?");
        }
        $resultado1 = $stmt->execute([$paymentId]);
        error_log("UPDATE transactions resultado: " . ($resultado1 ? 'SUCCESS' : 'FAILED'));

        if (!$resultado1) {
            $errorInfo = $stmt->errorInfo();
            error_log("ERRO SQL no UPDATE transactions: " . print_r($errorInfo, true));
            throw new Exception("Erro ao atualizar transação: " . $errorInfo[2]);
        }

        $rowsAffected = $stmt->rowCount();
        error_log("Linhas afetadas no UPDATE transactions: $rowsAffected");

        if ($rowsAffected === 0) {
            error_log("AVISO: Nenhuma linha foi atualizada na tabela transactions");
        }

        $stmt = $pdo->prepare("UPDATE users SET saldo = saldo + ? WHERE id = ?");
        $resultado2 = $stmt->execute([$valorTotal, $transacao['user_id']]);
        error_log("UPDATE users resultado: " . ($resultado2 ? 'SUCCESS' : 'FAILED'));

        if (!$resultado2) {
            $errorInfo = $stmt->errorInfo();
            error_log("ERRO SQL no UPDATE users: " . print_r($errorInfo, true));
            throw new Exception("Erro ao atualizar saldo: " . $errorInfo[2]);
        }

        $userRowsAffected = $stmt->rowCount();
        error_log("Linhas afetadas no UPDATE users: $userRowsAffected");

        if ($userRowsAffected === 0) {
            error_log("AVISO: Nenhuma linha foi atualizada na tabela users (usuário pode não existir)");
        }

        $stmt = $pdo->prepare("SELECT saldo FROM users WHERE id = ?");
        $stmt->execute([$transacao['user_id']]);
        $novoSaldo = $stmt->fetchColumn();
        error_log("Novo saldo do usuário {$transacao['user_id']}: R$ $novoSaldo");

        $stmt = $pdo->prepare("SELECT status FROM transactions WHERE transaction_id = ?");
        $stmt->execute([$paymentId]);
        $statusAtualizado = $stmt->fetchColumn();
        error_log("Status da transação após UPDATE: $statusAtualizado");

        $pdo->commit();
        error_log("COMMIT realizado com sucesso");

        // Buscar dados de tracking do usuário para o webhook
        try {
            $stmt = $pdo->prepare("SELECT click_id, pixel_id, campaign_id, adset_id, creative_id, utm_source, utm_campaign, utm_medium, fbclid FROM users WHERE id = ?");
            $stmt->execute([$transacao['user_id']]);
            $userTrackingData = $stmt->fetch(PDO::FETCH_ASSOC);

            $eventData = [
                'evento' => 'purchase',
                'user_id' => $transacao['user_id'],
                'valor' => $valorTotal,
                'transaction_id' => $paymentId,
                'click_id' => $userTrackingData['click_id'] ?? null,
                'pixel_id' => $userTrackingData['pixel_id'] ?? null,
                'campaign_id' => $userTrackingData['campaign_id'] ?? null,
                'adset_id' => $userTrackingData['adset_id'] ?? null,
                'creative_id' => $userTrackingData['creative_id'] ?? null,
                'utm_source' => $userTrackingData['utm_source'] ?? null,
                'utm_campaign' => $userTrackingData['utm_campaign'] ?? null,
                'utm_medium' => $userTrackingData['utm_medium'] ?? null,
                'fbclid' => $userTrackingData['fbclid'] ?? null
            ];

            // Enviar evento Purchase para tracking
            $clickIdParaTracking = $userTrackingData['click_id'];

            // Se o usuário atual não tem click_id, buscar o último click_id de uma transação paga
            if (!$clickIdParaTracking) {
                try {
                    $stmtFallback = $pdo->prepare("
                        SELECT 
                            t.id,
                            u.click_id
                        FROM 
                            transactions t
                        JOIN 
                            users u 
                        ON t.user_id = u.id
                        WHERE t.status = 'pago' AND u.click_id IS NOT NULL AND u.click_id != ''
                        ORDER BY 
                            t.id DESC
                        LIMIT 1
                    ");
                    $stmtFallback->execute();
                    $fallbackData = $stmtFallback->fetch(PDO::FETCH_ASSOC);

                    if ($fallbackData && $fallbackData['click_id']) {
                        $clickIdParaTracking = $fallbackData['click_id'];
                        error_log("FALLBACK TRACKING: Usando click_id de transação paga anterior: {$clickIdParaTracking}");
                    }
                } catch (Exception $e) {
                    error_log("FALLBACK TRACKING ERROR: " . $e->getMessage());
                }
            }

            if ($clickIdParaTracking) {
                try {
                    $result = sendTrackingEvent('Purchase', $clickIdParaTracking, $valorDeposito);
                } catch (Exception $e) {
                    error_log("TRACKING ERROR: " . $e->getMessage());
                }
            } else {
                error_log("TRACKING WARNING: Nenhum click_id disponível para tracking");
            }
        } catch (Exception $e) {
            error_log("TRACKING_WEBHOOK_ERROR: " . $e->getMessage());
        }

        error_log("=== WEBHOOK PROCESSADO COM SUCESSO ===");
        error_log("PaymentID: $paymentId | User: {$transacao['user_id']} | Novo saldo: R$ $novoSaldo");

        echo json_encode([
            'success' => true,
            'message' => 'Pagamento processado via webhook',
            'payment_id' => $paymentId,
            'user_id' => $transacao['user_id'],
            'total_credited' => $valorTotal,
            'new_balance' => $novoSaldo,
            'timestamp' => $timestamp,
            'status_updated' => $statusAtualizado
        ]);

    } else if ($status === 'REFUNDED') {

        if ($transacao['status'] === 'reembolsado') {
            error_log("INFO: Transação $paymentId já foi reembolsada anteriormente");
            echo json_encode(['success' => true, 'message' => 'Transação já foi reembolsada']);
            exit;
        }

        $stmt = $pdo->prepare("SHOW COLUMNS FROM transactions LIKE 'updated_at'");
        $stmt->execute();
        $hasUpdatedAt = $stmt->fetch();

        $valorDeposito = floatval($transacao['amount']);
        $valorBonus = floatval($transacao['bonus_amount'] ?? 0);
        $valorTotal = $valorDeposito + $valorBonus;

        error_log("PROCESSAMENTO DE REEMBOLSO INICIADO:");
        error_log("  PaymentID: $paymentId");
        error_log("  User ID: {$transacao['user_id']}");
        error_log("  Valor Total a reembolsar: R$ $valorTotal");
        error_log("  Status Atual: {$transacao['status']}");

        $pdo->beginTransaction();
        error_log("Transação do banco iniciada");

        if ($hasUpdatedAt) {
            $stmt = $pdo->prepare("UPDATE transactions SET status = 'reembolsado', updated_at = NOW() WHERE transaction_id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE transactions SET status = 'reembolsado' WHERE transaction_id = ?");
        }
        $resultado1 = $stmt->execute([$paymentId]);
        error_log("UPDATE transactions para reembolsado resultado: " . ($resultado1 ? 'SUCCESS' : 'FAILED'));

        $stmt = $pdo->prepare("UPDATE users SET saldo = GREATEST(0, saldo - ?) WHERE id = ?");
        $resultado2 = $stmt->execute([$valorTotal, $transacao['user_id']]);
        error_log("UPDATE users saldo (reembolso) resultado: " . ($resultado2 ? 'SUCCESS' : 'FAILED'));

        $stmt = $pdo->prepare("SELECT saldo FROM users WHERE id = ?");
        $stmt->execute([$transacao['user_id']]);
        $novoSaldo = $stmt->fetchColumn();
        error_log("Novo saldo do usuário {$transacao['user_id']}: R$ $novoSaldo");

        $pdo->commit();
        error_log("COMMIT do reembolso realizado com sucesso");

        error_log("=== REEMBOLSO PROCESSADO COM SUCESSO ===");
        error_log("PaymentID: $paymentId | User: {$transacao['user_id']} | Novo saldo: R$ $novoSaldo");

        echo json_encode([
            'success' => true,
            'message' => 'Reembolso processado via webhook',
            'payment_id' => $paymentId,
            'user_id' => $transacao['user_id'],
            'total_refunded' => $valorTotal,
            'new_balance' => $novoSaldo,
            'timestamp' => $timestamp
        ]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollback();
        error_log("ROLLBACK realizado devido ao erro");
    }

    error_log("=== ERRO NO WEBHOOK ===");
    error_log("PaymentID: " . ($paymentId ?? 'unknown'));
    error_log("Erro: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'error' => 'Erro interno do servidor',
        'message' => $e->getMessage(),
        'payment_id' => $paymentId ?? 'unknown'
    ]);
}
?>
