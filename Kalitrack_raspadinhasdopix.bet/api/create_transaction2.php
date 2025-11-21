<?php
require 'db.php'; // conexão com o banco
require '../dino/track.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");



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
function generateCpfFromUserId($userId)
{
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

// Configurações da Paymaker
require_once 'paymaker_config.php';

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

// Verificar se as chaves da Paymaker estão configuradas
if (!isPaymakerConfigured()) {
    error_log("ERRO: Chaves da Paymaker não configuradas");
    echo json_encode(['success' => false, 'error' => 'Serviço de pagamento não configurado']);
    exit;
}

$apiUrl = PAYMAKER_API_URL;

$site = "https";
$site .= "://" . $_SERVER['HTTP_HOST'];
$callback = $site . "/api/webhook_pay.php";

// Gerar email e telefone válidos
$validEmail = 'user' . $user_id . '@tempmail.com';
$validPhone = '11999999999';
$validCpf = generateCpfFromUserId($user_id);

// Validações básicas
if (empty($username) || $amount <= 0) {
    error_log("ERRO: Dados inválidos - Username: '$username', Amount: $amount");
    echo json_encode(['success' => false, 'error' => 'Dados inválidos fornecidos']);
    exit;
}

$transactionData = [
    'name' => $username,
    'email' => $validEmail,
    'tel' => $validPhone,
    'document' => $validCpf,
    'payType' => 'PIX',
    'installments' => 0,
    'cardId' => 'string',
    'transAmt' => intval($amount * 100), // Converter para centavos
    'trans_utm_query' => 'string',
    'product' => [
        'pro_name' => 'Depósito Castro 2',
        'pro_text' => 'Depósito para conta Castro.',
        'pro_category' => 'Depósito',
        'pro_email' => 'suporte@castro.com',
        'pro_phone' => '+55 11 999999999',
        'pro_days_warranty' => 1,
        'pro_delivery_type' => 'Digital',
        'pro_text_email' => 'Seu depósito foi processado!',
        'pro_site' => ''
    ],
    'address_cep' => '04567-000',
    'trans_webhook_url' => $callback,
    'address_street' => 'Av. Brigadeiro Faria Lima',
    'address_number' => '1234',
    'address_district' => 'Itaim Bibi',
    'address_city' => 'São Paulo',
    'address_state' => 'SP',
    'address_country' => 'Brasil',
    'address_complement' => 'Conjunto'
];




// Debug melhorado
error_log("=== DEBUG PAYMAKER ===");
error_log("CPF gerado para user_id $user_id: " . generateCpfFromUserId($user_id));
error_log("Dados enviados: " . json_encode($transactionData));

// Fazer requisição para criar transação na Paymaker
$authorization = getPaymakerAuthHeader();
$ch = curl_init($apiUrl . '/api/transactions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transactionData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: " . $authorization,
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Log da resposta completa
error_log("HTTP Code: $httpCode");
error_log("Resposta completa da Paymaker: " . $response);

if ($httpCode === 200 || $httpCode === 201) {
    $resp = json_decode($response, true);

    // Log da estrutura da resposta
    error_log("Estrutura da resposta: " . print_r($resp, true));

    // Paymaker retorna trans_id como ID da transação
    $transactionId = $resp['trans_id'] ?? $resp['pay_id'] ?? $resp['transaction_id'] ?? $resp['id'] ?? null;

    // Paymaker retorna o código PIX em pay_codepix
    $qrCodeText = $resp['pay_codepix'] ?? null;
    
    if ($qrCodeText) {
        error_log("QR Code encontrado em pay_codepix: " . substr($qrCodeText, 0, 50) . "...");
    }

    if (!$transactionId) {
        echo json_encode(['success' => false, 'error' => 'Resposta inválida da Paymaker - ID da transação não encontrado']);
        exit;
    }

    if (!$qrCodeText) {
        // Log detalhado se QR Code não for encontrado
        error_log("QR Code não encontrado. Estrutura completa da resposta:");
        error_log(print_r($resp, true));

        echo json_encode([
            'success' => false,
            'error' => 'QR Code não encontrado na resposta da Paymaker',
            'debug' => [
                'response_structure' => array_keys($resp),
                'data_structure' => isset($resp['data']) ? array_keys($resp['data']) : 'data key not found'
            ]
        ]);
        exit;
    }

    // Validar se o QR Code é um PIX válido
    if (
        !preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i', $qrCodeText) &&
        !str_contains($qrCodeText, 'BR.GOV.BCB.PIX')
    ) {

        error_log("QR Code com formato suspeito: " . $qrCodeText);
        // Não bloqueia, mas registra o log
    }

    $qrCodeImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrCodeText);

    $stmt = $pdo->prepare("INSERT INTO transactions (transaction_id, user_id, username, amount, status, qr_code, qr_code_text) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$transactionId, $user_id, $username, $amount, 'pendente', $qrCodeImageUrl, $qrCodeText]);

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
                'transaction_id' => $transactionId,
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
        'transactionId' => $transactionId,
        'pix' => [
            'qrcode_image_url' => $qrCodeImageUrl,
            'qrcode' => $qrCodeText
        ]
    ]);
} else {
    error_log("Erro na requisição Paymaker - HTTP: $httpCode - Response: $response");

    echo json_encode([
        'success' => false,
        'http_code' => $httpCode,
        'response' => $response,
        'error' => 'Erro ao criar transação na Paymaker'
    ]);
}
?>
