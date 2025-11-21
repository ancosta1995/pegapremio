<?php
require_once 'auth_config.php';

class JWTHelper {
    public static function generateToken($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => AuthConfig::ALGORITHM]);
        $payload['iat'] = time();
        $payload['exp'] = time() + AuthConfig::TOKEN_EXPIRY;
        $payload['iss'] = AuthConfig::ISSUER;
        
        $payload = json_encode($payload);
        
        $headerEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $payloadEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, AuthConfig::getSecret(), true);
        $signatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
    }
    
    public static function validateToken($token) {
        if (!$token) return false;
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, AuthConfig::getSecret(), true);
        $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if (!hash_equals($expectedSignature, $signatureEncoded)) {
            return false;
        }
        
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payloadEncoded)), true);
        
        if (!$payload) return false;
        
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
}
?>