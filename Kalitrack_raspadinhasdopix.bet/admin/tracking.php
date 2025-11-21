<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';

if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $pdo->prepare("INSERT INTO trackings (source, pixel_id, access_token, description) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$_POST['source'], $_POST['pixel_id'], $_POST['access_token'], $_POST['description']]);
                    $success_message = "Pixel cadastrado com sucesso!";
                    break;
                
                case 'edit':
                    $stmt = $pdo->prepare("UPDATE trackings SET source = ?, pixel_id = ?, access_token = ?, description = ? WHERE id = ?");
                    $stmt->execute([$_POST['source'], $_POST['pixel_id'], $_POST['access_token'], $_POST['description'], $_POST['id']]);
                    $success_message = "Pixel atualizado com sucesso!";
                    break;
                
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM trackings WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $success_message = "Pixel removido com sucesso!";
                    break;
            }
        }
    } catch (PDOException $e) {
        $error_message = "Erro na operação: " . $e->getMessage();
    }
}

try {
    $stmt = $pdo->query("SELECT * FROM trackings ORDER BY source, created_at DESC");
    $trackings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $kwai_pixels = array_filter($trackings, function($item) { return $item['source'] === 'kwai'; });
    $facebook_pixels = array_filter($trackings, function($item) { return $item['source'] === 'facebook'; });
} catch (PDOException $e) {
    $error_message = "Erro ao buscar dados: " . $e->getMessage();
}

$editing_pixel = null;
if (isset($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM trackings WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $editing_pixel = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Erro ao buscar pixel para edição: " . $e->getMessage();
    }
}

$total_pixels = count($trackings);
$total_kwai = count($kwai_pixels);
$total_facebook = count($facebook_pixels);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Rastreamento</title>
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
        
        /* Alert Messages */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
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
        
        .stat-card.kwai .stat-value {
            color: #f59e0b;
        }
        
        .stat-card.facebook .stat-value {
            color: #3b82f6;
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
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .platform-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .platform-badge.kwai {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        
        .platform-badge.facebook {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        
        .card-content {
            padding: 24px;
        }
        
        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .form-group {
            margin-bottom: 16px;
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
        
        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
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
        
        /* Pixel ID */
        .pixel-id {
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 12px;
            background: #18181b;
            padding: 4px 6px;
            border-radius: 4px;
            color: #e4e4e7;
        }
        
        /* Date display */
        .date-display {
            font-size: 13px;
            color: #a1a1aa;
        }
        
        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
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
            
            .action-buttons {
                flex-direction: column;
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
                    <a href="affiliates.php" class="nav-link">
                        <svg class="nav-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"/>
                        </svg>
                        Afiliados
                    </a>
                </div>
                <div class="nav-item">
                    <a href="tracking.php" class="nav-link active">
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
                        <h1>Gerenciamento de Rastreamento</h1>
                        <div class="header-info">
                            <div>Total: <?php echo $total_pixels; ?> pixels</div>
                        </div>
                    </div>
                </div>
                
                <div class="main-content">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-error">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $total_pixels; ?></div>
                            <div class="stat-label">Total de Pixels</div>
                        </div>
                        <div class="stat-card kwai">
                            <div class="stat-value"><?php echo $total_kwai; ?></div>
                            <div class="stat-label">Pixels Kwai</div>
                        </div>
                        <div class="stat-card facebook">
                            <div class="stat-value"><?php echo $total_facebook; ?></div>
                            <div class="stat-label">Pixels Facebook</div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                                </svg>
                                <?php echo $editing_pixel ? 'Editar Pixel' : 'Cadastrar Novo Pixel'; ?>
                            </h2>
                        </div>
                        <div class="card-content">
                            <form method="POST">
                                <input type="hidden" name="action" value="<?php echo $editing_pixel ? 'edit' : 'add'; ?>">
                                <?php if ($editing_pixel): ?>
                                    <input type="hidden" name="id" value="<?php echo $editing_pixel['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label">Plataforma</label>
                                        <select name="source" required class="form-input">
                                            <option value="">Selecione a plataforma</option>
                                            <option value="kwai" <?php echo ($editing_pixel && $editing_pixel['source'] === 'kwai') ? 'selected' : ''; ?>>Kwai</option>
                                            <option value="facebook" <?php echo ($editing_pixel && $editing_pixel['source'] === 'facebook') ? 'selected' : ''; ?>>Facebook</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Pixel ID</label>
                                        <input type="text" name="pixel_id" required 
                                               value="<?php echo $editing_pixel ? htmlspecialchars($editing_pixel['pixel_id']) : ''; ?>"
                                               placeholder="Ex: 123456789"
                                               class="form-input">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Access Token</label>
                                    <textarea name="access_token" required rows="3" 
                                              placeholder="Cole aqui o access token da plataforma"
                                              class="form-input form-textarea"><?php echo $editing_pixel ? htmlspecialchars($editing_pixel['access_token']) : ''; ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Descrição (opcional)</label>
                                    <input type="text" name="description" 
                                           value="<?php echo $editing_pixel ? htmlspecialchars($editing_pixel['description']) : ''; ?>"
                                           placeholder="Descreva o uso deste pixel"
                                           class="form-input">
                                </div>
                                
                                <div class="action-buttons">
                                    <button type="submit" class="btn btn-primary">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <?php echo $editing_pixel ? 'Atualizar Pixel' : 'Cadastrar Pixel'; ?>
                                    </button>
                                    <?php if ($editing_pixel): ?>
                                        <a href="tracking.php" class="btn btn-secondary">
                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                            Cancelar
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <span class="platform-badge kwai">KWAI</span>
                                Pixels Kwai
                            </h2>
                        </div>
                        <div class="card-content" style="padding: 0;">
                            <div class="table-container">
                                <?php if (empty($kwai_pixels)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">🎯</div>
                                    <h3>Nenhum pixel do Kwai</h3>
                                    <p>Cadastre seu primeiro pixel do Kwai para começar o rastreamento.</p>
                                </div>
                                <?php else: ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Pixel ID</th>
                                            <th>Descrição</th>
                                            <th>Criado em</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($kwai_pixels as $pixel): ?>
                                        <tr>
                                            <td><?php echo $pixel['id']; ?></td>
                                            <td>
                                                <span class="pixel-id">
                                                    <?php echo htmlspecialchars($pixel['pixel_id']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($pixel['description'] ?: '-'); ?></td>
                                            <td>
                                                <span class="date-display">
                                                    <?php echo date('d/m/Y H:i', strtotime($pixel['created_at'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="tracking.php?edit=<?php echo $pixel['id']; ?>" class="btn btn-primary btn-sm">
                                                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                                        </svg>
                                                        Editar
                                                    </a>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja remover este pixel?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $pixel['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd"/>
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 012 0v4a1 1 0 11-2 0V7zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V7a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                            </svg>
                                                            Remover
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
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <span class="platform-badge facebook">FACEBOOK</span>
                                Pixels Facebook
                            </h2>
                        </div>
                        <div class="card-content" style="padding: 0;">
                            <div class="table-container">
                                <?php if (empty($facebook_pixels)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">📊</div>
                                    <h3>Nenhum pixel do Facebook</h3>
                                    <p>Cadastre seu primeiro pixel do Facebook para começar o rastreamento.</p>
                                </div>
                                <?php else: ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Pixel ID</th>
                                            <th>Descrição</th>
                                            <th>Criado em</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($facebook_pixels as $pixel): ?>
                                        <tr>
                                            <td><?php echo $pixel['id']; ?></td>
                                            <td>
                                                <span class="pixel-id">
                                                    <?php echo htmlspecialchars($pixel['pixel_id']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($pixel['description'] ?: '-'); ?></td>
                                            <td>
                                                <span class="date-display">
                                                    <?php echo date('d/m/Y H:i', strtotime($pixel['created_at'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="tracking.php?edit=<?php echo $pixel['id']; ?>" class="btn btn-primary btn-sm">
                                                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                                        </svg>
                                                        Editar
                                                    </a>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja remover este pixel?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $pixel['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd"/>
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 012 0v4a1 1 0 11-2 0V7zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V7a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                            </svg>
                                                            Remover
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
    </script>
</body>
</html>