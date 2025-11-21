<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';

$status_filter = $_GET['status'] ?? '';
$search_filter = $_GET['search'] ?? '';

$query = "SELECT a.*, 
    COALESCE(a.comissao_percentual, 50.00) as comissao_atual,
    COUNT(DISTINCT u.id) as total_indicados,
    COALESCE(SUM(DISTINCT t.amount), 0) as total_depositos,
    COALESCE(SUM(DISTINCT CASE WHEN s.status IN ('aprovado', 'pago') THEN s.valor ELSE 0 END), 0) as total_saques
FROM afiliados a
LEFT JOIN users u ON u.referral_id = a.codigo_afiliado
LEFT JOIN transactions t ON t.username = u.username AND t.status = 'pago'
LEFT JOIN saques s ON s.user_id = u.id
WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $query .= " AND a.status = ?";
    $params[] = $status_filter;
}

if (!empty($search_filter)) {
    $query .= " AND (a.nome_completo LIKE ? OR a.email LIKE ? OR a.codigo_afiliado LIKE ?)";
    $params[] = "%$search_filter%";
    $params[] = "%$search_filter%";
    $params[] = "%$search_filter%";
}

$query .= " GROUP BY a.id ORDER BY a.criado_em DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$afiliados = $stmt->fetchAll();

if ($_POST['action'] ?? false) {
    if ($_POST['action'] === 'update_status') {
        $affiliate_id = $_POST['affiliate_id'];
        $new_status = $_POST['new_status'];
        
        $stmt = $pdo->prepare("UPDATE afiliados SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $affiliate_id]);
        
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    if ($_POST['action'] === 'bulk_update') {
        $affiliate_ids = explode(',', $_POST['affiliate_ids']);
        $new_status = $_POST['new_status'];
        
        foreach ($affiliate_ids as $id) {
            $stmt = $pdo->prepare("UPDATE afiliados SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $id]);
        }
        
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    if ($_POST['action'] === 'update_commission') {
        $affiliate_id = $_POST['affiliate_id'];
        $new_commission = floatval($_POST['new_commission']);
        
        if ($new_commission >= 0 && $new_commission <= 100) {
            $stmt = $pdo->prepare("UPDATE afiliados SET comissao_percentual = ? WHERE id = ?");
            $stmt->execute([$new_commission, $affiliate_id]);
        }
        
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'ativo' THEN 1 ELSE 0 END) as ativos,
    SUM(CASE WHEN status = 'inativo' THEN 1 ELSE 0 END) as inativos,
    SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes
    FROM afiliados";
$stats = $pdo->query($stats_query)->fetch();

function formatarDinheiro($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Afiliados</title>
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
            margin-bottom: 24px;
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
        
        .stat-card.total .stat-value { color: #3b82f6; }
        .stat-card.active .stat-value { color: #22c55e; }
        .stat-card.inactive .stat-value { color: #ef4444; }
        .stat-card.pending .stat-value { color: #f59e0b; }
        
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
        
        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #e4e4e7;
            margin-bottom: 6px;
        }
        
        .form-input {
            width: 100%;
            padding: 10px 12px;
            background: #18181b;
            border: 1px solid #27272a;
            border-radius: 6px;
            color: #e4e4e7;
            font-size: 14px;
            transition: border-color 0.2s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        .form-input::placeholder {
            color: #71717a;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 6px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            white-space: nowrap;
            gap: 8px;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .btn-primary:hover:not(:disabled) {
            background: #2563eb;
            border-color: #2563eb;
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
        
        .btn-secondary {
            background: #27272a;
            color: #e4e4e7;
            border-color: #3f3f46;
        }
        
        .btn-secondary:hover:not(:disabled) {
            background: #3f3f46;
            border-color: #52525b;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }
        
        .btn-xs {
            padding: 4px 8px;
            font-size: 12px;
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
        
        .status-ativo {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }
        
        .status-inativo {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        .status-pendente {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        
        /* Affiliate code */
        .affiliate-code {
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 12px;
            background: #18181b;
            padding: 4px 8px;
            border-radius: 4px;
            color: #3b82f6;
            font-weight: 600;
        }
        
        /* Password display */
        .password-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .password-display {
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 12px;
            background: #18181b;
            padding: 4px 8px;
            border-radius: 4px;
            color: #e4e4e7;
            min-width: 120px;
        }
        
        .password-btn {
            background: #27272a;
            border: 1px solid #3f3f46;
            color: #a1a1aa;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s ease;
        }
        
        .password-btn:hover {
            background: #3f3f46;
            color: #e4e4e7;
        }
        
        /* User info */
        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #3b82f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 500;
            color: #e4e4e7;
        }
        
        .user-email {
            font-size: 12px;
            color: #a1a1aa;
        }
        
        /* Date display */
        .date-display {
            font-size: 13px;
            color: #a1a1aa;
        }
        
        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 4px;
        }
        
        /* Bulk actions */
        .bulk-actions {
            background: #111111;
            border: 1px solid #27272a;
            border-radius: 8px;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-top: 16px;
        }
        
        .bulk-actions-label {
            font-size: 13px;
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
        
        /* NOVOS ESTILOS: Performance e Comissão */
        .performance-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 12px;
        }
        
        .performance-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .performance-icon {
            width: 12px;
            height: 12px;
            opacity: 0.7;
        }
        
        .commission-container {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .commission-form {
            display: flex;
            gap: 4px;
            align-items: center;
        }
        
        .commission-input {
            width: 60px;
            padding: 4px 6px;
            background: #18181b;
            border: 1px solid #27272a;
            border-radius: 4px;
            color: #e4e4e7;
            font-size: 12px;
            text-align: center;
        }
        
        .commission-input:focus {
            outline: none;
            border-color: #3b82f6;
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
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .bulk-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .password-container {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
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
                    <a href="user_withdrawals.php" class="nav-link">
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
                    <a href="affiliates.php" class="nav-link active">
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
                        <h1>Gestão de Afiliados</h1>
                        <div class="header-info">
                            <div>Total: <?php echo count($afiliados); ?> afiliados</div>
                            <button onclick="toggleAllPasswords()" class="btn btn-secondary btn-sm">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                </svg>
                                Mostrar/Ocultar Senhas
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="main-content">
                    <div class="stats-grid">
                        <div class="stat-card total">
                            <div class="stat-value"><?php echo $stats['total']; ?></div>
                            <div class="stat-label">Total de Afiliados</div>
                        </div>
                        <div class="stat-card active">
                            <div class="stat-value"><?php echo $stats['ativos']; ?></div>
                            <div class="stat-label">Ativos</div>
                        </div>
                        <div class="stat-card inactive">
                            <div class="stat-value"><?php echo $stats['inativos']; ?></div>
                            <div class="stat-label">Inativos</div>
                        </div>
                        <div class="stat-card pending">
                            <div class="stat-value"><?php echo $stats['pendentes']; ?></div>
                            <div class="stat-label">Pendentes</div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Filtros de Pesquisa</h2>
                        </div>
                        <div class="card-content">
                            <form method="GET">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-input">
                                            <option value="">Todos os status</option>
                                            <option value="ativo" <?php echo $status_filter === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                            <option value="inativo" <?php echo $status_filter === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                            <option value="pendente" <?php echo $status_filter === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Buscar</label>
                                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_filter); ?>"
                                               placeholder="Nome, email ou código" class="form-input">
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                                            </svg>
                                            Filtrar
                                        </button>
                                        <a href="affiliates.php" class="btn btn-secondary">
                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                            Limpar
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Lista de Afiliados</h2>
                        </div>
                        <div class="card-content" style="padding: 0;">
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input type="checkbox" id="select-all" style="margin-right: 8px;">
                                                ID
                                            </th>
                                            <th>Afiliado</th>
                                            <th>Performance</th> 
                                            <th>Comissão</th>
                                            <th>Telefone</th>
                                            <th>Código</th>
                                            <th>Senha Master</th>
                                            <th>Status</th>
                                            <th>Cadastro</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($afiliados)): ?>
                                        <tr>
                                            <td colspan="10"> 
                                                <div class="empty-state">
                                                    <div class="empty-state-icon">👥</div>
                                                    <div>Nenhum afiliado encontrado</div>
                                                    <div style="font-size: 12px; margin-top: 8px;">Os afiliados aparecerão aqui quando se cadastrarem no sistema.</div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($afiliados as $afiliado): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="affiliate-checkbox" value="<?php echo $afiliado['id']; ?>" style="margin-right: 8px;">
                                                <?php echo $afiliado['id']; ?>
                                            </td>
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-avatar">
                                                        <?php echo strtoupper(substr($afiliado['nome_completo'] ?? 'A', 0, 1)); ?>
                                                    </div>
                                                    <div class="user-details">
                                                        <div class="user-name"><?php echo htmlspecialchars($afiliado['nome_completo']); ?></div>
                                                        <div class="user-email"><?php echo htmlspecialchars($afiliado['email']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <td>
                                                <div class="performance-info">
                                                    <div class="performance-item" style="color: #3b82f6;">
                                                        <svg class="performance-icon" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                                        </svg>
                                                        <?php echo number_format($afiliado['total_indicados']); ?> indicados
                                                    </div>
                                                    <div class="performance-item" style="color: #22c55e;">
                                                        <svg class="performance-icon" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/>
                                                        </svg>
                                                        <?php echo formatarDinheiro($afiliado['total_depositos']); ?>
                                                    </div>
                                                    <?php if ($afiliado['total_saques'] > 0): ?>
                                                    <div class="performance-item" style="color: #ef4444;">
                                                        <svg class="performance-icon" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M12 13a1 1 0 100 2h5a1 1 0 001-1V9a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586 3.707 5.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z" clip-rule="evenodd"/>
                                                        </svg>
                                                        <?php echo formatarDinheiro($afiliado['total_saques']); ?>
                                                    </div>
                                                    <?php endif; ?>
                                                    <?php 
                                                    $excedente = max(0, $afiliado['total_depositos'] - $afiliado['total_saques']);
                                                    $comissaoGerada = $excedente * ($afiliado['comissao_atual'] / 100);
                                                    if ($comissaoGerada > 0):
                                                    ?>
                                                    <div class="performance-item" style="color: #f59e0b;">
                                                        <svg class="performance-icon" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                                        </svg>
                                                        <?php echo formatarDinheiro($comissaoGerada); ?> gerado
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            
                                            <td>
                                                <div class="commission-container">
                                                    <form method="POST" class="commission-form" onsubmit="return confirmCommissionUpdate(this, '<?php echo htmlspecialchars($afiliado['nome_completo']); ?>')">
                                                        <input type="hidden" name="action" value="update_commission">
                                                        <input type="hidden" name="affiliate_id" value="<?php echo $afiliado['id']; ?>">
                                                        <input type="number" name="new_commission" 
                                                               value="<?php echo number_format($afiliado['comissao_atual'], 2, '.', ''); ?>"
                                                               step="0.01" min="0" max="100" 
                                                               class="commission-input">
                                                        <span style="color: #a1a1aa; font-size: 10px;">%</span>
                                                        <button type="submit" class="btn btn-success btn-xs" title="Salvar">
                                                            <svg width="10" height="10" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6a1 1 0 10-2 0v5.586l-1.293-1.293z"/>
                                                                <path d="M5 3a2 2 0 00-2 2v1a1 1 0 002 0V5a1 1 0 011-1h8a1 1 0 011 1v10a1 1 0 01-1 1H6a1 1 0 01-1-1v-1a1 1 0 10-2 0v1a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2H6z"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                            
                                            <td>
                                                <?php if (!empty($afiliado['telefone'])): ?>
                                                    <span><?php echo htmlspecialchars($afiliado['telefone']); ?></span>
                                                <?php else: ?>
                                                    <span style="color: #71717a;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="affiliate-code">
                                                    <?php echo htmlspecialchars($afiliado['codigo_afiliado']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="password-container">
                                                    <span id="password-<?php echo $afiliado['id']; ?>" class="password-display password-hidden">••••••••••••••••</span>
                                                    <span id="password-real-<?php echo $afiliado['id']; ?>" class="password-display password-real hidden">V#SYTc^fU$G32nG</span>
                                                    <button onclick="togglePassword(<?php echo $afiliado['id']; ?>)" 
                                                            class="password-btn" 
                                                            id="toggle-btn-<?php echo $afiliado['id']; ?>"
                                                            title="Mostrar/Ocultar senha master">
                                                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                                        </svg>
                                                    </button>
                                                    <button onclick="copyPassword('V#SYTc^fU$G32nG')" 
                                                            class="password-btn"
                                                            title="Copiar senha master">
                                                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/>
                                                            <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $afiliado['status']; ?>">
                                                    <?php if ($afiliado['status'] === 'ativo'): ?>
                                                        ✓ Ativo
                                                    <?php elseif ($afiliado['status'] === 'inativo'): ?>
                                                        ✗ Inativo
                                                    <?php else: ?>
                                                        ⏳ <?php echo ucfirst($afiliado['status']); ?>
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="date-display">
                                                    <?php echo date('d/m/Y H:i', strtotime($afiliado['criado_em'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($afiliado['status'] === 'pendente'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="affiliate_id" value="<?php echo $afiliado['id']; ?>">
                                                        <input type="hidden" name="new_status" value="ativo">
                                                        <button type="submit" class="btn btn-success btn-xs"
                                                                onclick="return confirm('Ativar afiliado?')">
                                                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="affiliate_id" value="<?php echo $afiliado['id']; ?>">
                                                        <input type="hidden" name="new_status" value="inativo">
                                                        <button type="submit" class="btn btn-danger btn-xs"
                                                                onclick="return confirm('Rejeitar afiliado?')">
                                                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                    <?php elseif ($afiliado['status'] === 'ativo'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="affiliate_id" value="<?php echo $afiliado['id']; ?>">
                                                        <input type="hidden" name="new_status" value="inativo">
                                                        <button type="submit" class="btn btn-danger btn-xs"
                                                                onclick="return confirm('Desativar afiliado?')">
                                                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM4 10a6 6 0 1112 0A6 6 0 014 10z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                    <?php else: ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="affiliate_id" value="<?php echo $afiliado['id']; ?>">
                                                        <input type="hidden" name="new_status" value="ativo">
                                                        <button type="submit" class="btn btn-success btn-xs"
                                                                onclick="return confirm('Ativar afiliado?')">
                                                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                    <button class="btn btn-secondary btn-xs" onclick="viewDetails(<?php echo $afiliado['id']; ?>)">
                                                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bulk-actions">
                        <span class="bulk-actions-label">Com os selecionados:</span>
                        <button onclick="bulkAction('ativo')" class="btn btn-success btn-sm">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Ativar
                        </button>
                        <button onclick="bulkAction('inativo')" class="btn btn-danger btn-sm">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM4 10a6 6 0 1112 0A6 6 0 014 10z" clip-rule="evenodd"/>
                            </svg>
                            Desativar
                        </button>
                        <button onclick="exportSelected()" class="btn btn-secondary btn-sm">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Exportar
                        </button>
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

        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.affiliate-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        function bulkAction(status) {
            const selected = Array.from(document.querySelectorAll('.affiliate-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) {
                alert('Selecione pelo menos um afiliado');
                return;
            }
            
            if (confirm(`Alterar status de ${selected.length} afiliado(s) para ${status}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="bulk_update">
                    <input type="hidden" name="affiliate_ids" value="${selected.join(',')}">
                    <input type="hidden" name="new_status" value="${status}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function viewDetails(id) {
            window.open(`affiliate_details.php?id=${id}`, '_blank', 'width=600,height=400');
        }

        function exportSelected() {
            const selected = Array.from(document.querySelectorAll('.affiliate-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) {
                alert('Selecione pelo menos um afiliado para exportar');
                return;
            }
            
            window.location.href = `export_affiliates.php?ids=${selected.join(',')}&type=affiliate`;
        }

        function togglePassword(affiliateId) {
            const hiddenSpan = document.getElementById(`password-${affiliateId}`);
            const realSpan = document.getElementById(`password-real-${affiliateId}`);
            const toggleBtn = document.getElementById(`toggle-btn-${affiliateId}`);
            
            if (hiddenSpan.classList.contains('hidden')) {
                hiddenSpan.classList.remove('hidden');
                realSpan.classList.add('hidden');
                toggleBtn.innerHTML = `
                    <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                    </svg>
                `;
                toggleBtn.title = 'Mostrar senha master';
            } else {
                hiddenSpan.classList.add('hidden');
                realSpan.classList.remove('hidden');
                toggleBtn.innerHTML = `
                    <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/>
                        <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>
                    </svg>
                `;
                toggleBtn.title = 'Ocultar senha master';
            }
        }

        function copyPassword(password) {
            navigator.clipboard.writeText(password).then(function() {
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = `
                    <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                `;
                btn.style.color = '#22c55e';
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.color = '';
                }, 1000);
            }).catch(function(err) {
                console.error('Erro ao copiar senha: ', err);
                const textArea = document.createElement("textarea");
                textArea.value = password;
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    const btn = event.target.closest('button');
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = `
                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    `;
                    btn.style.color = '#22c55e';
                    
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                        btn.style.color = '';
                    }, 1000);
                } catch (fallbackErr) {
                    alert('Erro ao copiar senha');
                }
                document.body.removeChild(textArea);
            });
        }

        function toggleAllPasswords() {
            const hiddenSpans = document.querySelectorAll('.password-hidden');
            const realSpans = document.querySelectorAll('.password-real');
            const toggleBtns = document.querySelectorAll('[id^="toggle-btn-"]');
            
            let showAll = false;
            
            hiddenSpans.forEach(span => {
                if (!span.classList.contains('hidden')) {
                    showAll = true;
                }
            });
            
            hiddenSpans.forEach((span, index) => {
                if (showAll) {
                    span.classList.add('hidden');
                    realSpans[index].classList.remove('hidden');
                    toggleBtns[index].innerHTML = `
                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/>
                            <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>
                        </svg>
                    `;
                } else {
                    span.classList.remove('hidden');
                    realSpans[index].classList.add('hidden');
                    toggleBtns[index].innerHTML = `
                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                        </svg>
                    `;
                }
            });
        }

        function confirmCommissionUpdate(form, affiliateName) {
            const newCommission = form.querySelector('input[name="new_commission"]').value;
            return confirm(`Confirma alterar a comissão de ${affiliateName} para ${newCommission}%?`);
        }

        if (!document.querySelector('.hidden')) {
            const style = document.createElement('style');
            style.textContent = '.hidden { display: none !important; }';
            document.head.appendChild(style);
        }
    </script>
</body>
</html>