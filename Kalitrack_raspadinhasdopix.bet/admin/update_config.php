<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/config.php';
    
    session_start();
    
    try {
        $configs = [
            'max_rtp' => floatval($_POST['max_rtp']) / 100,
            'safety_margin' => floatval($_POST['safety_margin']) / 100,
            'base_win_chance' => floatval($_POST['base_win_chance']) / 100
        ];
        
        if ($configs['max_rtp'] < 0.10 || $configs['max_rtp'] > 0.50) {
            throw new Exception('RTP deve estar entre 10% e 50%');
        }
        
        if ($configs['safety_margin'] < 0.01 || $configs['safety_margin'] > 0.10) {
            throw new Exception('Margem de segurança deve estar entre 1% e 10%');
        }
        
        if ($configs['base_win_chance'] < 0.10 || $configs['base_win_chance'] > 0.50) {
            throw new Exception('Chance base deve estar entre 10% e 50%');
        }
        
        foreach ($configs as $chave => $valor) {
            $stmt = $pdo->prepare("
                INSERT INTO configuracoes_sistema (chave, valor, descricao) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()
            ");
            
            $descricoes = [
                'max_rtp' => 'RTP máximo permitido para o sistema de controle',
                'safety_margin' => 'Margem de segurança antes de atingir RTP máximo',
                'base_win_chance' => 'Chance base de vitória quando RTP está normal'
            ];
            
            $stmt->execute([$chave, $valor, $descricoes[$chave] ?? 'Configuração do sistema']);
        }
        
        error_log("⚙️ CONFIGURAÇÕES ATUALIZADAS por usuário " . ($_SESSION['user_id'] ?? 'desconhecido'));
        error_log("   Max RTP: " . ($configs['max_rtp'] * 100) . "%");
        error_log("   Margem: " . ($configs['safety_margin'] * 100) . "%");
        error_log("   Chance base: " . ($configs['base_win_chance'] * 100) . "%");
        
        header('Location: dashboard_rtp.php?success=1&msg=' . urlencode('Configurações atualizadas com sucesso!'));
        
    } catch (Exception $e) {
        error_log("❌ ERRO AO ATUALIZAR CONFIGURAÇÕES: " . $e->getMessage());
        header('Location: dashboard_rtp.php?error=1&msg=' . urlencode($e->getMessage()));
    }
    
    exit;
}

header('Location: dashboard_rtp.php');
exit;

?>