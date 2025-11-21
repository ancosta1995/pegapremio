<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['afiliado_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$afiliadoId = $_SESSION['afiliado_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_stats':
            $stats = getAffiliateStats($afiliadoId);
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        case 'get_my_commission':
            $dadosAfiliado = getDadosAfiliado($afiliadoId);
            echo json_encode([
                'success' => true,
                'data' => [
                    'comissao_percentual' => $dadosAfiliado['comissao_percentual'] ?? 50.00,
                    'codigo_afiliado' => $dadosAfiliado['codigo_afiliado']
                ]
            ]);
            break;
            
        case 'get_referrals':
            $limit = intval($_GET['limit'] ?? 50);
            $indicados = getIndicatedUsers($afiliadoId, $limit);
            echo json_encode([
                'success' => true,
                'data' => $indicados
            ]);
            break;
            
        case 'get_withdrawals':
            $limit = intval($_GET['limit'] ?? 20);
            $saques = getAffiliateSaques($afiliadoId, $limit);
            echo json_encode([
                'success' => true,
                'data' => $saques
            ]);
            break;
            
        case 'request_withdrawal':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $valor = floatval($_POST['valor'] ?? 0);
            $chavePix = trim($_POST['chave_pix'] ?? '');
            $tipoChave = $_POST['tipo_chave'] ?? '';
            $nomeTitular = trim($_POST['nome_titular'] ?? '');
            
            $resultado = solicitarSaque($afiliadoId, $valor, $chavePix, $tipoChave, $nomeTitular);
            echo json_encode($resultado);
            break;
            
        case 'update_profile':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $nome = trim($_POST['nome'] ?? '');
            $telefone = trim($_POST['telefone'] ?? '');
            
            if (empty($nome)) {
                throw new Exception('Nome é obrigatório');
            }
            
            $stmt = $pdo->prepare("UPDATE afiliados SET nome_completo = ?, telefone = ? WHERE id = ?");
            $stmt->execute([$nome, $telefone, $afiliadoId]);
            
            $_SESSION['afiliado_nome'] = $nome;
            
            echo json_encode([
                'success' => true,
                'message' => 'Perfil atualizado com sucesso'
            ]);
            break;
            
        case 'change_password':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $senhaAtual = $_POST['senha_atual'] ?? '';
            $novaSenha = $_POST['nova_senha'] ?? '';
            
            if (empty($senhaAtual) || empty($novaSenha)) {
                throw new Exception('Todos os campos são obrigatórios');
            }
            
            if (strlen($novaSenha) < 6) {
                throw new Exception('Nova senha deve ter pelo menos 6 caracteres');
            }
            
            $stmt = $pdo->prepare("SELECT senha_hash FROM afiliados WHERE id = ?");
            $stmt->execute([$afiliadoId]);
            $senhaHashAtual = $stmt->fetchColumn();
            
            if (!password_verify($senhaAtual, $senhaHashAtual)) {
                throw new Exception('Senha atual incorreta');
            }
            
            $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE afiliados SET senha_hash = ? WHERE id = ?");
            $stmt->execute([$novaSenhaHash, $afiliadoId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Senha alterada com sucesso'
            ]);
            break;
            
        case 'validate_pix':
            $chavePix = trim($_POST['chave_pix'] ?? '');
            $tipoChave = $_POST['tipo_chave'] ?? '';
            
            $valida = validarChavePix($chavePix, $tipoChave);
            
            echo json_encode([
                'success' => true,
                'valid' => $valida
            ]);
            break;
            
        case 'get_referral_link':
            $dadosAfiliado = getDadosAfiliado($afiliadoId);
            $baseUrl = gerarUrlIndicacao($dadosAfiliado['codigo_afiliado']);
            
            $source = $_GET['utm_source'] ?? '';
            $medium = $_GET['utm_medium'] ?? '';
            $campaign = $_GET['utm_campaign'] ?? '';
            
            $url = $baseUrl;
            $params = [];
            
            if ($source) $params[] = 'utm_source=' . urlencode($source);
            if ($medium) $params[] = 'utm_medium=' . urlencode($medium);
            if ($campaign) $params[] = 'utm_campaign=' . urlencode($campaign);
            
            if (!empty($params)) {
                $url .= '&' . implode('&', $params);
            }
            
            echo json_encode([
                'success' => true,
                'link' => $url
            ]);
            break;
            
        case 'dashboard_summary':
            $stats = getAffiliateStats($afiliadoId);
            $ultimosIndicados = getIndicatedUsers($afiliadoId, 5);
            $ultimosSaques = getAffiliateSaques($afiliadoId, 3);
            $dadosAfiliado = getDadosAfiliado($afiliadoId);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'ultimos_indicados' => $ultimosIndicados,
                    'ultimos_saques' => $ultimosSaques,
                    'afiliado' => $dadosAfiliado,
                    'url_indicacao' => gerarUrlIndicacao($dadosAfiliado['codigo_afiliado']),
                    'comissao_personalizada' => $dadosAfiliado['comissao_percentual']
                ]
            ]);
            break;
            
        case 'commission_details':
            $dadosAfiliado = getDadosAfiliado($afiliadoId);
            $stats = getAffiliateStats($afiliadoId);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'comissao_percentual' => $dadosAfiliado['comissao_percentual'],
                    'excedente_total' => $stats['excedente'],
                    'comissao_calculada' => $stats['comissao_total'],
                    'total_depositos' => $stats['total_depositos'],
                    'total_saques_indicados' => $stats['total_saques_indicados']
                ]
            ]);
            break;
            
        default:
            throw new Exception('Ação não encontrada');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Erro na API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ]);
}
?>