<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';

$status_filter = $_GET['status'] ?? '';
$user_filter = $_GET['user'] ?? '';
$date_filter = $_GET['date'] ?? '';

$query = "SELECT t.*, u.username FROM transactions t
          LEFT JOIN users u ON t.user_id = u.id
          WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $query .= " AND t.status = ?";
    $params[] = $status_filter;
}

if (!empty($user_filter)) {
    $query .= " AND (u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$user_filter%";
    $params[] = "%$user_filter%";
}

if (!empty($date_filter)) {
    $query .= " AND DATE(t.created_at) = ?";
    $params[] = $date_filter;
}

$query .= " ORDER BY t.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$deposits = $stmt->fetchAll();

if ($_POST['action'] ?? false) {
    if ($_POST['action'] === 'update_status') {
        $transaction_id = $_POST['transaction_id'];
        $new_status = $_POST['new_status'];
        
        $stmt = $pdo->prepare("UPDATE transactions SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $transaction_id]);
        
        if ($new_status === 'pago') {
            $stmt = $pdo->prepare("SELECT user_id, amount FROM transactions WHERE id = ?");
            $stmt->execute([$transaction_id]);
            $transaction = $stmt->fetch();
            
            if ($transaction) {
                $stmt = $pdo->prepare("UPDATE users SET saldo = saldo + ? WHERE id = ?");
                $stmt->execute([$transaction['amount'], $transaction['user_id']]);
            }
        }
        
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    if ($_POST['action'] === 'bulk_update') {
        $transaction_ids = explode(',', $_POST['transaction_ids']);
        $new_status = $_POST['new_status'];
        
        foreach ($transaction_ids as $id) {
            $stmt = $pdo->prepare("UPDATE transactions SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $id]);
            
            if ($new_status === 'pago') {
                $stmt = $pdo->prepare("SELECT user_id, amount FROM transactions WHERE id = ?");
                $stmt->execute([$id]);
                $transaction = $stmt->fetch();
                
                if ($transaction) {
                    $stmt = $pdo->prepare("UPDATE users SET saldo = saldo + ? WHERE id = ?");
                    $stmt->execute([$transaction['amount'], $transaction['user_id']]);
                }
            }
        }
        
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Depósitos</title>
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
        
        .status-pago {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }
        
        .status-pendente {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        
        .status-cancelado {
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
        
        /* Transaction ID */
        .transaction-id {
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
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: #111111;
            border: 1px solid #27272a;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #27272a;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 16px;
            font-weight: 600;
            color: #fafafa;
            margin: 0;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .qr-code-display {
            background: #18181b;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            word-break: break-all;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 12px;
            color: #e4e4e7;
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
            
            .bulk-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
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
                    <a href="depositos.php" class="nav-link active">
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
                        <h1>Gestão de Depósitos</h1>
                        <div class="header-info">
                            <div>Total: <?php echo count($deposits); ?> registros</div>
                        </div>
                    </div>
                </div>
                
                <div class="main-content">
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
                                            <option value="pendente" <?php echo $status_filter === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                            <option value="pago" <?php echo $status_filter === 'pago' ? 'selected' : ''; ?>>Pago</option>
                                            <option value="cancelado" <?php echo $status_filter === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                        </select>
                                    </div>
                                    
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
                                        <button type="submit" class="btn btn-primary">
                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                                            </svg>
                                            Filtrar
                                        </button>
                                        <a href="depositos.php" class="btn btn-secondary">
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
                            <h2 class="card-title">Lista de Depósitos</h2>
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
                                            <th>Usuário</th>
                                            <th>Valor</th>
                                            <th>Status</th>
                                            <th>QR Code</th>
                                            <th>Transaction ID</th>
                                            <th>Data</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($deposits)): ?>
                                        <tr>
                                            <td colspan="8">
                                                <div class="empty-state">
                                                    <div class="empty-state-icon">📥</div>
                                                    <div>Nenhum depósito encontrado</div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($deposits as $deposit): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="deposit-checkbox" value="<?php echo $deposit['id']; ?>" style="margin-right: 8px;">
                                                <?php echo $deposit['id']; ?>
                                            </td>
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-avatar">
                                                        <?php echo strtoupper(substr($deposit['username'] ?? 'U', 0, 1)); ?>
                                                    </div>
                                                    <span><?php echo htmlspecialchars($deposit['username'] ?? 'N/A'); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="amount">R$ <?php echo number_format($deposit['amount'], 2, ',', '.'); ?></span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $deposit['status']; ?>">
                                                    <?php echo ucfirst($deposit['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($deposit['qr_code_text'])): ?>
                                                    <button onclick="showQRCode('<?php echo htmlspecialchars($deposit['qr_code_text']); ?>')"
                                                             class="btn btn-secondary btn-xs">
                                                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd"/>
                                                        </svg>
                                                        Ver QR
                                                    </button>
                                                <?php else: ?>
                                                    <span style="color: #71717a;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($deposit['transaction_id'])): ?>
                                                    <span class="transaction-id">
                                                        <?php echo htmlspecialchars(substr($deposit['transaction_id'], 0, 20)) . (strlen($deposit['transaction_id']) > 20 ? '...' : ''); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #71717a;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="date-display">
                                                    <?php echo date('d/m/Y H:i', strtotime($deposit['created_at'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($deposit['status'] === 'pendente'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="transaction_id" value="<?php echo $deposit['id']; ?>">
                                                        <input type="hidden" name="new_status" value="pago">
                                                        <button type="submit" class="btn btn-success btn-xs"
                                                                 onclick="return confirm('Confirmar pagamento? O valor será adicionado ao saldo do usuário.')">
                                                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="transaction_id" value="<?php echo $deposit['id']; ?>">
                                                        <input type="hidden" name="new_status" value="cancelado">
                                                        <button type="submit" class="btn btn-danger btn-xs"
                                                                onclick="return confirm('Cancelar depósito?')">
                                                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                    <button class="btn btn-secondary btn-xs" onclick="viewDetails(<?php echo $deposit['id']; ?>)">
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
                        <button onclick="bulkAction('pago')" class="btn btn-success btn-sm">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Aprovar
                        </button>
                        <button onclick="bulkAction('cancelado')" class="btn btn-danger btn-sm">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Cancelar
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
    
    <div id="qrModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Código PIX</h3>
                <button onclick="closeModal('qrModal')" style="background: none; border: none; color: #a1a1aa; cursor: pointer;">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="qr-code-display" id="qrCodeText"></div>
                <button onclick="copyQRCode()" class="btn btn-primary">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/>
                        <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"/>
                    </svg>
                    Copiar Código
                </button>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.deposit-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        function bulkAction(status) {
            const selected = Array.from(document.querySelectorAll('.deposit-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) {
                alert('Selecione pelo menos um depósito');
                return;
            }
            
            if (confirm(`Alterar status de ${selected.length} depósito(s) para ${status}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="bulk_update">
                    <input type="hidden" name="transaction_ids" value="${selected.join(',')}">
                    <input type="hidden" name="new_status" value="${status}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function viewDetails(id) {
            window.open(`transaction_details.php?id=${id}`, '_blank', 'width=600,height=400');
        }

        function exportSelected() {
            const selected = Array.from(document.querySelectorAll('.deposit-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) {
                alert('Selecione pelo menos um depósito para exportar');
                return;
            }
            
            window.location.href = `export_transactions.php?ids=${selected.join(',')}&type=deposit`;
        }

        let currentQRCode = '';

        function showQRCode(qrText) {
            currentQRCode = qrText;
            document.getElementById('qrCodeText').textContent = qrText;
            document.getElementById('qrModal').classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        function copyQRCode() {
            navigator.clipboard.writeText(currentQRCode).then(() => {
                alert('Código PIX copiado para a área de transferência!');
            }).catch(() => {
                const textArea = document.createElement('textarea');
                textArea.value = currentQRCode;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('Código PIX copiado para a área de transferência!');
            });
        }

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
            }
        });
    </script>
</body>
</html>
