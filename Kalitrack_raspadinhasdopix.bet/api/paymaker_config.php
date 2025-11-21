<?php
/**
 * Configurações da API Paymaker
 * 
 * IMPORTANTE: Substitua as chaves abaixo pelas suas chaves reais da Paymaker
 * - Obtenha suas chaves no painel da Paymaker
 * - Use chaves de teste (pk_test_xxx, sk_test_xxx) para desenvolvimento
 * - Use chaves de produção (pk_live_xxx, sk_live_xxx) para produção
 */

// URL base da API Paymaker
define('PAYMAKER_API_URL', 'https://api.paymaker.com.br');

// Chaves da API - SUBSTITUA PELAS SUAS CHAVES REAIS
define('PAYMAKER_PUBLIC_KEY', '968c46a4-ef89-4bb4-8d01-869cb5c0719d'); // Sua chave pública
define('PAYMAKER_SECRET_KEY', 'a7b7266f-aaff-49fc-a5a2-cd2fe6bceb7c'); // Sua chave secreta

/**
 * Gera o header de autorização para a API Paymaker
 * @return string Header Authorization em base64
 */
function getPaymakerAuthHeader() {
    return base64_encode(PAYMAKER_PUBLIC_KEY . ':' . PAYMAKER_SECRET_KEY);
}

/**
 * Verifica se as chaves estão configuradas corretamente
 * @return bool True se as chaves estão configuradas
 */
function isPaymakerConfigured() {
    return PAYMAKER_PUBLIC_KEY !== 'pk_live_abc123' && 
           PAYMAKER_SECRET_KEY !== 'sk_live_xyz789' &&
           !empty(PAYMAKER_PUBLIC_KEY) && 
           !empty(PAYMAKER_SECRET_KEY);
}
?>
