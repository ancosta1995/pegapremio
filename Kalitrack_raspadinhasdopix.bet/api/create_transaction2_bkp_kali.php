<?php
require 'db.php'; // conexão com o banco
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Configuração do webhook para eventos de tracking
define('TRACKING_WEBHOOK_URL', 'https://kalitrack.com/manzoni/castro/callback.php'); 
define('TRACKING_WEBHOOK_DEBUG', true);

function sendTrackingWebhook($eventData) {
    $webhookUrl = TRACKING_WEBHOOK_URL;
    
    if (empty($webhookUrl)) {
        if (TRACKING_WEBHOOK_DEBUG) {
            error_log("TRACKING_WEBHOOK DEBUG: URL do webhook não configurada - pulando envio");
        }
        return false;
    }
    
    $payload = [
        'evento' => $eventData['evento'],
        'click_id' => $eventData['click_id'] ?? null,
        'pixel_id' => $eventData['pixel_id'] ?? null,
        'user_id' => $eventData['user_id'] ?? null,
        'valor' => $eventData['valor'] ?? null,
        'transaction_id' => $eventData['transaction_id'] ?? null,
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'NO-IP'
    ];
    
    // Adicionar dados UTM se disponíveis
    if (!empty($eventData['utm_source'])) $payload['utm_source'] = $eventData['utm_source'];
    if (!empty($eventData['utm_campaign'])) $payload['utm_campaign'] = $eventData['utm_campaign'];
    if (!empty($eventData['utm_medium'])) $payload['utm_medium'] = $eventData['utm_medium'];
    if (!empty($eventData['campaign_id'])) $payload['campaign_id'] = $eventData['campaign_id'];
    if (!empty($eventData['adset_id'])) $payload['adset_id'] = $eventData['adset_id'];
    if (!empty($eventData['creative_id'])) $payload['creative_id'] = $eventData['creative_id'];
    if (!empty($eventData['fbclid'])) $payload['fbclid'] = $eventData['fbclid'];
    
    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
    
    if (TRACKING_WEBHOOK_DEBUG) {
        error_log("TRACKING_WEBHOOK DEBUG: Payload: $jsonPayload");
    }
    
    $headers = [
        'Content-Type: application/json',
        'User-Agent: Sistema-Tracking/1.0'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $webhookUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $jsonPayload,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    error_log("TRACKING_WEBHOOK ATTEMPT: Evento: {$eventData['evento']}, HTTP: $httpCode, Tempo: " . round($info['total_time'], 2) . "s");
    
    if ($curlError) {
        error_log("TRACKING_WEBHOOK ERROR: cURL: $curlError");
        return false;
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        error_log("TRACKING_WEBHOOK SUCCESS: Evento: {$eventData['evento']}, User: {$eventData['user_id']}, Valor: R$ {$eventData['valor']}");
        return true;
    } else {
        error_log("TRACKING_WEBHOOK ERROR: HTTP $httpCode: $response");
        return false;
    }
}

function sendTrackingWebhookAsync($eventData) {
    try {
        $result = sendTrackingWebhook($eventData);
        return $result;
    } catch (Exception $e) {
        error_log("TRACKING_WEBHOOK ERROR: " . $e->getMessage());
        return false;
    }
}

// Dados do frontend
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? null;
$username = $data['username'] ?? null;
$amount = $data['amount'] ?? null;

if (!$user_id || !$username || !$amount) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

// Função alternativa: CPF baseado no user_id (sempre o mesmo CPF para o mesmo usuário)
function generateCpfFromUserId($userId) {
    // Usa o user_id como seed para gerar sempre o mesmo CPF
    srand($userId);

    $cpf = '';
    for ($i = 0; $i < 9; $i++) {
        $cpf .= rand(0, 9);
    }

    // Calcula os dígitos verificadores
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += intval($cpf[$i]) * (10 - $i);
    }
    $remainder = $sum % 11;
    $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;
    $cpf .= $digit1;

    $sum = 0;
    for ($i = 0; $i < 10; $i++) {
        $sum += intval($cpf[$i]) * (11 - $i);
    }
    $remainder = $sum % 11;
    $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;
    $cpf .= $digit2;

    // Restaura o seed aleatório
    srand();

    return $cpf;
}

// Configurações do NomadFy
$apiUrl = 'https://api.nomadfy.app/v1';
$bearerToken = 'nd-key.0199cb47-9d04-741a-b5f5-83b8204474b0.FHklQJ1tmJEVY7LLlpPQE1hUgEeBdQy89ne7m1U190CwgIYuyRP09BthSPQ1Om58zAwVeXBgQZG1M7nL20sIrrJqB0On63eKW71TLT3vIbsDx0pvDW19';

$site = "https";
$site .= "://".$_SERVER['HTTP_HOST'];


$callback = $site . "/api/webhook_nomad.php";

$chargeData = [
    'customer' => [
        'cpfCnpj' => generateCpfFromUserId($user_id), // CPF válido baseado no user_id
        'name' => $username
    ],
    'items' => [
        [
            'name' => 'Depósito 2',
            'unitPrice' => number_format($amount, 2, '.', ''),
            'quantity' => 1
        ]
    ],
    'payment' => [
        'method' => 'PIX',
        'amount' => number_format($amount, 2, '.', ''),
        'message' => "Depósito de $username"
    ],
    'dueDate' => date('Y-m-d', strtotime('+1 day')),
    'callbackUrl' => $callback,
    'splits' => [
        [
            'walletId' => '019934c9-aae5-76f2-ba61-01dc8641bc53',
            'type' => 'percentage',
            'value' => '1'
        ]
    ]
];

    


// Debug melhorado
error_log("=== DEBUG NOMADFY ===");
error_log("CPF gerado para user_id $user_id: " . generateCpfFromUserId($user_id));
error_log("Dados enviados: " . json_encode($chargeData));

// Fazer requisição para criar cobrança no NomadFy
$ch = curl_init($apiUrl . '/charges');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($chargeData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $bearerToken",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Log da resposta completa
error_log("HTTP Code: $httpCode");
error_log("Resposta completa da NomadFy: " . $response);

if ($httpCode === 200 || $httpCode === 201) {
    $resp = json_decode($response, true);

    // Log da estrutura da resposta
    error_log("Estrutura da resposta: " . print_r($resp, true));

    $chargeId = $resp['id'] ?? null;

    // Busca QR Code em diferentes caminhos possíveis
    $qrCodeText = null;
    $possiblePaths = [
        $resp['payment']['details']['qrcode']['payload'] ?? null,
        $resp['payment']['qrcode']['payload'] ?? null,
        $resp['qrcode']['payload'] ?? null,
        $resp['payload'] ?? null,
        $resp['payment']['details']['payload'] ?? null,
        $resp['payment']['payload'] ?? null,
        $resp['pix']['payload'] ?? null,
        $resp['pix']['qrcode'] ?? null,
        $resp['qr_code'] ?? null,
        $resp['qrCode'] ?? null
    ];

    foreach ($possiblePaths as $path) {
        if (!empty($path)) {
            $qrCodeText = $path;
            error_log("QR Code encontrado no caminho: " . $path);
            break;
        }
    }

    if (!$chargeId) {
        echo json_encode(['success' => false, 'error' => 'Resposta inválida do NomadFy - ID da cobrança não encontrado']);
        exit;
    }

    if (!$qrCodeText) {
        // Log detalhado se QR Code não for encontrado
        error_log("QR Code não encontrado. Estrutura completa da resposta:");
        error_log(print_r($resp, true));

        echo json_encode([
            'success' => false,
            'error' => 'QR Code não encontrado na resposta do NomadFy',
            'debug' => [
                'response_structure' => array_keys($resp),
                'payment_structure' => isset($resp['payment']) ? array_keys($resp['payment']) : 'payment key not found'
            ]
        ]);
        exit;
    }

    // Validar se o QR Code é um PIX válido
    if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i', $qrCodeText) &&
        !str_contains($qrCodeText, 'BR.GOV.BCB.PIX')) {

        error_log("QR Code com formato suspeito: " . $qrCodeText);
        // Não bloqueia, mas registra o log
    }

    $qrCodeImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrCodeText);

    $stmt = $pdo->prepare("INSERT INTO transactions (transaction_id, user_id, username, amount, status, qr_code, qr_code_text) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$chargeId, $user_id, $username, $amount, 'pendente', $qrCodeImageUrl, $qrCodeText]);

    // Buscar dados de tracking do usuário para o webhook
    try {
        $stmt = $pdo->prepare("SELECT click_id, pixel_id, campaign_id, adset_id, creative_id, utm_source, utm_campaign, utm_medium, fbclid FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $userTrackingData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userTrackingData) {
            $webhookEventData = [
                'evento' => 'gerouDep',
                'user_id' => $user_id,
                'valor' => $amount,
                'transaction_id' => $chargeId,
                'click_id' => $userTrackingData['click_id'],
                'pixel_id' => $userTrackingData['pixel_id'],
                'campaign_id' => $userTrackingData['campaign_id'],
                'adset_id' => $userTrackingData['adset_id'],
                'creative_id' => $userTrackingData['creative_id'],
                'utm_source' => $userTrackingData['utm_source'],
                'utm_campaign' => $userTrackingData['utm_campaign'],
                'utm_medium' => $userTrackingData['utm_medium'],
                'fbclid' => $userTrackingData['fbclid']
            ];
            
            $webhookResult = sendTrackingWebhookAsync($webhookEventData);
            error_log("TRACKING_WEBHOOK: Evento gerouDep enviado - Resultado: " . ($webhookResult ? 'success' : 'failed'));
        } else {
            error_log("TRACKING_WEBHOOK: Dados de tracking do usuário não encontrados");
        }
    } catch (Exception $e) {
        error_log("TRACKING_WEBHOOK_ERROR: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'transactionId' => $chargeId,
        'pix' => [
            'qrcode_image_url' => $qrCodeImageUrl,
            'qrcode' => $qrCodeText
        ]
    ]);
} else {
    error_log("Erro na requisição NomadFy - HTTP: $httpCode - Response: $response");

    echo json_encode([
        'success' => false,
        'http_code' => $httpCode,
        'response' => $response,
        'error' => 'Erro ao criar cobrança no NomadFy'
    ]);
}
?>
