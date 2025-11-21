<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'jwt_helper.php';
require_once 'enhanced_security.php';

function isValidRequest() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $allowedHosts = ['localhost', 'raspoupremios.top', '127.0.0.1'];
    
    foreach ($allowedHosts as $allowedHost) {
        if (strpos($host, $allowedHost) !== false || 
            strpos($referer, $allowedHost) !== false) {
            return true;
        }
    }
    
    $botPatterns = ['googlebot', 'bingbot', 'slurp', 'duckduckbot'];
    foreach ($botPatterns as $pattern) {
        if (stripos($userAgent, $pattern) !== false) {
            return false;
        }
    }
    
    return true;
}

function checkSimpleRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $cacheFile = sys_get_temp_dir() . '/token_rate_' . md5($ip);
    
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        $now = time();
        
        if (($now - $data['time']) > 60) {
            $data = ['count' => 0, 'time' => $now];
        }
        
        if ($data['count'] >= 10) {
            http_response_code(429);
            echo json_encode(['erro' => 'Muitos tokens solicitados']);
            exit;
        }
        
        $data['count']++;
        file_put_contents($cacheFile, json_encode($data));
        
    } else {
        file_put_contents($cacheFile, json_encode(['count' => 1, 'time' => time()]));
    }
}

error_log("TOKEN_SERVICE: Tentativa de acesso - Host: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . 
          " - Referer: " . ($_SERVER['HTTP_REFERER'] ?? 'unknown') . 
          " - User-Agent: " . substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 50));

if (!isValidRequest()) {
    error_log("TOKEN_SERVICE: Acesso negado - falha na validação");
    http_response_code(403);
    echo json_encode([
        'erro' => 'Acesso negado',
        'debug' => [
            'host' => $_SERVER['HTTP_HOST'] ?? 'null',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'null',
            'session_id' => session_id()
        ]
    ]);
    exit;
}

checkSimpleRateLimit();

try {
    $token = UltraSecureAuth::generateSecureToken();
    
    error_log("TOKEN_SERVICE: Token gerado com sucesso para sessão: " . session_id());

    echo json_encode([
        'token' => $token,
        'expires_in' => 300,
        'debug' => [
            'session_id' => session_id(),
            'generated_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    error_log("TOKEN_SERVICE: Erro ao gerar token: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'erro' => 'Erro interno ao gerar token',
        'debug' => $e->getMessage()
    ]);
}
?>