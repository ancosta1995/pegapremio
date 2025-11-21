<?php
require_once 'auth_config.php';
require_once 'jwt_helper.php';

class UltraSecureAuth {

    public static function generateSecureToken() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $payload = [
            'session_id' => session_id(),
            'ip' => self::getUserIP(),
            'timestamp' => time(),
            'nonce' => bin2hex(random_bytes(8)),
            'app' => 'raspoupremios_frontend'
        ];

        $_SESSION['token_nonce'] = $payload['nonce'];
        $_SESSION['token_created'] = time();

        return JWTHelper::generateToken($payload);
    }

    public static function validateSecureToken($token) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $payload = JWTHelper::validateToken($token);
        if (!$payload) {
            self::logSecurity('INVALID_JWT', 'Token JWT inválido');
            return false;
        }

        if (!empty($payload['session_id']) && $payload['session_id'] !== session_id()) {
            self::logSecurity('SESSION_WARNING', 'Sessão diferente mas continuando');
        }

        if (!empty($payload['nonce']) &&
            isset($_SESSION['token_nonce']) &&
            $_SESSION['token_nonce'] !== $payload['nonce']) {
            self::logSecurity('NONCE_WARNING', 'Nonce diferente mas continuando');
        }

        $tokenAge = time() - ($payload['timestamp'] ?? 0);
        if ($tokenAge > 600) {
            self::logSecurity('TOKEN_EXPIRED', "Token expirado: {$tokenAge}s");
            return false;
        }

        $currentIP = self::getUserIP();
        if (!empty($payload['ip']) && $payload['ip'] !== $currentIP) {
            self::logSecurity('IP_CHANGED', "IP mudou de {$payload['ip']} para {$currentIP}");
        }

        return $payload;
    }


    private static function getUserIP() {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }


    private static function logSecurity($action, $details) {
        $logMessage = "SECURITY[$action]: $details - IP: " . self::getUserIP() .
                     " - Session: " . session_id() .
                     " - Time: " . date('Y-m-d H:i:s');

        error_log($logMessage);

        try {
            global $pdo;
            if (isset($pdo) && $pdo) {
                $stmt = $pdo->prepare("
                    INSERT INTO security_logs (ip_address, action, details, user_agent, request_uri, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");

                $stmt->execute([
                    self::getUserIP(),
                    $action,
                    $details,
                    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                    $_SERVER['REQUEST_URI'] ?? ''
                ]);
            }
        } catch (Exception $e) {
            error_log("Erro ao salvar log de segurança: " . $e->getMessage());
        }
    }
}
?>
