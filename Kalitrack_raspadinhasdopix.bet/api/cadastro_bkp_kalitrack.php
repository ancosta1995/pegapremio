<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'db.php';
require 'SecurityHelper.php';

$tokenPayload = SecurityHelper::protectEndpoint(['endpoint' => 'cadastro']);

define('SMSFUNNEL_WEBHOOK_URL', ' ');
define('SMSFUNNEL_DEBUG', false);
define('AFILIADO_COLUNA', 'referral_id');

// Configuração do webhook para eventos de tracking
define('TRACKING_WEBHOOK_URL', 'https://kalitrack.com/manzoni/castro/callback.php'); 
define('TRACKING_WEBHOOK_DEBUG', true);

function applyAffiliateToUser($userId, $affiliateCode, $pdo) {
    if (!$affiliateCode || !$userId) {
        return false;
    }
    try {
        $stmt = $pdo->prepare("SELECT id, nome_completo FROM afiliados WHERE codigo_afiliado = ?");
        $stmt->execute([$affiliateCode]);
        $afiliado = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($afiliado) {
            $stmt = $pdo->prepare("UPDATE users SET referral_id = ? WHERE id = ?");
            $stmt->execute([$affiliateCode, $userId]);
            $stmt = $pdo->prepare(
                "INSERT INTO estatisticas_afiliados (afiliado_id, total_indicados)
                 VALUES (?, 1)
                 ON DUPLICATE KEY UPDATE total_indicados = total_indicados + 1, ultima_atualizacao = CURRENT_TIMESTAMP"
            );
            $stmt->execute([$afiliado['id']]);
            error_log("SUCESSO: Usuário $userId vinculado ao afiliado: $affiliateCode ({$afiliado['nome_completo']})");
            return [
                'success' => true,
                'afiliado_id' => $afiliado['id'],
                'afiliado_nome' => $afiliado['nome_completo']
            ];
        }
        error_log("ERRO: Código de afiliado inválido ou não encontrado: $affiliateCode");
        return false;
    } catch (PDOException $e) {
        error_log("ERRO ao aplicar afiliado: " . $e->getMessage());
        return false;
    }
}

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function checkRateLimit($ip, $pdo) {
    try {
        $max_tentativas = 4;
        $janela_tempo = 3600;
        $tempo_bloqueio = 3600;

        $stmt = $pdo->prepare("
            SELECT bloqueado_ate FROM rate_limit_cadastro
            WHERE ip_address = ? AND bloqueado_ate > NOW()
        ");
        $stmt->execute([$ip]);
        $bloqueio = $stmt->fetch();

        if ($bloqueio) {
            error_log("RATE LIMIT: IP $ip ainda bloqueado até {$bloqueio['bloqueado_ate']}");
            return false;
        }

        $stmt = $pdo->prepare("
            SELECT tentativas, primeira_tentativa
            FROM rate_limit_cadastro
            WHERE ip_address = ? AND primeira_tentativa > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$ip, $janela_tempo]);
        $registro = $stmt->fetch();

        if ($registro) {
            $novas_tentativas = $registro['tentativas'] + 1;

            if ($novas_tentativas > $max_tentativas) {
                $stmt = $pdo->prepare("
                    UPDATE rate_limit_cadastro
                    SET tentativas = ?, bloqueado_ate = DATE_ADD(NOW(), INTERVAL ? SECOND)
                    WHERE ip_address = ?
                ");
                $stmt->execute([$novas_tentativas, $tempo_bloqueio, $ip]);

                error_log("RATE LIMIT: IP $ip BLOQUEADO por exceder $max_tentativas tentativas");
                return false;
            } else {
                $stmt = $pdo->prepare("
                    UPDATE rate_limit_cadastro
                    SET tentativas = ?, ultima_tentativa = NOW()
                    WHERE ip_address = ?
                ");
                $stmt->execute([$novas_tentativas, $ip]);

                error_log("RATE LIMIT: IP $ip - tentativa $novas_tentativas/$max_tentativas");
                return true;
            }
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO rate_limit_cadastro (ip_address, tentativas)
                VALUES (?, 1)
                ON DUPLICATE KEY UPDATE
                    tentativas = 1,
                    primeira_tentativa = NOW(),
                    ultima_tentativa = NOW(),
                    bloqueado_ate = NULL
            ");
            $stmt->execute([$ip]);

            error_log("RATE LIMIT: IP $ip - primeira tentativa registrada");
            return true;
        }

    } catch (PDOException $e) {
        error_log("ERRO RATE LIMIT: " . $e->getMessage());
        return true;
    }
}

function registrarCadastroSucesso($ip, $pdo) {
    try {
        $stmt = $pdo->prepare("
            UPDATE rate_limit_cadastro
            SET ultima_tentativa = NOW()
            WHERE ip_address = ?
        ");
        $stmt->execute([$ip]);
    } catch (PDOException $e) {
        error_log("ERRO ao registrar sucesso: " . $e->getMessage());
    }
}

function sendToSMSFunnel($leadData) {
    $webhookUrl = SMSFUNNEL_WEBHOOK_URL;

    if (empty($webhookUrl)) {
        if (SMSFUNNEL_DEBUG) {
            error_log("SMSFUNNEL DEBUG: URL do webhook não configurada - pulando envio");
        }
        return false;
    }

    $payload = [
        'name' => $leadData['username'],
        'phone' => $leadData['phone'],
        'email' => $leadData['email'],
        'pix_code' => '',
        'product_name' => 'Cadastro Sistema',
        'product_price' => '0',
        'product_url' => '',
        'customized_url' => '',
        'success_url' => ''
    ];

    if (!empty($leadData['utm_source'])) {
        $payload['utm_source'] = $leadData['utm_source'];
    }
    if (!empty($leadData['utm_campaign'])) {
        $payload['utm_campaign'] = $leadData['utm_campaign'];
    }
    if (!empty($leadData['affiliate_ref'])) {
        $payload['affiliate_ref'] = $leadData['affiliate_ref'];
    }
    if (!empty($leadData['ip_address'])) {
        $payload['ip_address'] = $leadData['ip_address'];
    }

    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

    $headers = [
        'Content-Type: application/json',
        'User-Agent: Sistema-Cadastro/1.0'
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $webhookUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $jsonPayload,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("SMSFUNNEL ERRO cURL: $curlError");
        return false;
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        error_log("SMSFUNNEL SUCESSO: Lead enviado - HTTP $httpCode - User: {$leadData['username']}");
        return true;
    } else {
        error_log("SMSFUNNEL ERRO HTTP: $httpCode - Response: $response");
        return false;
    }
}

function sendToSMSFunnelAsync($leadData) {
    try {
        $result = sendToSMSFunnel($leadData);
        if ($result) {
            error_log("SMSFUNNEL: Enviado com sucesso para user: {$leadData['username']}");
        } else {
            error_log("SMSFUNNEL: Falha no envio para user: {$leadData['username']} - Cadastro continuou normalmente");
        }
        return $result;
    } catch (Exception $e) {
        error_log("SMSFUNNEL ERRO: " . $e->getMessage() . " - Cadastro continuou normalmente");
        return false;
    }
}

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
        'ip_address' => getUserIP()
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
        error_log("TRACKING_WEBHOOK SUCCESS: Evento: {$eventData['evento']}, User: {$eventData['user_id']}");
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

try {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        SecurityHelper::sendError(400, 'INVALID_JSON', 'Dados JSON inválidos');
    }

    $data = SecurityHelper::sanitizeData($data);

    $validationRules = [
        'username' => [
            'required' => true,
            'type' => 'username',
            'min_length' => 2,
            'max_length' => 60
        ],
        'email' => [
            'required' => true,
            'type' => 'email',
            'max_length' => 80
        ],
        'phone' => [
            'required' => true,
            'type' => 'phone'
        ],
        'senha' => [
            'required' => true,
            'type' => 'string',
            'min_length' => 6,
            'max_length' => 20
        ]
    ];

    $validationErrors = SecurityHelper::validateInput($data, $validationRules);
    if (!empty($validationErrors)) {
        SecurityHelper::sendError(400, 'VALIDATION_ERROR', 'Dados inválidos', $validationErrors);
    }

    $username = $data['username'];
    $email = $data['email'];
    $phone = preg_replace('/[^0-9]/', '', $data['phone']);
    $senha = $data['senha'];
    $affiliate_ref = $data['affiliate_ref'] ?? '';

    $click_id = $data['click_id'] ?? null;
    $pixel_id = $data['pixel_id'] ?? null;
    $campaign_id = $data['campaign_id'] ?? null;
    $adset_id = $data['adset_id'] ?? null;
    $creative_id = $data['creative_id'] ?? null;
    $utm_source = $data['utm_source'] ?? null;
    $utm_campaign = $data['utm_campaign'] ?? null;
    $utm_medium = $data['utm_medium'] ?? null;
    $utm_content = $data['utm_content'] ?? null;
    $utm_term = $data['utm_term'] ?? null;
    $utm_id = $data['utm_id'] ?? null;
    $fbclid = $data['fbclid'] ?? null;

    $user_ip = getUserIP();

    error_log("CADASTRO RECEBIDO: username=$username, email=$email, phone=$phone, affiliate_ref=$affiliate_ref");

    if (!checkRateLimit($user_ip, $pdo)) {
        SecurityHelper::sendError(429, 'RATE_LIMIT_EXCEEDED',
            'Muitas tentativas de cadastro deste IP. Tente novamente em 1 hora.');
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        SecurityHelper::sendError(400, 'DUPLICATE_USER', 'Usuário ou email já cadastrado');
    }

    $pdo->beginTransaction();

    $password_hash = password_hash($senha, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (
        username, email, phone, password_hash,
        click_id, pixel_id, campaign_id, adset_id, creative_id,
        utm_source, utm_campaign, utm_medium, utm_content,
        utm_term, utm_id, fbclid, ip_address
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $username, $email, $phone, $password_hash,
        $click_id, $pixel_id, $campaign_id, $adset_id, $creative_id,
        $utm_source, $utm_campaign, $utm_medium, $utm_content,
        $utm_term, $utm_id, $fbclid, $user_ip
    ]);

    $userId = $pdo->lastInsertId();
    error_log("USUÁRIO CRIADO: ID=$userId, username=$username");

    registrarCadastroSucesso($user_ip, $pdo);

    $affiliateResult = false;
    if ($affiliate_ref) {
        $affiliateResult = applyAffiliateToUser($userId, $affiliate_ref, $pdo);
    }

    $pdo->commit();

    $leadDataForSMS = [
        'user_id' => $userId,
        'username' => $username,
        'email' => $email,
        'phone' => $phone,
        'ip_address' => $user_ip,
        'affiliate_ref' => $affiliate_ref,
        'click_id' => $click_id,
        'utm_source' => $utm_source,
        'utm_campaign' => $utm_campaign,
        'utm_medium' => $utm_medium,
        'fbclid' => $fbclid
    ];

    try {
        sendToSMSFunnelAsync($leadDataForSMS);
        error_log("SMSFunnel: Tentativa de envio realizada para user_id: $userId");
    } catch (Exception $e) {
        error_log("SMSFunnel ERRO: " . $e->getMessage() . " - Cadastro continuou normalmente");
    }

    $trackingEventData = [
        'evento' => 'registro',
        'user_id' => $userId,
        'click_id' => $click_id,
        'pixel_id' => $pixel_id,
        'utm_source' => $utm_source,
        'utm_campaign' => $utm_campaign,
        'utm_medium' => $utm_medium,
        'campaign_id' => $campaign_id,
        'adset_id' => $adset_id,
        'creative_id' => $creative_id,
        'fbclid' => $fbclid
    ];

    sendTrackingWebhookAsync($trackingEventData);

    $responseData = [
        'user_id' => $userId,
        'username' => $username,
        'email' => $email,
        'phone' => $phone
    ];

    if ($affiliateResult && $affiliateResult['success']) {
        $responseData['afiliado'] = [
            'codigo' => $affiliate_ref,
            'nome' => $affiliateResult['afiliado_nome'],
            'vinculado' => true
        ];
    } elseif ($affiliate_ref) {
        $responseData['afiliado'] = [
            'codigo' => $affiliate_ref,
            'vinculado' => false,
            'erro' => 'Código de afiliado inválido'
        ];
    }

    SecurityHelper::sendSuccess($responseData, 'Cadastro realizado com sucesso');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("ERRO NO CADASTRO: " . $e->getMessage());

    SecurityHelper::sendError(500, 'INTERNAL_ERROR', 'Erro interno do servidor');
}

?>
