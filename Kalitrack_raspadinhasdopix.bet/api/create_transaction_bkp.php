<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'SecurityHelper.php';
require_once 'db.php';
require '../dino/track.php';


try {
    $payload = SecurityHelper::protectEndpoint([
        'endpoint' => 'create_pix_transaction',
        'rate_limit' => ['max' => 15, 'window' => 300]
    ]);

    $authenticated_user = $payload;

} catch (Exception $e) {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    SecurityHelper::sendError(405, 'METHOD_NOT_ALLOWED', 'Apenas método POST é permitido');
}

$user_ip = SecurityHelper::getUserIP();

if (!SecurityHelper::checkRateLimit("pix_creation_{$user_ip}", 300, 300)) {
    SecurityHelper::sendError(429, 'PIX_RATE_LIMIT', 'Muitas tentativas de criar PIX. Aguarde 5 minutos.');
}


$rawInput = file_get_contents("php://input");
if (empty($rawInput)) {
    SecurityHelper::sendError(400, 'EMPTY_REQUEST', 'Dados da requisição estão vazios');
}

$data = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    SecurityHelper::sendError(400, 'INVALID_JSON', 'JSON inválido: ' . json_last_error_msg());
}

$data = SecurityHelper::sanitizeData($data);

$validationRules = [
    'user_id' => [
        'required' => true,
        'type' => 'integer',
        'min_value' => 1
    ],
    'username' => [
        'required' => true,
        'type' => 'username',
        'min_length' => 2,
        'max_length' => 100
    ],
    'amount' => [
        'required' => true,
        'type' => 'float',
        'min_value' => 4.00,
        'max_value' => 10000.00
    ],
    'email' => [
        'required' => false,
        'type' => 'email',
        'max_length' => 255
    ],
    'phone' => [
        'required' => false,
        'type' => 'phone',
        'max_length' => 20
    ]
];

$validationErrors = SecurityHelper::validateInput($data, $validationRules);
if (!empty($validationErrors)) {
    SecurityHelper::sendError(400, 'VALIDATION_ERROR', 'Dados inválidos', $validationErrors);
}

$user_id = intval($data['user_id']);
$username = trim($data['username']);
$amount = floatval($data['amount']);
$user_email = trim($data['email'] ?? '');
$user_phone = trim($data['phone'] ?? '');

if ($amount < 4.00 || $amount > 10000.00) {
    SecurityHelper::sendError(400, 'AMOUNT_OUT_OF_RANGE', 'Valor deve estar entre R$ 10,00 e R$ 10.000,00');
}

function getUserIP()
{
    return SecurityHelper::getUserIP();
}

function checkPixRateLimit($ip, $user_id, $pdo)
{
    try {
        $max_pix_por_ip_hora = 12;
        $max_pix_por_usuario_hora = 12;
        $max_pix_por_usuario_dia = 20;
        $tempo_bloqueio = 1800;

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS rate_limit_pix (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                user_id INT,
                pix_gerados INT DEFAULT 1,
                primeira_geracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ultima_geracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                bloqueado_ate TIMESTAMP NULL,
                tipo_limite ENUM('ip', 'usuario') NOT NULL,
                UNIQUE KEY unique_ip_user (ip_address, user_id, tipo_limite),
                INDEX idx_bloqueio (ip_address, user_id, bloqueado_ate)
            )
        ");

        $stmt = $pdo->prepare("
            SELECT bloqueado_ate FROM rate_limit_pix
            WHERE ip_address = ? AND tipo_limite = 'ip' AND bloqueado_ate > NOW()
        ");
        $stmt->execute([$ip]);
        if ($stmt->fetch()) {
            return ['allowed' => false, 'reason' => 'IP temporariamente bloqueado por excesso de PIX gerados'];
        }

        $stmt = $pdo->prepare("
            SELECT bloqueado_ate FROM rate_limit_pix
            WHERE user_id = ? AND tipo_limite = 'usuario' AND bloqueado_ate > NOW()
        ");
        $stmt->execute([$user_id]);
        if ($stmt->fetch()) {
            return ['allowed' => false, 'reason' => 'Usuário temporariamente bloqueado por excesso de PIX gerados'];
        }

        $stmt = $pdo->prepare("
            SELECT pix_gerados FROM rate_limit_pix
            WHERE ip_address = ? AND tipo_limite = 'ip'
            AND primeira_geracao > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$ip]);
        $registro_ip = $stmt->fetch();

        if ($registro_ip && $registro_ip['pix_gerados'] >= $max_pix_por_ip_hora) {
            $stmt = $pdo->prepare("
                UPDATE rate_limit_pix
                SET bloqueado_ate = DATE_ADD(NOW(), INTERVAL ? SECOND)
                WHERE ip_address = ? AND tipo_limite = 'ip'
            ");
            $stmt->execute([$tempo_bloqueio, $ip]);

            SecurityHelper::logAPIAccess('create_pix_transaction', 'BLOCKED', "IP $ip bloqueado por rate limit PIX");
            return ['allowed' => false, 'reason' => 'Muitos PIX gerados deste IP. Tente em 30 minutos.'];
        }

        $stmt = $pdo->prepare("
            SELECT pix_gerados FROM rate_limit_pix
            WHERE user_id = ? AND tipo_limite = 'usuario'
            AND primeira_geracao > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$user_id]);
        $registro_user_hora = $stmt->fetch();

        if ($registro_user_hora && $registro_user_hora['pix_gerados'] >= $max_pix_por_usuario_hora) {
            return ['allowed' => false, 'reason' => 'Limite de PIX por hora atingido. Aguarde um pouco.'];
        }

        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM transactions
            WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
        $stmt->execute([$user_id]);
        $pix_dia = $stmt->fetchColumn();

        if ($pix_dia >= $max_pix_por_usuario_dia) {
            return ['allowed' => false, 'reason' => 'Limite diário de PIX atingido.'];
        }

        $stmt = $pdo->prepare("
            SELECT COUNT(*) as pendentes FROM transactions
            WHERE user_id = ? AND status IN ('pendente')
            AND created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)
        ");
        $stmt->execute([$user_id]);
        $pix_pendentes = $stmt->fetchColumn();

        if ($pix_pendentes >= 3) {
            return ['allowed' => false, 'reason' => 'Você tem PIX pendentes. Efetue o pagamento antes de gerar novos.'];
        }

        return ['allowed' => true, 'reason' => ''];

    } catch (PDOException $e) {
        SecurityHelper::logAPIAccess('create_pix_transaction', 'ERROR', 'Erro no rate limit PIX: ' . $e->getMessage());
        return ['allowed' => true, 'reason' => ''];
    }
}


function registrarPixGerado($ip, $user_id, $pdo)
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO rate_limit_pix (ip_address, user_id, pix_gerados, tipo_limite)
            VALUES (?, NULL, 1, 'ip')
            ON DUPLICATE KEY UPDATE
                pix_gerados = pix_gerados + 1,
                ultima_geracao = NOW()
        ");
        $stmt->execute([$ip]);

        $stmt = $pdo->prepare("
            INSERT INTO rate_limit_pix (ip_address, user_id, pix_gerados, tipo_limite)
            VALUES (?, ?, 1, 'usuario')
            ON DUPLICATE KEY UPDATE
                pix_gerados = pix_gerados + 1,
                ultima_geracao = NOW()
        ");
        $stmt->execute([$ip, $user_id]);

    } catch (PDOException $e) {
        SecurityHelper::logAPIAccess('create_pix_transaction', 'ERROR', 'Erro ao registrar PIX: ' . $e->getMessage());
    }
}

define('SMSFUNNEL_WEBHOOK_URL', ' ');
define('SMSFUNNEL_DEBUG', true);

function sendPixGeneratedToSMSFunnel($pixData)
{
    $webhookUrl = SMSFUNNEL_WEBHOOK_URL;

    if (empty($webhookUrl)) {
        SecurityHelper::logAPIAccess('smsfunnel', 'ERROR', 'URL não configurada');
        return false;
    }

    $payload = [
        'name' => $pixData['username'],
        'phone' => $pixData['phone'],
        'email' => $pixData['email'],
        'pix_code' => $pixData['qrcode'],
        'product_name' => 'PIX R$ ' . number_format($pixData['amount'], 2, ',', '.'),
        'product_price' => number_format($pixData['amount'], 2, '.', ''),
        'product_url' => $pixData['pix_url'] ?? '',
        'customized_url' => '',
        'success_url' => ''
    ];

    $payload['transaction_id'] = $pixData['transaction_id'];
    $payload['amount_original'] = $pixData['amount'];
    $payload['event_type'] = 'pix_gerado';

    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

    if (SMSFUNNEL_DEBUG) {
        SecurityHelper::logAPIAccess('smsfunnel', 'DEBUG', "Payload: $jsonPayload");
    }

    $headers = [
        'Content-Type: application/json',
        'User-Agent: Sistema-PIX/1.0'
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $webhookUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $jsonPayload,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    SecurityHelper::logAPIAccess('smsfunnel', 'ATTEMPT', "HTTP: $httpCode, Tempo: " . round($info['total_time'], 2) . "s");

    if ($curlError) {
        SecurityHelper::logAPIAccess('smsfunnel', 'ERROR', "cURL: $curlError");
        return false;
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        SecurityHelper::logAPIAccess('smsfunnel', 'SUCCESS', "User: {$pixData['username']}, Valor: R$ {$pixData['amount']}");
        return true;
    } else {
        SecurityHelper::logAPIAccess('smsfunnel', 'ERROR', "HTTP $httpCode: $response");
        return false;
    }
}

function sendPixToSMSFunnelAsync($pixData)
{
    try {
        $result = sendPixGeneratedToSMSFunnel($pixData);
        return $result;
    } catch (Exception $e) {
        SecurityHelper::logAPIAccess('smsfunnel', 'ERROR', $e->getMessage());
        return false;
    }
}

function createValidEmail($username, $userId)
{
    $cleanUsername = preg_replace(
        '/[^a-zA-Z0-9]/',
        '',
        iconv('UTF-8', 'ASCII//TRANSLIT', $username)
    );

    if (strlen($cleanUsername) < 3) {
        $cleanUsername = 'user' . $userId;
    }

    $cleanUsername = strtolower(substr($cleanUsername, 0, 30));
    return $cleanUsername . '@tempmail.com';
}

function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function formatPhone($basePhone = '11999999999')
{
    $phone = preg_replace('/[^0-9]/', '', $basePhone);

    if (strlen($phone) < 10) {
        $phone = '11999999999';
    }

    return substr($phone, 0, 11);
}

function generateCpfFromUserId($userId)
{
    $seed = abs(crc32($userId));
    mt_srand($seed);

    $cpf = '';
    for ($i = 0; $i < 9; $i++) {
        $cpf .= mt_rand(0, 9);
    }

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

    mt_srand();
    return $cpf;
}

define('NOMADFY_API_URL', 'https://api.nomadfy.app/v1');
define('NOMADFY_BEARER_TOKEN', 'nd-key.0199cb47-9d04-741a-b5f5-83b8204474b0.FHklQJ1tmJEVY7LLlpPQE1hUgEeBdQy89ne7m1U190CwgIYuyRP09BthSPQ1Om58zAwVeXBgQZG1M7nL20sIrrJqB0On63eKW71TLT3vIbsDx0pvDW19');



function testNomadFyConnection()
{
    $ch = curl_init(NOMADFY_API_URL . '/charges');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . NOMADFY_BEARER_TOKEN,
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    SecurityHelper::logAPIAccess('nomadfy_test', 'CONNECTION_TEST', "HTTP: $httpCode, Error: $curlError");

    return ['accessible' => !$curlError && $httpCode > 0];
}

function nomadFyCreateCharge($amount, $username, $cpf, $user_id)
{

    $site = "https";
    $site .= "://" . $_SERVER['HTTP_HOST'];


    $callback = $site . "/api/webhook_nomad.php";

    $chargeData = [
        'customer' => [
            'cpfCnpj' => $cpf,
            'name' => $username
        ],
        'items' => [
            [
                'name' => 'Depósito 1',
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


    SecurityHelper::logAPIAccess('nomadfy_charge', 'REQUEST', "User: $user_id, Valor: R$ $amount");
    SecurityHelper::logAPIAccess('nomadfy_charge', 'DEBUG', "Charge data: " . json_encode($chargeData));





    $ch = curl_init(NOMADFY_API_URL . '/charges');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($chargeData),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . NOMADFY_BEARER_TOKEN,
            "Content-Type: application/json"
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_USERAGENT => 'Sistema-PIX/1.0'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlInfo = curl_getinfo($ch);
    curl_close($ch);

    SecurityHelper::logAPIAccess('nomadfy_charge', 'ATTEMPT', "HTTP: $httpCode, Time: " . round($curlInfo['total_time'], 2) . "s");
    SecurityHelper::logAPIAccess('nomadfy_charge', 'DEBUG', "Full response: $response");

    if ($curlError) {
        SecurityHelper::logAPIAccess('nomadfy_charge', 'ERROR', "cURL Error: $curlError");
        return ['success' => false, 'error' => 'Erro de conexão com NomadFy: ' . $curlError];
    }

    if (empty($response)) {
        SecurityHelper::logAPIAccess('nomadfy_charge', 'ERROR', "Empty response from NomadFy");
        return ['success' => false, 'error' => 'Resposta vazia da NomadFy'];
    }

    if ($httpCode !== 200 && $httpCode !== 201) {
        SecurityHelper::logAPIAccess('nomadfy_charge', 'ERROR', "HTTP $httpCode: $response");
        $errorResponse = json_decode($response, true);
        $errorMessage = $errorResponse['message'] ?? 'Erro desconhecido';
        return [
            'success' => false,
            'error' => "Erro na API NomadFy (HTTP $httpCode): $errorMessage",
            'details' => $errorResponse
        ];
    }

    $resp = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        SecurityHelper::logAPIAccess('nomadfy_charge', 'ERROR', "Invalid JSON response: " . json_last_error_msg());
        return ['success' => false, 'error' => 'Resposta JSON inválida da NomadFy'];
    }

    SecurityHelper::logAPIAccess('nomadfy_charge', 'DEBUG', "Full response structure: " . json_encode($resp));

    $chargeId = $resp['id'] ?? null;
    if (!$chargeId) {
        SecurityHelper::logAPIAccess('nomadfy_charge', 'ERROR', 'ID da cobrança não encontrado na resposta');
        return ['success' => false, 'error' => 'ID da cobrança não encontrado na resposta'];
    }

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
        if (!empty($path) && is_string($path)) {
            $qrCodeText = $path;
            break;
        }
    }

    if (!$qrCodeText) {
        SecurityHelper::logAPIAccess('nomadfy_charge', 'ERROR', "QR Code não encontrado - Response: " . json_encode($resp));
        return ['success' => false, 'error' => 'QR Code não encontrado na resposta da NomadFy'];
    }

    SecurityHelper::logAPIAccess('nomadfy_charge', 'SUCCESS', "Charge ID: $chargeId, QR Code extraído com sucesso");

    return [
        'success' => true,
        'data' => [
            'id' => $chargeId,
            'qrcode' => $qrCodeText,
            'status' => 'WAITING_PAYMENT',
            'message' => 'PIX gerado com sucesso'
        ]
    ];
}

try {
    SecurityHelper::logAPIAccess('create_pix_transaction', 'PROCESSING', "User: $user_id, Valor: R$ $amount");

    $validEmail = !empty($user_email) && isValidEmail($user_email)
        ? $user_email
        : createValidEmail($username, $user_id);

    $validPhone = !empty($user_phone)
        ? formatPhone($user_phone)
        : formatPhone();

    $validCpf = generateCpfFromUserId($user_id);

    if (!isValidEmail($validEmail)) {
        $validEmail = 'user' . $user_id . '@tempmail.com';
    }

    $connectionTest = testNomadFyConnection();
    if (!$connectionTest['accessible']) {
        SecurityHelper::logAPIAccess('create_pix_transaction', 'ERROR', 'NomadFy não acessível');
        SecurityHelper::sendError(500, 'NOMADFY_UNREACHABLE', 'Serviço de pagamento temporariamente indisponível');
    }

    SecurityHelper::logAPIAccess('create_pix_transaction', 'DEBUG', "Usando dados: Email: $validEmail, Phone: $validPhone, CPF: $validCpf");

    $chargeResult = nomadFyCreateCharge($amount, $username, $validCpf, $user_id);

    if (!$chargeResult['success']) {
        SecurityHelper::logAPIAccess('create_pix_transaction', 'ERROR', 'Falha ao criar cobrança: ' . $chargeResult['error']);

        $errorMsg = $chargeResult['error'];
        if (strpos($errorMsg, 'Invalid amount') !== false || strpos($errorMsg, 'amount') !== false) {
            SecurityHelper::sendError(400, 'INVALID_AMOUNT', 'Valor inválido para depósito');
        } elseif (strpos($errorMsg, 'customer') !== false || strpos($errorMsg, 'cpf') !== false) {
            SecurityHelper::sendError(400, 'INVALID_CUSTOMER', 'Dados do cliente inválidos');
        } else {
            SecurityHelper::sendError(500, 'NOMADFY_CHARGE_ERROR', 'Erro ao gerar PIX');
        }
    }

    $chargeData = $chargeResult['data'];
    $transactionId = $chargeData['id'];
    $pixCode = $chargeData['qrcode'];
    $nomadFyStatus = $chargeData['status'];

    $statusMapping = [
        'WAITING_PAYMENT' => 'pendente',
        'waiting_payment' => 'pendente',
        'PAID' => 'pago',
        'paid' => 'pago',
        'EXPIRED' => 'expirado',
        'expired' => 'expirado',
        'CANCELLED' => 'cancelado',
        'cancelled' => 'cancelado',
        'CANCELED' => 'cancelado',
        'canceled' => 'cancelado'
    ];

    $pixStatus = $statusMapping[$nomadFyStatus] ?? 'pendente';

    SecurityHelper::logAPIAccess('create_pix_transaction', 'CHARGE_SUCCESS', "ID: $transactionId, Status NomadFy: $nomadFyStatus, Status Mapeado: $pixStatus");

    if (empty($pixCode)) {
        SecurityHelper::logAPIAccess('create_pix_transaction', 'ERROR', 'Código PIX vazio na resposta');
        SecurityHelper::sendError(500, 'EMPTY_PIX_CODE', 'Código PIX não foi gerado');
    }

    if (strlen($pixCode) < 20) {
        SecurityHelper::logAPIAccess('create_pix_transaction', 'WARNING', 'Código PIX muito curto: ' . $pixCode . ' (length: ' . strlen($pixCode) . ')');
    }

    SecurityHelper::logAPIAccess('create_pix_transaction', 'PIX_CODE_OK', "Código PIX recebido: " . substr($pixCode, 0, 50) . "...");

    $qrCodeImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($pixCode);

    SecurityHelper::logAPIAccess('create_pix_transaction', 'QR_CODE_GENERATED', "URL: $qrCodeImageUrl");

    try {
        SecurityHelper::logAPIAccess('create_pix_transaction', 'DATABASE_ATTEMPT', "Tentando salvar: ID=$transactionId, User=$user_id, Amount=$amount");

        $stmt = $pdo->prepare("SHOW COLUMNS FROM transactions LIKE 'bonus_amount'");
        $stmt->execute();
        $hasBonus = $stmt->fetch();

        if ($hasBonus) {
            $stmt = $pdo->prepare("INSERT INTO transactions (transaction_id, user_id, username, amount, bonus_amount, status, qr_code, qr_code_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $transactionId,
                $user_id,
                $username,
                $amount,
                0,
                $pixStatus,
                $qrCodeImageUrl,
                $pixCode
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO transactions (transaction_id, user_id, username, amount, status, qr_code, qr_code_text) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $transactionId,
                $user_id,
                $username,
                $amount,
                $pixStatus,
                $qrCodeImageUrl,
                $pixCode
            ]);
        }

        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            SecurityHelper::logAPIAccess('create_pix_transaction', 'DATABASE_ERROR', 'Erro SQL: ' . json_encode($errorInfo));
            SecurityHelper::sendError(500, 'DATABASE_ERROR', 'Erro ao salvar transação: ' . $errorInfo[2]);
        }

        SecurityHelper::logAPIAccess('create_pix_transaction', 'DATABASE_SAVED', "Transaction ID: $transactionId salvo com sucesso");

    } catch (PDOException $e) {
        SecurityHelper::logAPIAccess('create_pix_transaction', 'DATABASE_EXCEPTION', 'Erro PDO: ' . $e->getMessage());

        if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
            SecurityHelper::sendError(400, 'DUPLICATE_TRANSACTION', 'Transação já existe no sistema');
        }

        SecurityHelper::sendError(500, 'DATABASE_EXCEPTION', 'Erro de banco de dados: ' . $e->getMessage());
    }

    try {
        registrarPixGerado($user_ip, $user_id, $pdo);
        SecurityHelper::logAPIAccess('create_pix_transaction', 'RATE_LIMIT_REGISTERED', 'Rate limit registrado');
    } catch (Exception $e) {
        SecurityHelper::logAPIAccess('create_pix_transaction', 'RATE_LIMIT_ERROR', $e->getMessage());
    }

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

            // Enviar evento AddToCart para tracking
            if ($userTrackingData['click_id']) {
                try {
                    $result = sendTrackingEvent('AddToCart', $userTrackingData['click_id'], $amount, 'deposito');
                } catch (Exception $e) {
                    SecurityHelper::logAPIAccess('create_pix_transaction', 'TRACKING_ERROR', $e->getMessage());
                }
            }
        } else {
            SecurityHelper::logAPIAccess('create_pix_transaction', 'TRACKING_WEBHOOK', 'Dados de tracking do usuário não encontrados');
        }
    } catch (Exception $e) {
        SecurityHelper::logAPIAccess('create_pix_transaction', 'TRACKING_WEBHOOK_ERROR', $e->getMessage());
    }

    $smsPixData = [
        'transaction_id' => $transactionId,
        'username' => $username,
        'email' => $validEmail,
        'phone' => $validPhone,
        'amount' => $amount,
        'qrcode' => $pixCode,
        'pix_url' => $qrCodeImageUrl
    ];

    SecurityHelper::logAPIAccess('create_pix_transaction', 'SMS_PREPARATION', 'Dados SMS preparados');

    $smsResult = false;
    try {
        $smsResult = sendPixToSMSFunnelAsync($smsPixData);
        SecurityHelper::logAPIAccess('create_pix_transaction', 'SMS_ATTEMPT', "Resultado SMS: " . ($smsResult ? 'success' : 'failed'));
    } catch (Exception $e) {
        SecurityHelper::logAPIAccess('create_pix_transaction', 'SMS_ERROR', $e->getMessage());
    }

    SecurityHelper::logAPIAccess('create_pix_transaction', 'PREPARING_RESPONSE', "Transaction: $transactionId, User: $user_id, Valor: R$ $amount");

    $successResponse = [
        'transactionId' => $transactionId,
        'amountToPay' => $amount,
        'status' => $pixStatus,
        'pix' => [
            'qrcode_image_url' => $qrCodeImageUrl,
            'qrcode' => $pixCode
        ],
        'display_info' => [
            'valor_a_pagar' => 'R$ ' . number_format($amount, 2, ',', '.'),
            'titulo' => "Depositar R$ " . number_format($amount, 2, ',', '.')
        ],
        'sms_funnel_sent' => $smsResult,
        'message' => $chargeData['message']
    ];

    SecurityHelper::logAPIAccess('create_pix_transaction', 'SUCCESS_FINAL', "PIX gerado com sucesso - ID: $transactionId");

    try {
        SecurityHelper::sendSuccess($successResponse, 'PIX gerado com sucesso');
    } catch (Exception $e) {
        SecurityHelper::logAPIAccess('create_pix_transaction', 'SENDSUCESS_ERROR', 'Erro no SecurityHelper::sendSuccess: ' . $e->getMessage());

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'PIX gerado com sucesso',
            'dados' => $successResponse
        ]);
        exit;
    }

} catch (Exception $e) {
    SecurityHelper::logAPIAccess('create_pix_transaction', 'EXCEPTION', $e->getMessage());
    SecurityHelper::sendError(500, 'INTERNAL_ERROR', 'Erro interno do servidor');
}
?>
