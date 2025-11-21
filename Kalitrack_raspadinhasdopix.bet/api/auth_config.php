<?php
class AuthConfig {
    private static $JWT_SECRET = 'NSAvnUmlriRWUtwfDoyQjcOqgiveWTJSxwVYUaEyrQeQscjcMnLAkQGAbRgDaUpomdbiplYGJUDyhDrNIAyMJvzkiYPkcwmTknyyeVSXRWbRjSwulDWarDYDuQhBTOsE';
    
    const TOKEN_EXPIRY = 3600;
    const ISSUER = 'raspoupremios.top';
    const ALGORITHM = 'HS256';
    
    public static function getSecret() {
        return self::$JWT_SECRET;
    }
    
    public static function getComplexSecret() {
        $base = self::$JWT_SECRET;
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $year = date('Y');
        
        return hash('sha256', $base . '_' . $domain . '_' . $year);
    }
}
?>