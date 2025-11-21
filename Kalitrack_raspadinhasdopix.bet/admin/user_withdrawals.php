<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $observacoes = trim($_POST['observacoes'] ?? '');

    if ($status === 'aprovado') {
        $stmt = $pdo->prepare("SELECT valor, chave_pix, tipo_chave, nome_titular
                                 FROM saques WHERE id = ?");
        $stmt->execute([$id]);
        $saque = $stmt->fetch(PDO::FETCH_ASSOC);

        $mapa = [
            'cpf'       => 'CPF',
            'cnpj'      => 'CNPJ',
            'email'     => 'EMAIL',
            'telefone'  => 'PHONE',
            'aleatori'  => 'RANDOM',
        ];
        $rawType = strtolower($saque['tipo_chave'] ?? 'email');
        if (!isset($mapa[$rawType])) {
            die("Tipo de chave inválido: {$saque['tipo_chave']}");
        }
        $pixKeyType = $mapa[$rawType];

        $pixKey = trim($saque['chave_pix']);
        switch($pixKeyType) {
            case 'CPF':
            case 'CNPJ':
                $pixKey = preg_replace('/\D/', '', $pixKey);
                break;
            case 'PHONE':
                $onlyDigits = preg_replace('/\D/', '', $pixKey);
                if (substr($onlyDigits, 0, 2) === '55') {
                    $onlyDigits = substr($onlyDigits, 2);
                }
                if (strlen($onlyDigits) !== 11) {
                    die("Chave PIX telefone inválida após sanitização: '{$onlyDigits}' (".strlen($onlyDigits)." dígitos)");
                }
                $pixKey = $onlyDigits;
                break;
        }

        $payload = [
            'amount'     => number_format($saque['valor'], 2, '.', ''),
            'pixKey'     => $pixKey,
            'pixKeyType' => $pixKeyType,
            'message'    => "Saque usuário #{$id} para {$saque['nome_titular']}"
        ];

        // 5) Chama a API
        $ch = curl_init('https://api.nomadfy.app/v1/wallet/withdrawal-requests');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer nd-key.01992d8f-f4c1-74ca-b338-017616b7261d.62vptH5ah79Yf80TGRckHAjgfHzxL4Q7MiLuDvM4E1B2Vh6DEOCNGbYdA0K5AfQR3io65NoA5atPw7G0lBnQ5RWQkVtaZjXMk6wlzYFNP3bK2SEd3m88',
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);
        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno || $httpCode !== 201) {
            $errorMsg = $errno
              ? "cURL error {$errno}"
              : "API retornou status {$httpCode}";
            die("Falha ao enviar saque: {$errorMsg} — responsta: {$response}");
        }

        $respData = json_decode($response, true);
        $apiReqId = $respData['id'] ?? null;

        $stmt = $pdo->prepare("
            UPDATE saques
               SET status = ?, observacoes = ?, atualizado_em = NOW(), processado_por = 1,
                   api_request_id = ?
             WHERE id = ?
        ");
        $stmt->execute([$status, $observacoes, $apiReqId, $id]);

    } else {
        $stmt = $pdo->prepare("
            UPDATE saques
               SET status = ?, observacoes = ?, atualizado_em = NOW(), processado_por = 1
             WHERE id = ?
        ");
        $stmt->execute([$status, $observacoes, $id]);
    }

    header("Location: user_withdrawals.php");
    exit();
}

$stmt = $pdo->query("
    SELECT s.*, u.username, u.email
    FROM saques s
    JOIN users u ON s.user_id = u.id
    WHERE s.status = 'pendente'
    ORDER BY s.id DESC
");
$saquesPendentes = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT s.*, u.username, u.email
    FROM saques s
    JOIN users u ON s.user_id = u.id
    WHERE s.status IN ('aprovado', 'recusado')
    ORDER BY s.atualizado_em DESC
    LIMIT 50
");
$saquesProcessados = $stmt->fetchAll();

$statsQuery = "
    SELECT
        COUNT(*) as total_saques,
        SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
        SUM(CASE WHEN status = 'aprovado' THEN 1 ELSE 0 END) as aprovados,
        SUM(CASE WHEN status = 'recusado' THEN 1 ELSE 0 END) as recusados,
        SUM(CASE WHEN status = 'aprovado' THEN valor ELSE 0 END) as valor_aprovado,
        SUM(CASE WHEN status = 'pendente' THEN valor ELSE 0 END) as valor_pendente
    FROM saques";
$statsStmt = $pdo->query($statsQuery);
$stats = $statsStmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Saques Usuários</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0a0a;
            color: #e4e4e7;
            min-height: 100vh;
            line-height: 1.5;
            font-size: 14px;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: #111111;
            border-right: 1px solid #27272a;
            padding: 24px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 0 24px 24px;
            border-bottom: 1px solid #27272a;
            margin-bottom: 24px;
        }

        .sidebar-title {
            font-size: 18px;
            font-weight: 700;
            color: #fafafa;
            letter-spacing: -0.025em;
        }

        .sidebar-nav {
            padding: 0 16px;
        }

        .nav-item {
            margin-bottom: 4px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #a1a1aa;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            background: #18181b;
            color: #e4e4e7;
        }

        .nav-link.active {
            background: #3b82f6;
            color: white;
        }

        .nav-icon {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            opacity: 0.7;
        }

        /* Main Content Area */
        .main-wrapper {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: #111111;
            border-bottom: 1px solid #27272a;
            padding: 24px 32px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1600px;
            margin: 0 auto;
        }

        .header h1 {
            font-size: 20px;
            font-weight: 600;
            color: #fafafa;
            letter-spacing: -0.025em;
        }

        .header-info {
            display: flex;
            align-items: center;
            gap: 24px;
            font-size: 13px;
            color: #a1a1aa;
        }

        /* Main Content */
        .main-content {
            padding: 32px;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: #111111;
            border: 1px solid #27272a;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #fafafa;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            color: #a1a1aa;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-card.pending .stat-value {
            color: #f59e0b;
        }

        .stat-card.approved .stat-value {
            color: #22c55e;
        }

        .stat-card.rejected .stat-value {
            color: #ef4444;
        }

        /* Cards */
        .card {
            background: #111111;
            border: 1px solid #27272a;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #27272a;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #fafafa;
            margin: 0;
        }

        .card-content {
            padding: 24px;
        }

        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 1px solid #27272a;
            margin-bottom: 24px;
        }

        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            color: #a1a1aa;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            position: relative;
        }

        .tab:hover {
            color: #e4e4e7;
        }

        .tab.active {
            color: #3b82f6;
        }

        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: #3b82f6;
        }

        .tab-badge {
            background: #ef4444;
            color: white;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 8px;
        }

        .tab-badge.gray {
            background: #52525b;
        }

        /* Tab Content */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Tables */
        .table-container {
            background: #111111;
            border: 1px solid #27272a;
            border-radius: 8px;
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: #18181b;
            padding: 12px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #a1a1aa;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #27272a;
        }

        .table td {
            padding: 12px 16px;
            border-bottom: 1px solid #27272a;
            color: #e4e4e7;
            font-size: 14px;
        }

        .table tbody tr:hover {
            background: #18181b;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .status-aprovado {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .status-pendente {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .status-recusado {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        /* User info */
        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #3b82f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 600;
            color: white;
        }

        /* Amount display */
        .amount {
            font-weight: 600;
            color: #22c55e;
            font-variant-numeric: tabular-nums;
        }

        /* PIX Key */
        .pix-key {
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 12px;
            color: #a1a1aa;
            background: #18181b;
            padding: 2px 6px;
            border-radius: 4px;
        }

        /* Date display */
        .date-display {
            font-size: 13px;
            color: #a1a1aa;
        }

        /* Forms */
        .form-inline {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .form-input {
            padding: 6px 8px;
            background: #18181b;
            border: 1px solid #27272a;
            border-radius: 4px;
            color: #e4e4e7;
            font-size: 12px;
            width: 120px;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 4px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            white-space: nowrap;
            gap: 4px;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-success {
            background: #22c55e;
            color: white;
            border-color: #22c55e;
        }

        .btn-success:hover:not(:disabled) {
            background: #16a34a;
            border-color: #16a34a;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }

        .btn-danger:hover:not(:disabled) {
            background: #dc2626;
            border-color: #dc2626;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }

        /* API Request ID */
        .api-id {
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 11px;
            background: #18181b;
            padding: 2px 4px;
            border-radius: 3px;
            color: #a1a1aa;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #71717a;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1002;
            background: #111111;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 1001;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-wrapper {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 16px 20px;
            }

            .main-content {
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .table-container {
                overflow-x: auto;
            }

            .tabs {
                flex-direction: column;
            }

            .tab {
                text-align: left;
                padding: 12px 16px;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #18181b;
        }

        ::-webkit-scrollbar-thumb {
            background: #3f3f46;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #52525b;
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
        <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
        </svg>
    </button>

    <div class="dashboard-container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2 class="sidebar-title">Painel Administrativo</h2>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <svg class="nav-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                        </svg>
                        Dashboard
                    </a>
                </div>
                <div class="nav-item">
                    <a href="depositos.php" class="nav-link">
                        <svg class="nav-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                        </svg>
                        Depósitos
                    </a>
                </div>
                <div class="nav-item">
                    <a href="games.php" class="nav-link">
                        <svg class="nav-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                        </svg>
                        Jogos
                    </a>
                </div>
                <div class="nav-item">
                    <a href="jogadas.php" class="nav-link">
                        <svg class="nav-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        Histórico de Jogadas
                    </a>
                </div>
                <div class="nav-item">
                    <a href="affiliate_withdrawals.php" class="nav-link">
                        <svg class="nav-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                        Saques Afiliados
                    </a>
                </div>
                <div class="nav-item">
                    <a href="user_withdrawals.php" class="nav-link active">
                        <svg class="nav-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 11-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Saques Usuários
                    </a>
                </div>
                <div class="nav-item">
                    <a href="users.php" class="nav-link">
                        <svg class="nav-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                        Usuários
                    </a>
                </div>
                <div class="nav-item">
                    <a href="affiliates.php" class="nav-link">
                        <svg class="nav-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"/>
                        </svg>
                        Afiliados
                    </a>
                </div>
                <div class="nav-item">
                    <a href="tracking.php" class="nav-link">
                        <svg class="nav-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Trackeamento
                    </a>
                </div>
                <div class="nav-item" style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #27272a;">
                    <a href="logout.php" class="nav-link">
                        <svg class="nav-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/>
                        </svg>
                        Sair
                    </a>
                </div>
            </nav>
        </aside>

        <div class="main-wrapper">
            <div class="container">
                <div class="header">
                    <div class="header-content">
                        <h1>Gestão de Saques Usuários</h1>
                        <div class="header-info">
                            <div>Total: <?php echo $stats['total_saques']; ?> saques</div>
                        </div>
                    </div>
                </div>

                <div class="main-content">
                    <div class="stats-grid">
                        <div class="stat-card pending">
                            <div class="stat-value"><?php echo $stats['pendentes']; ?></div>
                            <div class="stat-label">Pendentes</div>
                        </div>
                        <div class="stat-card approved">
                            <div class="stat-value"><?php echo $stats['aprovados']; ?></div>
                            <div class="stat-label">Aprovados</div>
                        </div>
                        <div class="stat-card rejected">
                            <div class="stat-value"><?php echo $stats['recusados']; ?></div>
                            <div class="stat-label">Recusados</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">R$ <?php echo number_format($stats['valor_pendente'], 2, ',', '.'); ?></div>
                            <div class="stat-label">Valor Pendente</div>
                        </div>
                        <div class="stat-card approved">
                            <div class="stat-value">R$ <?php echo number_format($stats['valor_aprovado'], 2, ',', '.'); ?></div>
                            <div class="stat-label">Valor Aprovado</div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-content" style="padding: 0;">
                            <div class="tabs">
                                <button class="tab active" onclick="showTab('pendentes')">
                                    Saques Pendentes
                                    <?php if (count($saquesPendentes) > 0): ?>
                                        <span class="tab-badge"><?php echo count($saquesPendentes); ?></span>
                                    <?php endif; ?>
                                </button>
                                <button class="tab" onclick="showTab('historico')">
                                    Histórico
                                    <span class="tab-badge gray"><?php echo count($saquesProcessados); ?></span>
                                </button>
                            </div>

                            <div id="tab-pendentes" class="tab-content active">
                                <div class="table-container">
                                    <?php if (empty($saquesPendentes)): ?>
                                    <div class="empty-state">
                                        <div class="empty-state-icon">✅</div>
                                        <h3>Nenhum saque pendente</h3>
                                        <p>Todos os saques foram processados!</p>
                                    </div>
                                    <?php else: ?>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Usuário</th>
                                                <th>Email</th>
                                                <th>Valor</th>
                                                <th>Chave PIX</th>
                                                <th>Tipo</th>
                                                <th>Titular</th>
                                                <th>Data</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($saquesPendentes as $saque): ?>
                                            <tr>
                                                <td><?php echo $saque['id']; ?></td>
                                                <td>
                                                    <div class="user-info">
                                                        <div class="user-avatar">
                                                            <?php echo strtoupper(substr($saque['username'], 0, 1)); ?>
                                                        </div>
                                                        <span><?php echo htmlspecialchars($saque['username']); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($saque['email']); ?></td>
                                                <td>
                                                    <span class="amount">R$ <?php echo number_format($saque['valor'], 2, ',', '.'); ?></span>
                                                </td>
                                                <td>
                                                    <span class="pix-key"><?php echo htmlspecialchars($saque['chave_pix']); ?></span>
                                                </td>
                                                <td>
                                                    <?php if (isset($saque['tipo_chave']) && $saque['tipo_chave']): ?>
                                                        <span class="status-badge status-pendente">
                                                            <?php echo ucfirst($saque['tipo_chave']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="color: #71717a;">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo isset($saque['nome_titular']) && $saque['nome_titular'] ? htmlspecialchars($saque['nome_titular']) : '-'; ?>
                                                </td>
                                                <td>
                                                    <span class="date-display">
                                                        <?php echo date('d/m/Y H:i', strtotime($saque['criado_em'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div style="display: flex; flex-direction: column; gap: 8px;">
                                                        <form method="POST" class="form-inline">
                                                            <input type="hidden" name="id" value="<?php echo $saque['id']; ?>">
                                                            <input type="hidden" name="status" value="aprovado">
                                                            <input type="text" name="observacoes" placeholder="Observações" class="form-input">
                                                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Aprovar saque?')">
                                                                <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                                </svg>
                                                                Aprovar
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="form-inline">
                                                            <input type="hidden" name="id" value="<?php echo $saque['id']; ?>">
                                                            <input type="hidden" name="status" value="recusado">
                                                            <input type="text" name="observacoes" placeholder="Motivo recusa" class="form-input">
                                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Recusar saque?')">
                                                                <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                                </svg>
                                                                Recusar
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div id="tab-historico" class="tab-content">
                                <div class="table-container">
                                    <?php if (empty($saquesProcessados)): ?>
                                    <div class="empty-state">
                                        <div class="empty-state-icon">📋</div>
                                        <h3>Nenhum saque processado</h3>
                                        <p>O histórico aparecerá aqui após processar os primeiros saques.</p>
                                    </div>
                                    <?php else: ?>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Status</th>
                                                <th>Usuário</th>
                                                <th>Email</th>
                                                <th>Valor</th>
                                                <th>Chave PIX</th>
                                                <th>Titular</th>
                                                <th>Data Solicitação</th>
                                                <th>Data Processamento</th>
                                                <th>API Request ID</th>
                                                <th>Observações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($saquesProcessados as $saque): ?>
                                            <tr>
                                                <td><?php echo $saque['id']; ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $saque['status']; ?>">
                                                        <?php echo $saque['status'] === 'aprovado' ? '✓ Aprovado' : '✗ Recusado'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="user-info">
                                                        <div class="user-avatar">
                                                            <?php echo strtoupper(substr($saque['username'], 0, 1)); ?>
                                                        </div>
                                                        <span><?php echo htmlspecialchars($saque['username']); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($saque['email']); ?></td>
                                                <td>
                                                    <span class="amount">R$ <?php echo number_format($saque['valor'], 2, ',', '.'); ?></span>
                                                </td>
                                                <td>
                                                    <div style="font-size: 12px;">
                                                        <div class="pix-key"><?php echo htmlspecialchars($saque['chave_pix']); ?></div>
                                                        <?php if (isset($saque['tipo_chave']) && $saque['tipo_chave']): ?>
                                                            <div style="color: #71717a; margin-top: 2px;">
                                                                <?php echo ucfirst($saque['tipo_chave']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo isset($saque['nome_titular']) && $saque['nome_titular'] ? htmlspecialchars($saque['nome_titular']) : '-'; ?>
                                                </td>
                                                <td>
                                                    <span class="date-display">
                                                        <?php echo date('d/m/Y H:i', strtotime($saque['criado_em'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="date-display">
                                                        <?php echo $saque['atualizado_em'] ? date('d/m/Y H:i', strtotime($saque['atualizado_em'])) : '-'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (isset($saque['api_request_id']) && $saque['api_request_id']): ?>
                                                        <span class="api-id">
                                                            <?php echo htmlspecialchars(substr($saque['api_request_id'], 0, 8)) . '...'; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="color: #71717a;">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (isset($saque['observacoes']) && $saque['observacoes']): ?>
                                                        <div style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                                                             title="<?php echo htmlspecialchars($saque['observacoes']); ?>">
                                                            <?php echo htmlspecialchars($saque['observacoes']); ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span style="color: #71717a;">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        function showTab(tabName) {
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));

            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));

            document.getElementById('tab-' + tabName).classList.add('active');

            event.target.classList.add('active');
        }
    </script>
</body>
</html>
