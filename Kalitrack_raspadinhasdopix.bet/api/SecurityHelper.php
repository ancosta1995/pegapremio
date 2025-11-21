<?php

class SecurityHelper {
    

    public static function protectEndpoint($options = []) {
        require_once 'auth_config.php';
        require_once 'jwt_helper.php';
        require_once 'enhanced_security.php';
        
        if (isset($options['rate_limit'])) {
            $rateLimit = $options['rate_limit'];
            $key = "api_" . self::getUserIP() . "_" . ($options['endpoint'] ?? 'unknown');
            
            if (!self::checkRateLimit($key, $rateLimit['max'], $rateLimit['window'])) {
                self::sendError(429, 'RATE_LIMIT_EXCEEDED', 'Muitas tentativas. Aguarde antes de tentar novamente.');
            }
        }
        
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (empty($authHeader)) {
            self::sendError(401, 'MISSING_TOKEN', 'Token de autorização requerido');
        }
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            self::sendError(401, 'INVALID_TOKEN_FORMAT', 'Formato de token inválido');
        }
        
        $token = $matches[1];
        $payload = UltraSecureAuth::validateSecureToken($token);
        
        if (!$payload) {
            self::sendError(401, 'INVALID_TOKEN', 'Token inválido ou expirado');
        }
        
        self::logAPIAccess($options['endpoint'] ?? $_SERVER['REQUEST_URI'] ?? '', 'SUCCESS');
        
        return $payload;
    }
    

    public static function getUserIP() {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     
            'HTTP_CLIENT_IP',            
            'HTTP_X_FORWARDED_FOR',      
            'HTTP_X_FORWARDED',          
            'HTTP_X_CLUSTER_CLIENT_IP',  
            'HTTP_FORWARDED_FOR',        
            'HTTP_FORWARDED',            
            'HTTP_X_REAL_IP',            
            'REMOTE_ADDR'                
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    

    public static function sendError($httpCode, $errorCode, $message, $details = null) {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        
        $response = [
            'sucesso' => false,
            'erro' => $message,
            'codigo' => $errorCode,
            'timestamp' => time(),
            'request_id' => uniqid('req_')
        ];
        
        if ($details) {
            $response['detalhes'] = $details;
        }
        
        self::logAPIAccess($_SERVER['REQUEST_URI'] ?? '', 'BLOCKED', $message);
        
        echo json_encode($response);
        exit;
    }
    
    public static function sendSuccess($data, $message = 'Operação realizada com sucesso') {
        header('Content-Type: application/json');
        
        $response = [
            'sucesso' => true,
            'mensagem' => $message,
            'dados' => $data,
            'timestamp' => time(),
            'request_id' => uniqid('req_')
        ];
        
        echo json_encode($response);
        exit;
    }
    

    public static function validateInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = "Campo {$field} é obrigatório";
                continue;
            }
            
            if (empty($value)) continue;
            
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "Email inválido";
                        }
                        break;
                        
                    case 'phone':
                        $phoneClean = preg_replace('/[^0-9]/', '', $value);
                        if (strlen($phoneClean) < 10 || strlen($phoneClean) > 11) {
                            $errors[$field] = "Telefone deve ter 10 ou 11 dígitos";
                        }
                        break;
                        
                    case 'string':
                        if (!is_string($value)) {
                            $errors[$field] = "Deve ser texto";
                        }
                        break;
                        
                    case 'username':
                        if (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $value)) {
                            $errors[$field] = "Nome deve conter apenas letras e espaços";
                        }
                        break;
                        
                    case 'integer':
                        if (!is_numeric($value) || intval($value) != $value) {
                            $errors[$field] = "Deve ser um número inteiro";
                        }
                        break;
                        
                    case 'float':
                        if (!is_numeric($value)) {
                            $errors[$field] = "Deve ser um número";
                        }
                        break;
                }
            }
            
            if (isset($rule['min_value']) && is_numeric($value)) {
                if (floatval($value) < $rule['min_value']) {
                    $errors[$field] = "Valor mínimo é {$rule['min_value']}";
                }
            }
            
            if (isset($rule['max_value']) && is_numeric($value)) {
                if (floatval($value) > $rule['max_value']) {
                    $errors[$field] = "Valor máximo é {$rule['max_value']}";
                }
            }
            
            if (isset($rule['min_length'])) {
                if (strlen($value) < $rule['min_length']) {
                    $errors[$field] = "Mínimo {$rule['min_length']} caracteres";
                }
            }
            
            if (isset($rule['max_length'])) {
                if (strlen($value) > $rule['max_length']) {
                    $errors[$field] = "Máximo {$rule['max_length']} caracteres";
                }
            }
        }
        
        return $errors;
    }
    

    public static function checkRateLimit($key, $maxAttempts, $windowSeconds) {
        $cacheDir = __DIR__ . '/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $cacheFile = $cacheDir . '/rate_limit_' . md5($key);
        $now = time();
        $lockFile = $cacheFile . '.lock';
        
        $lockHandle = fopen($lockFile, 'w');
        if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
            fclose($lockHandle);
            return false;
        }
        
        try {
            if (file_exists($cacheFile)) {
                $data = json_decode(file_get_contents($cacheFile), true);
                
                if (($now - $data['first_attempt']) > $windowSeconds) {
                    $data = ['count' => 0, 'first_attempt' => $now];
                }
                
                if ($data['count'] >= $maxAttempts) {
                    return false;
                }
                
                $data['count']++;
            } else {
                $data = ['count' => 1, 'first_attempt' => $now];
            }
            
            file_put_contents($cacheFile, json_encode($data));
            return true;
            
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            @unlink($lockFile);
        }
    }
    

    public static function logAPIAccess($endpoint, $status, $details = '') {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoint' => $endpoint,
            'status' => $status,
            'ip' => self::getUserIP(),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100),
            'details' => $details,
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'request_id' => uniqid('req_')
        ];
        
        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/api_access_' . date('Y-m-d') . '.log';
        $logLine = json_encode($logData) . "\n";
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        try {
            global $pdo;
            if (isset($pdo) && $pdo) {
                $stmt = $pdo->prepare("
                    INSERT INTO security_logs (ip_address, action, details, user_agent, request_uri, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $logData['ip'],
                    $status,
                    $details,
                    $logData['user_agent'],
                    $endpoint
                ]);
            }
        } catch (Exception $e) {
            error_log("Erro ao salvar log de API: " . $e->getMessage());
        }
    }
    

    public static function sanitizeData($data) {
        if (is_array($data)) {
            $clean = [];
            foreach ($data as $key => $value) {
                $cleanKey = self::sanitizeString($key);
                if (is_string($value)) {
                    $clean[$cleanKey] = self::sanitizeString($value);
                } elseif (is_array($value)) {
                    $clean[$cleanKey] = self::sanitizeData($value);
                } elseif (is_numeric($value)) {
                    $clean[$cleanKey] = $value;
                } elseif (is_bool($value)) {
                    $clean[$cleanKey] = $value;
                } else {
                    $clean[$cleanKey] = null;
                }
            }
            return $clean;
        } elseif (is_string($data)) {
            return self::sanitizeString($data);
        } else {
            return $data;
        }
    }
    

    private static function sanitizeString($string) {
        $string = trim($string);
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
        
        $string = preg_replace('/\s+/', ' ', $string);
        
        return $string;
    }
    

    public static function checkTrustedOrigin($allowedOrigins = []) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        $defaultTrusted = ['localhost', '127.0.0.1', 'raspoupremios.top'];
        $trustedOrigins = array_merge($defaultTrusted, $allowedOrigins);
        
        foreach ($trustedOrigins as $trusted) {
            if (strpos($origin, $trusted) !== false || 
                strpos($referer, $trusted) !== false || 
                strpos($host, $trusted) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function cleanOldLogs($daysToKeep = 30) {
        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) return;
        
        $files = glob($logDir . '/api_access_*.log');
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }
}