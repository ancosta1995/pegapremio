<?php
session_start();

function trackAffiliateCode() {
    $referralCode = $_GET['ref'] ?? $_GET['referral'] ?? $_GET['afiliado'] ?? null;
    
    if ($referralCode) {
        $referralCode = trim($referralCode);
        $referralCode = preg_replace('/[^a-zA-Z0-9]/', '', $referralCode);
        
        if (strlen($referralCode) >= 3) {
            setcookie('affiliate_ref', $referralCode, time() + (30 * 24 * 60 * 60), '/');
            $_SESSION['affiliate_ref'] = $referralCode;
            
            error_log("Nova indicação capturada: " . $referralCode . " - IP: " . $_SERVER['REMOTE_ADDR']);
            
            $cleanUrl = strtok($_SERVER["REQUEST_URI"], '?');
            if ($cleanUrl !== $_SERVER["REQUEST_URI"]) {
                header("Location: " . $cleanUrl, true, 302);
                exit;
            }
        }
    }
    
    if (!isset($_SESSION['affiliate_ref'])) {
        if (isset($_COOKIE['affiliate_ref'])) {
            $_SESSION['affiliate_ref'] = $_COOKIE['affiliate_ref'];
        }
    }
}

function getAffiliateCode() {
    return $_SESSION['affiliate_ref'] ?? $_COOKIE['affiliate_ref'] ?? null;
}

function applyAffiliateToUser($userId, $pdo) {
    $affiliateCode = getAffiliateCode();
    
    if ($affiliateCode && $userId) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM afiliados WHERE codigo_afiliado = ? AND status = 'ativo'");
            $stmt->execute([$affiliateCode]);
            $afiliado = $stmt->fetch();
            
            if ($afiliado) {
                $stmt = $pdo->prepare("UPDATE users SET referral_id = ? WHERE id = ?");
                $stmt->execute([$affiliateCode, $userId]);
                
                $stmt = $pdo->prepare("
                    INSERT INTO estatisticas_afiliados (afiliado_id, total_indicados) 
                    VALUES (?, 1)
                    ON DUPLICATE KEY UPDATE 
                    total_indicados = total_indicados + 1,
                    ultima_atualizacao = CURRENT_TIMESTAMP
                ");
                $stmt->execute([$afiliado['id']]);
                
                unset($_SESSION['affiliate_ref']);
                setcookie('affiliate_ref', '', time() - 3600, '/');
                
                error_log("Usuário $userId vinculado ao afiliado: $affiliateCode");
                return true;
            }
        } catch(PDOException $e) {
            error_log("Erro ao aplicar afiliado: " . $e->getMessage());
        }
    }
    
    return false;
}

trackAffiliateCode();
?>

<script>
function trackAffiliateClick(affiliateCode) {
    if (typeof(Storage) !== "undefined") {
        localStorage.setItem('affiliate_ref', affiliateCode);
        localStorage.setItem('affiliate_ref_date', Date.now());
    }
    
    if (typeof gtag !== 'undefined') {
        gtag('event', 'affiliate_click', {
            'affiliate_code': affiliateCode,
            'page_location': window.location.href
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const refCode = urlParams.get('ref') || urlParams.get('referral') || urlParams.get('afiliado');
    
    if (refCode) {
        trackAffiliateClick(refCode);
    }
});
</script>