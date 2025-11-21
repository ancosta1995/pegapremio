<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

$timestamp = date('Y-m-d H:i:s');
$method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'NO-USER-AGENT';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'NO-IP';

error_log("=== CHECK STATUS DEBUG [$timestamp] ===");
error_log("Método: $method | IP: $ip | User-Agent: $userAgent");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $transaction_id = $_GET['transaction_id'] ?? null;
} else {
    $input = json_decode(file_get_contents("php://input"), true);
    $transaction_id = $input['transaction_id'] ?? null;
}

if (!$transaction_id) {
    error_log("ERRO: transaction_id ausente");
    echo json_encode(['success' => false, 'error' => 'transaction_id ausente']);
    exit;
}

error_log("Transaction ID recebido: $transaction_id");

try {
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE transaction_id = ?");
    $stmt->execute([$transaction_id]);
    $transacao = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Busca no BD para transaction_id '$transaction_id': " . ($transacao ? 'ENCONTRADA' : 'NÃO ENCONTRADA'));

    if (!$transacao) {
        error_log("ERRO: Transação '$transaction_id' não encontrada no banco");
        echo json_encode(['success' => false, 'message' => 'Transação não encontrada']);
        exit;
    }

    error_log("Transação encontrada: status={$transacao['status']}, amount={$transacao['amount']}");

    $statusMap = [
        'pendente' => 'waiting',
        'pago' => 'paid',
        'cancelado' => 'cancelled',
        'expirado' => 'expired',
        'rejeitado' => 'rejected'
    ];

    $dbStatus = $transacao['status'];
    $mappedStatus = $statusMap[$dbStatus] ?? 'unknown';

    $updatedAt = $transacao['updated_at'] ?? $transacao['created_at'] ?? date('Y-m-d H:i:s');
    $bonusAmount = $transacao['bonus_amount'] ?? 0;

    if ($dbStatus === 'pago') {
        error_log("INFO: Transação $transaction_id já foi processada anteriormente");
        echo json_encode([
            'success' => true,
            'status' => 'paid',
            'message' => 'Pagamento confirmado',
            'transaction_id' => $transaction_id,
            'amount' => $transacao['amount'],
            'bonus_amount' => $bonusAmount,
            'updated_at' => $updatedAt,
            'user_id' => $transacao['user_id']
        ]);
        exit;
    }

    if ($dbStatus === 'pendente') {
        error_log("Status pendente - consultando API Nomad...");

        $apiUrl = 'https://api.nomadfy.app/v1';
        $bearerToken = 'nd-key.0199cb47-9d04-741a-b5f5-83b8204474b0.FHklQJ1tmJEVY7LLlpPQE1hUgEeBdQy89ne7m1U190CwgIYuyRP09BthSPQ1Om58zAwVeXBgQZG1M7nL20sIrrJqB0On63eKW71TLT3vIbsDx0pvDW19';

        $url = "$apiUrl/charges/$transaction_id";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $bearerToken",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        error_log("Consulta API: HTTP $httpCode" . ($curlError ? " | Erro: $curlError" : ""));

        if ($httpCode === 200 && $response && !$curlError) {
            $resp = json_decode($response, true);
            $apiStatus = $resp["status"] ?? null;

            error_log("Status da API: $apiStatus");

            if ($apiStatus === 'PAID') {
                error_log("FALLBACK: Processando pagamento via API...");

                $valorDeposito = $transacao['amount'];
                $valorBonus = $bonusAmount;
                $valorTotal = $valorDeposito + $valorBonus;

                $pdo->beginTransaction();

                try {
                    $stmt = $pdo->prepare("SHOW COLUMNS FROM transactions LIKE 'updated_at'");
                    $stmt->execute();
                    $hasUpdatedAt = $stmt->fetch();

                    if ($hasUpdatedAt) {
                        $stmt = $pdo->prepare("UPDATE transactions SET status = 'pago', updated_at = NOW() WHERE transaction_id = ?");
                    } else {
                        $stmt = $pdo->prepare("UPDATE transactions SET status = 'pago' WHERE transaction_id = ?");
                    }
                    $stmt->execute([$transaction_id]);

                    $stmt = $pdo->prepare("UPDATE users SET saldo = saldo + ? WHERE id = ?");
                    $stmt->execute([$valorTotal, $transacao['user_id']]);

                    $pdo->commit();

                    error_log("FALLBACK: Pagamento processado - Transaction: $transaction_id");

                    echo json_encode([
                        'success' => true,
                        'status' => 'paid',
                        'message' => 'Pagamento confirmado (processado via fallback)',
                        'transaction_id' => $transaction_id,
                        'amount' => $valorDeposito,
                        'bonus_amount' => $valorBonus,
                        'total_credited' => $valorTotal,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    exit;

                } catch (Exception $e) {
                    $pdo->rollback();
                    error_log("ERRO no fallback - Transaction: $transaction_id | Erro: " . $e->getMessage());
                }
            }
        } else {
            error_log("Erro na consulta API - usando status do banco como fallback");
        }
    }

    echo json_encode([
        'success' => true,
        'status' => $mappedStatus,
        'db_status' => $dbStatus,
        'transaction_id' => $transaction_id,
        'amount' => $transacao['amount'],
        'bonus_amount' => $bonusAmount,
        'created_at' => $transacao['created_at'],
        'updated_at' => $updatedAt
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollback();
        error_log("ROLLBACK realizado devido ao erro");
    }

    error_log("=== ERRO NO CHECK STATUS ===");
    error_log("Transaction ID: $transaction_id");
    error_log("Erro: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'error' => $e->getMessage(),
        'transaction_id' => $transaction_id ?? 'unknown'
    ]);
}
?>
