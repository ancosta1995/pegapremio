<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';

$user_filter = $_GET['user'] ?? '';
$date_filter = $_GET['date'] ?? '';
$game_filter = $_GET['game'] ?? '';
$result_filter = $_GET['result'] ?? '';
$affiliate_filter = $_GET['affiliate'] ?? ''; 

$page = (int)($_GET['page'] ?? 1);
$per_page = 50;
$offset = ($page - 1) * $per_page;

$where_conditions = ["1=1"];
$params = [];

if (!empty($user_filter)) {
    $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$user_filter%";
    $params[] = "%$user_filter%";
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(j.data_hora) = ?";
    $params[] = $date_filter;
}

if (!empty($game_filter)) {
    $where_conditions[] = "j.game_id = ?";
    $params[] = $game_filter;
}

if (!empty($result_filter)) {
    if ($result_filter === 'ganhou') {
        $where_conditions[] = "j.valor_ganho > j.valor_aposta";
    } elseif ($result_filter === 'perdeu') {
        $where_conditions[] = "j.valor_ganho < j.valor_aposta";
    } elseif ($result_filter === 'empate') {
        $where_conditions[] = "j.valor_ganho = j.valor_aposta";
    }
}

if (!empty($affiliate_filter)) {
    $where_conditions[] = "(a.email LIKE ? OR a.codigo_afiliado LIKE ?)";
    $params[] = "%$affiliate_filter%";
    $params[] = "%$affiliate_filter%";
}

$where_clause = implode(" AND ", $where_conditions);

$query = "SELECT j.*, u.username, u.email, u.referral_id, a.email as affiliate_email, a.codigo_afiliado
          FROM jogadas j
          LEFT JOIN users u ON j.user_id = u.id
          LEFT JOIN afiliados a ON u.referral_id = a.codigo_afiliado
          WHERE $where_clause
          ORDER BY j.data_hora DESC
          LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jogadas = $stmt->fetchAll();

$count_query = "SELECT COUNT(*) as total 
                FROM jogadas j
                LEFT JOIN users u ON j.user_id = u.id
                LEFT JOIN afiliados a ON u.referral_id = a.codigo_afiliado
                WHERE $where_clause";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch()['total'];
$total_pages = ceil($total_records / $per_page);

$games_query = "SELECT DISTINCT game_id FROM jogadas WHERE game_id IS NOT NULL ORDER BY game_id";
$games_stmt = $pdo->query($games_query);
$available_games = $games_stmt->fetchAll();

$affiliates_query = "SELECT DISTINCT codigo_afiliado, email FROM afiliados ORDER BY email";
$affiliates_stmt = $pdo->query($affiliates_query);
$available_affiliates = $affiliates_stmt->fetchAll();

$stats_query = "SELECT 
    COUNT(*) as total_jogadas,
    COALESCE(SUM(valor_aposta), 0) as total_apostado,
    COALESCE(SUM(valor_ganho), 0) as total_ganho,
    SUM(CASE WHEN valor_ganho > valor_aposta THEN 1 ELSE 0 END) as total_vitorias,
    SUM(CASE WHEN valor_ganho < valor_aposta THEN 1 ELSE 0 END) as total_derrotas,
    COUNT(DISTINCT user_id) as usuarios_ativos
    FROM jogadas j
    LEFT JOIN users u ON j.user_id = u.id
    LEFT JOIN afiliados a ON u.referral_id = a.codigo_afiliado
    WHERE $where_clause";
$stats_stmt = $pdo->prepare($stats_query);
$stats_stmt->execute($params);
$stats = $stats_stmt->fetch();

$stats['total_jogadas'] = $stats['total_jogadas'] ?? 0;
$stats['total_apostado'] = $stats['total_apostado'] ?? 0;
$stats['total_ganho'] = $stats['total_ganho'] ?? 0;
$stats['total_vitorias'] = $stats['total_vitorias'] ?? 0;
$stats['total_derrotas'] = $stats['total_derrotas'] ?? 0;
$stats['usuarios_ativos'] = $stats['usuarios_ativos'] ?? 0;

function isDemo($game_id) {
    $demo_games = [5, 6, 7]; 
    return in_array((int)$game_id, $demo_games);
}

function getResultClass($aposta, $ganho) {
    if ($ganho > $aposta) return 'result-win';
    if ($ganho < $aposta) return 'result-loss';
    return 'result-tie';
}

function getResultText($aposta, $ganho) {
    if ($ganho > $aposta) return 'Vitória';
    if ($ganho < $aposta) return 'Derrota';
    return 'Empate';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Histórico de Jogadas</title>
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
        
        .stat-card.positive .stat-value {
            color: #22c55e;
        }
        
        .stat-card.negative .stat-value {
            color: #ef4444;
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
        
        /* Demo games styling */
        .table tbody tr.demo-game {
            background: rgba(245, 158, 11, 0.05);
        }
        
        .table tbody tr.demo-game:hover {
            background: rgba(245, 158, 11, 0.1);
        }
        
        .demo-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Result badges */
        .result-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        .result-win {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }
        
        .result-loss {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        .result-tie {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
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
        
        /* Affiliate info */
        .affiliate-info {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            color: #a1a1aa;
        }
        
        .affiliate-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 500;
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        
        /* Amount display */
        .amount {
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }
        
        .amount.positive {
            color: #22c55e;
        }
        
        .amount.negative {
            color: #ef4444;
        }
        
        /* Game ID */
        .game-id {
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
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
            padding: 20px;
        }
        
        .pagination a, .pagination span {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            min-width: 40px;
        }
        
        .pagination a {
            background: #27272a;
            color: #e4e4e7;
            border: 1px solid #3f3f46;
            transition: all 0.2s ease;
        }
        
        .pagination a:hover {
            background: #3f3f46;
            border-color: #52525b;
        }
        
        .pagination .current {
            background: #3b82f6;
            color: white;
            border: 1px solid #3b82f6;
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
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
                    <a href="jogadas.php" class="nav-link active">
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
                        <h1>Histórico de Jogadas</h1>
                        <div class="header-info">
                            <div>Total: <?php echo number_format($total_records); ?> jogadas</div>
                            <div>Página <?php echo $page; ?> de <?php echo $total_pages; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="main-content">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo number_format((int)$stats['total_jogadas']); ?></div>
                            <div class="stat-label">Total de Jogadas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">R$ <?php echo number_format((float)$stats['total_apostado'], 2, ',', '.'); ?></div>
                            <div class="stat-label">Total Apostado</div>
                        </div>
                        <div class="stat-card <?php echo (float)$stats['total_ganho'] > (float)$stats['total_apostado'] ? 'positive' : 'negative'; ?>">
                            <div class="stat-value">R$ <?php echo number_format((float)$stats['total_ganho'], 2, ',', '.'); ?></div>
                            <div class="stat-label">Total Ganho</div>
                        </div>
                        <div class="stat-card positive">
                            <div class="stat-value"><?php echo number_format((int)$stats['total_vitorias']); ?></div>
                            <div class="stat-label">Vitórias</div>
                        </div>
                        <div class="stat-card negative">
                            <div class="stat-value"><?php echo number_format((int)$stats['total_derrotas']); ?></div>
                            <div class="stat-label">Derrotas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo number_format((int)$stats['usuarios_ativos']); ?></div>
                            <div class="stat-label">Usuários Ativos</div>
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
                                        <label class="form-label">Usuário</label>
                                        <input type="text" name="user" value="<?php echo htmlspecialchars($user_filter); ?>"
                                               placeholder="Nome ou email do usuário" class="form-input">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Data</label>
                                        <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" class="form-input">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Jogo</label>
                                        <select name="game" class="form-input">
                                            <option value="">Todos os jogos</option>
                                            <?php foreach ($available_games as $game): ?>
                                            <option value="<?php echo $game['game_id']; ?>" <?php echo $game_filter === $game['game_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($game['game_id']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Resultado</label>
                                        <select name="result" class="form-input">
                                            <option value="">Todos os resultados</option>
                                            <option value="ganhou" <?php echo $result_filter === 'ganhou' ? 'selected' : ''; ?>>Vitória</option>
                                            <option value="perdeu" <?php echo $result_filter === 'perdeu' ? 'selected' : ''; ?>>Derrota</option>
                                            <option value="empate" <?php echo $result_filter === 'empate' ? 'selected' : ''; ?>>Empate</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Afiliado</label>
                                        <input type="text" name="affiliate" value="<?php echo htmlspecialchars($affiliate_filter); ?>"
                                               placeholder="Email ou código do afiliado" class="form-input">
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                                            </svg>
                                            Filtrar
                                        </button>
                                        <a href="jogadas.php" class="btn btn-secondary">
                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                            Limpar
                                        </a>
                                    </div>
                                </div>
                                
                                <input type="hidden" name="page" value="1">
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Lista de Jogadas</h2>
                        </div>
                        <div class="card-content" style="padding: 0;">
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Usuário</th>
                                            <th>Afiliado</th>
                                            <th>Jogo</th>
                                            <th>Demo</th>
                                            <th>Valor Aposta</th>
                                            <th>Valor Ganho</th>
                                            <th>Resultado</th>
                                            <th>Lucro/Prejuízo</th>
                                            <th>Data/Hora</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($jogadas)): ?>
                                        <tr>
                                            <td colspan="10">
                                                <div class="empty-state">
                                                    <div class="empty-state-icon">🎮</div>
                                                    <div>Nenhuma jogada encontrada</div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($jogadas as $jogada): ?>
                                        <?php 
                                        $lucro = $jogada['valor_ganho'] - $jogada['valor_aposta']; 
                                        $is_demo = isDemo($jogada['game_id']);
                                        ?>
                                        <tr<?php echo $is_demo ? ' class="demo-game"' : ''; ?>>
                                            <td><?php echo $jogada['id']; ?></td>
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-avatar">
                                                        <?php echo strtoupper(substr($jogada['username'] ?? 'U', 0, 1)); ?>
                                                    </div>
                                                    <span><?php echo htmlspecialchars($jogada['username'] ?? 'N/A'); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($jogada['affiliate_email'])): ?>
                                                <div class="affiliate-info">
                                                    <span class="affiliate-badge">
                                                        <?php echo htmlspecialchars($jogada['codigo_afiliado']); ?>
                                                    </span>
                                                    <br>
                                                    <span style="font-size: 11px; color: #71717a;">
                                                        <?php echo htmlspecialchars($jogada['affiliate_email']); ?>
                                                    </span>
                                                </div>
                                                <?php else: ?>
                                                <span style="color: #71717a;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="game-id">
                                                    <?php echo htmlspecialchars($jogada['game_id'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($is_demo): ?>
                                                    <span class="demo-badge">Demo</span>
                                                <?php else: ?>
                                                    <span style="color: #71717a;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="amount">R$ <?php echo number_format($jogada['valor_aposta'], 2, ',', '.'); ?></span>
                                            </td>
                                            <td>
                                                <span class="amount">R$ <?php echo number_format($jogada['valor_ganho'], 2, ',', '.'); ?></span>
                                            </td>
                                            <td>
                                                <span class="result-badge <?php echo getResultClass($jogada['valor_aposta'], $jogada['valor_ganho']); ?>">
                                                    <?php echo getResultText($jogada['valor_aposta'], $jogada['valor_ganho']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="amount <?php echo $lucro >= 0 ? 'positive' : 'negative'; ?>">
                                                    <?php echo ($lucro >= 0 ? '+' : '') . 'R$ ' . number_format($lucro, 2, ',', '.'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="date-display">
                                                    <?php echo date('d/m/Y H:i:s', strtotime($jogada['data_hora'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M15.707 15.707a1 1 0 01-1.414 0l-5-5a1 1 0 010-1.414l5-5a1 1 0 111.414 1.414L11.414 9H17a1 1 0 110 2h-5.586l3.293 3.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h4a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                        
                        <?php 
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        
                        for ($i = $start; $i <= $end; $i++): ?>
                            <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10.293 15.707a1 1 0 010-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 011.414-1.414l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                <path fill-rule="evenodd" d="M17 10a1 1 0 01-1 1h-4a1 1 0 110-2h4a1 1 0 011 1z" clip-rule="evenodd"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
    </script>
</body>
</html>