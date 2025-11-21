<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

require_once 'db.php';

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'erro' => true,
            'mensagem' => 'Usuário não autenticado'
        ]);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    $sql = "SELECT 
                id,
                user_id,
                valor,
                chave_pix,
                tipo_chave,
                nome_titular,
                status,
                observacoes,
                api_request_id,
                criado_em,
                atualizado_em,
                processado_em,
                processado_por
            FROM saques 
            WHERE user_id = ? 
            ORDER BY criado_em DESC 
            LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $saques = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$saques) {
        echo json_encode([
            'sucesso' => true,
            'saques' => [],
            'total' => 0,
            'mensagem' => 'Nenhum saque encontrado'
        ]);
        exit;
    }

    $saquesFormatados = [];
    foreach ($saques as $saque) {
        $saquesFormatados[] = [
            'id' => (int)$saque['id'],
            'user_id' => (int)$saque['user_id'],
            'valor' => number_format((float)$saque['valor'], 2, '.', ''),
            'valor_formatado' => 'R$ ' . number_format((float)$saque['valor'], 2, ',', '.'),
            'chave_pix' => $saque['chave_pix'],
            'tipo_chave' => $saque['tipo_chave'],
            'nome_titular' => $saque['nome_titular'],
            'status' => $saque['status'],
            'status_texto' => ucfirst($saque['status']),
            'observacoes' => $saque['observacoes'],
            'api_request_id' => $saque['api_request_id'],
            'criado_em' => $saque['criado_em'],
            'atualizado_em' => $saque['atualizado_em'],
            'processado_em' => $saque['processado_em'],
            'processado_por' => $saque['processado_por'],
            'data_solicitacao' => $saque['criado_em'],
            'data_processamento' => $saque['processado_em'],
            'criado_em_formatado' => date('d/m/Y H:i', strtotime($saque['criado_em'])),
            'atualizado_em_formatado' => $saque['atualizado_em'] ? 
                date('d/m/Y H:i', strtotime($saque['atualizado_em'])) : null,
            'processado_em_formatado' => $saque['processado_em'] ? 
                date('d/m/Y H:i', strtotime($saque['processado_em'])) : null
        ];
    }

    $total_saques = count($saquesFormatados);
    $valor_total = array_sum(array_column($saques, 'valor'));
    $pendentes = array_filter($saques, function($s) { return $s['status'] === 'pendente'; });
    $aprovados = array_filter($saques, function($s) { return $s['status'] === 'aprovado'; });
    $recusados = array_filter($saques, function($s) { return $s['status'] === 'recusado'; });

    echo json_encode([
        'sucesso' => true,
        'saques' => $saquesFormatados,
        'estatisticas' => [
            'total' => $total_saques,
            'valor_total' => number_format($valor_total, 2, '.', ''),
            'valor_total_formatado' => 'R$ ' . number_format($valor_total, 2, ',', '.'),
            'pendentes' => count($pendentes),
            'aprovados' => count($aprovados),
            'recusados' => count($recusados)
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => true,
        'mensagem' => 'Erro interno do servidor',
        'detalhes' => 'Erro ao consultar banco de dados'
    ]);
    
    error_log("Erro na API historico_saques.php: " . $e->getMessage());

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => true,
        'mensagem' => 'Erro interno do servidor',
        'detalhes' => 'Erro inesperado'
    ]);
    
    error_log("Erro na API historico_saques.php: " . $e->getMessage());
}
?>