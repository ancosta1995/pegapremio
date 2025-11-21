<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';

if ($_POST['action'] ?? '' === 'update_balance') {
    $user_id = intval($_POST['user_id']);
    $new_balance = floatval($_POST['new_balance']);
    
    $stmt = $pdo->prepare("UPDATE users SET saldo = ? WHERE id = ?");
    if ($stmt->execute([$new_balance, $user_id])) {
        $success_message = "Saldo atualizado com sucesso!";
    } else {
        $error_message = "Erro ao atualizar saldo.";
    }
}

$email_filter      = $_GET['email'] ?? '';
$order_by          = $_GET['order_by'] ?? 'id_desc';
$only_depositors   = $_GET['only_depositors'] ?? '';

$query  = "
    SELECT 
        u.*,
        COALESCE(
            SUM(CASE WHEN t.status = 'pago' THEN t.amount ELSE 0 END),
            0
        ) AS total_depositado
    FROM users u
    LEFT JOIN transactions t 
        ON u.id = t.user_id
";

$params = [];
$where  = [];

if (!empty($email_filter)) {
    $where[]  = "u.email LIKE ?";
    $params[] = "%{$email_filter}%";
}

if ($where) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " GROUP BY u.id";

if ($only_depositors === '1') {
    $query .= " HAVING total_depositado > 0";
}

switch ($order_by) {
    case 'deposit_desc':
        $query .= " ORDER BY total_depositado DESC, u.id DESC";
        break;
    case 'deposit_asc':
        $query .= " ORDER BY total_depositado ASC, u.id DESC";
        break;
    case 'id_asc':
        $query .= " ORDER BY u.id ASC";
        break;
    default:
        $query .= " ORDER BY u.id DESC";
        break;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN saldo > 0 THEN 1 ELSE 0 END) as com_saldo,
    SUM(saldo) as saldo_total,
    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as hoje
    FROM users";
$stats = $pdo->query($stats_query)->fetch();

$depositors_query = "SELECT COUNT(DISTINCT u.id) as total_depositantes
    FROM users u 
    INNER JOIN transactions t ON u.id = t.user_id 
    WHERE t.status = 'pago'";
$depositors_stats = $pdo->query($depositors_query)->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Usuários</title>
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
        .stat-card.balance .stat-value { color: #22c55e; }
        .stat-card.depositors .stat-value { color: #f59e0b; }
        .stat-card.today .stat-value { color: #8b5cf6; }
        
        /* Alert Messages */
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
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
        
        .form-checkbox {
            margin-right: 8px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .checkbox-label {
            font-size: 13px;
            color: #e4e4e7;
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
        
        .btn-group {
            display: flex;
            gap: 8px;
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
        
        /* Currency display */
        .currency {
            font-variant-numeric: tabular-nums;
            font-weight: 500;
        }
        
        .currency.positive {
            color: #22c55e;
        }
        
        .currency.neutral {
            color: #e4e4e7;
        }
        
        /* Date display */
        .date-display {
            font-size: 13px;
            color: #a1a1aa;
        }
        
        /* Referral badge */
        .referral-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
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
        
        /* Modal */
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal.hidden {
            display: none;
        }
        
        .modal-content {
            background: #111111;
            border: 1px solid #27272a;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            margin: 20px;
        }
        
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #27272a;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #fafafa;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: #a1a1aa;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: color 0.2s ease;
        }
        
        .modal-close:hover {
            color: #e4e4e7;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid #27272a;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        /* Filter info */
        .filter-info {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 13px;
            color: #a1a1aa;
        }
        
        .filter-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
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
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .header-info {
                flex-direction: column;
                align-items: flex-end;
                gap: 8px;
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
                    <a href="users.php" class="nav-link active">
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
                        <h1>Gestão de Usuários</h1>
                        <div class="header-info">
                            <div>Total: <?php echo count($users); ?> usuário(s)</div>
                            <?php if (!empty($email_filter)): ?>
                                <div>Filtro: <strong><?php echo htmlspecialchars($email_filter); ?></strong></div>
                            <?php endif; ?>
                            <?php if ($only_depositors === '1'): ?>
                                <div class="filter-badge">Apenas depositantes</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="main-content">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-error">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="stats-grid">
                        <div class="stat-card total">
                            <div class="stat-value"><?php echo $stats['total']; ?></div>
                            <div class="stat-label">Total de Usuários</div>
                        </div>
                        <div class="stat-card balance">
                            <div class="stat-value">R$ <?php echo number_format($stats['saldo_total'], 2, ',', '.'); ?></div>
                            <div class="stat-label">Saldo Total</div>
                        </div>
                        <div class="stat-card depositors">
                            <div class="stat-value"><?php echo $depositors_stats['total_depositantes']; ?></div>
                            <div class="stat-label">Depositantes</div>
                        </div>
                        <div class="stat-card today">
                            <div class="stat-value"><?php echo $stats['hoje']; ?></div>
                            <div class="stat-label">Cadastros Hoje</div>
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
                                        <label class="form-label">Filtrar por Email</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($email_filter); ?>"
                                               placeholder="Digite o email do usuário..." class="form-input">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Ordenar por</label>
                                        <select name="order_by" class="form-input">
                                            <option value="id_desc" <?php echo $order_by === 'id_desc' ? 'selected' : ''; ?>>ID (Mais recente)</option>
                                            <option value="id_asc" <?php echo $order_by === 'id_asc' ? 'selected' : ''; ?>>ID (Mais antigo)</option>
                                            <option value="deposit_desc" <?php echo $order_by === 'deposit_desc' ? 'selected' : ''; ?>>Maior depositante</option>
                                            <option value="deposit_asc" <?php echo $order_by === 'deposit_asc' ? 'selected' : ''; ?>>Menor depositante</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="checkbox-group">
                                            <input type="checkbox" id="only_depositors" name="only_depositors" value="1"
                                                   <?php echo $only_depositors === '1' ? 'checked' : ''; ?> class="form-checkbox">
                                            <label for="only_depositors" class="checkbox-label">Apenas usuários que depositaram</label>
                                        </div>
                                        <div class="btn-group">
                                            <button type="submit" class="btn btn-primary">
                                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                                                </svg>
                                                Filtrar
                                            </button>
                                            <a href="users.php" class="btn btn-secondary">
                                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                </svg>
                                                Limpar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Lista de Usuários</h2>
                            <?php if ($order_by !== 'id_desc'): ?>
                                <div class="filter-info">
                                    Ordenado por: 
                                    <strong>
                                        <?php
                                        switch ($order_by) {
                                            case 'deposit_desc': echo 'Maior depositante'; break;
                                            case 'deposit_asc': echo 'Menor depositante'; break;
                                            case 'id_asc': echo 'ID (Mais antigo)'; break;
                                        }
                                        ?>
                                    </strong>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-content" style="padding: 0;">
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Usuário</th>
                                            <th>Saldo</th>
                                            <th>Total Depositado</th>
                                            <th>Criado Em</th>
                                            <th>Referral ID</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="7">
                                                <div class="empty-state">
                                                    <div class="empty-state-icon">👥</div>
                                                    <div>Nenhum usuário encontrado</div>
                                                    <div style="font-size: 12px; margin-top: 8px;">Tente ajustar os filtros ou remover algumas restrições.</div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <span style="font-weight: 600;"><?php echo $user['id']; ?></span>
                                            </td>
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-avatar">
                                                        <?php echo strtoupper(substr($user['username'] ?? 'U', 0, 1)); ?>
                                                    </div>
                                                    <div class="user-details">
                                                        <div class="user-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                                        <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="currency <?php echo $user['saldo'] > 0 ? 'positive' : 'neutral'; ?>">
                                                    R$ <?php echo number_format($user['saldo'], 2, ',', '.'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="currency positive">
                                                    R$ <?php echo number_format($user['total_depositado'], 2, ',', '.'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="date-display">
                                                    <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($user['referral_id']): ?>
                                                    <span class="referral-badge">
                                                        <?php echo htmlspecialchars($user['referral_id']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #71717a;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button onclick="openBalanceModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', <?php echo $user['saldo']; ?>)"
                                                        class="btn btn-primary btn-sm">
                                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                                    </svg>
                                                    Editar Saldo
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="balanceModal" class="modal hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Editar Saldo do Usuário</h3>
                <button onclick="closeBalanceModal()" class="modal-close">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
            <form method="POST" onsubmit="return confirmBalanceUpdate()">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_balance">
                    <input type="hidden" name="user_id" id="modalUserId">
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">Usuário:</label>
                        <div style="padding: 12px; background: #18181b; border: 1px solid #27272a; border-radius: 6px;">
                            <p id="modalUsername" style="color: #fafafa; font-weight: 500; margin: 0;"></p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_balance" class="form-label">Novo Saldo (R$):</label>
                        <input type="number" step="0.01" min="0" id="new_balance" name="new_balance" 
                               required class="form-input">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeBalanceModal()" class="btn btn-secondary">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Atualizar Saldo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        function openBalanceModal(userId, username, currentBalance) {
            document.getElementById('modalUserId').value = userId;
            document.getElementById('modalUsername').textContent = username;
            document.getElementById('new_balance').value = currentBalance.toFixed(2);
            document.getElementById('balanceModal').classList.remove('hidden');
        }

        function closeBalanceModal() {
            document.getElementById('balanceModal').classList.add('hidden');
        }

        function confirmBalanceUpdate() {
            const username = document.getElementById('modalUsername').textContent;
            const newBalance = document.getElementById('new_balance').value;
            
            return confirm(`Tem certeza que deseja atualizar o saldo do usuário "${username}" para R$ ${parseFloat(newBalance).toFixed(2).replace('.', ',')}?`);
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeBalanceModal();
            }
        });

        document.getElementById('balanceModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeBalanceModal();
            }
        });
    </script>
</body>
</html>