<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM transactions WHERE status = 'pago'");
    $total_depositos = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM transactions WHERE status = 'pago'");
    $total_valor_pago = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM transactions WHERE status = 'pendente'");
    $total_valor_pendente = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM saques WHERE status = 'pendente'");
    $saques_usuarios_pendentes = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM saques WHERE status = 'aprovado'");
    $saques_usuarios_aprovados = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM saques_afiliados WHERE status = 'pendente'");
    $saques_afiliados_pendentes = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM saques_afiliados WHERE status = 'aprovado'");
    $saques_afiliados_aprovados = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_usuarios = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM saques WHERE status = 'aprovado' AND DATE(atualizado_em) = CURDATE()");
    $saques_aprovados_hoje = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM transactions WHERE status = 'pago' AND DATE(created_at) = CURDATE()");
    $depositos_hoje = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM transactions WHERE status = 'pago' AND DATE(created_at) = CURDATE()");
    $valor_depositos_hoje = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()");
    $usuarios_hoje = $stmt->fetch()['total'];
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM games WHERE status = 'ativo'");
        $jogos_ativos = $stmt->fetch()['total'] ?? 0;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM game_plays WHERE DATE(created_at) = CURDATE()");
        $jogadas_hoje = $stmt->fetch()['total'] ?? 0;
    } catch (PDOException $e) {
        $jogos_ativos = 0;
        $jogadas_hoje = 0;
    }
    
} catch (PDOException $e) {
    echo "Erro na query: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo</title>
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
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 32px;
        }
        
        .page-header {
            margin-bottom: 32px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #fafafa;
            margin-bottom: 8px;
        }
        
        .page-subtitle {
            color: #a1a1aa;
            font-size: 16px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: #111111;
            border: 1px solid #27272a;
            border-radius: 12px;
            padding: 24px;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent-color, #3b82f6);
        }
        
        .stat-card:hover {
            border-color: #3f3f46;
            transform: translateY(-2px);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .stat-title {
            font-size: 14px;
            font-weight: 600;
            color: #a1a1aa;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .stat-icon {
            width: 24px;
            height: 24px;
            opacity: 0.6;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: #fafafa;
            margin-bottom: 8px;
            font-variant-numeric: tabular-nums;
        }
        
        .stat-change {
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .stat-change.positive {
            color: #22c55e;
        }
        
        .stat-change.negative {
            color: #ef4444;
        }
        
        .stat-change.neutral {
            color: #a1a1aa;
        }
        
        /* Color Variants */
        .stat-card.blue::before { background: #3b82f6; }
        .stat-card.green::before { background: #22c55e; }
        .stat-card.yellow::before { background: #f59e0b; }
        .stat-card.red::before { background: #ef4444; }
        .stat-card.purple::before { background: #8b5cf6; }
        .stat-card.cyan::before { background: #06b6d4; }
        .stat-card.orange::before { background: #f97316; }
        .stat-card.pink::before { background: #ec4899; }
        
        /* Recent Activity */
        .activity-section {
            background: #111111;
            border: 1px solid #27272a;
            border-radius: 12px;
            margin-bottom: 32px;
        }
        
        .activity-header {
            padding: 24px;
            border-bottom: 1px solid #27272a;
        }
        
        .activity-title {
            font-size: 18px;
            font-weight: 600;
            color: #fafafa;
        }
        
        .activity-content {
            padding: 24px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #27272a;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            background: #18181b;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-text {
            color: #e4e4e7;
            font-weight: 500;
            margin-bottom: 4px;
        }
        
        .activity-time {
            color: #a1a1aa;
            font-size: 13px;
        }
        
        .activity-value {
            color: #22c55e;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        
        .action-card {
            background: #111111;
            border: 1px solid #27272a;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .action-card:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
        }
        
        .action-icon {
            width: 48px;
            height: 48px;
            background: #18181b;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        
        .action-title {
            font-size: 16px;
            font-weight: 600;
            color: #fafafa;
            margin-bottom: 8px;
        }
        
        .action-description {
            color: #a1a1aa;
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }
            
            .page-title {
                font-size: 24px;
            }
            
            .stat-value {
                font-size: 28px;
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
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2 class="sidebar-title">Painel Administrativo</h2>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
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
        
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Visão geral do sistema e métricas principais</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-header">
                        <div class="stat-title">Total de Depósitos</div>
                        <svg class="stat-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="stat-value"><?php echo number_format($total_depositos, 0, ',', '.'); ?></div>
                    <div class="stat-change neutral">
                        <span>Hoje: <?php echo $depositos_hoje; ?></span>
                    </div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-header">
                        <div class="stat-title">Valor Depósitos Pagos</div>
                        <svg class="stat-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="stat-value">R$ <?php echo number_format($total_valor_pago, 2, ',', '.'); ?></div>
                    <div class="stat-change positive">
                        <span>Hoje: R$ <?php echo number_format($valor_depositos_hoje, 2, ',', '.'); ?></span>
                    </div>
                </div>
                
                <div class="stat-card yellow">
                    <div class="stat-header">
                        <div class="stat-title">Depósitos Pendentes</div>
                        <svg class="stat-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="stat-value">R$ <?php echo number_format($total_valor_pendente, 2, ',', '.'); ?></div>
                    <div class="stat-change neutral">
                        <span>Aguardando processamento</span>
                    </div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-header">
                        <div class="stat-title">Jogos Ativos</div>
                        <svg class="stat-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="stat-value"><?php echo number_format($jogos_ativos, 0, ',', '.'); ?></div>
                    <div class="stat-change positive">
                        <span>Jogadas hoje: <?php echo number_format($jogadas_hoje, 0, ',', '.'); ?></span>
                    </div>
                </div>
                
                <div class="stat-card purple">
                    <div class="stat-header">
                        <div class="stat-title">Total de Usuários</div>
                        <svg class="stat-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                    </div>
                    <div class="stat-value"><?php echo number_format($total_usuarios, 0, ',', '.'); ?></div>
                    <div class="stat-change positive">
                        <span>Hoje: +<?php echo $usuarios_hoje; ?></span>
                    </div>
                </div>
                
                <div class="stat-card red">
                    <div class="stat-header">
                        <div class="stat-title">Saques Usuários Pendentes</div>
                        <svg class="stat-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="stat-value"><?php echo number_format($saques_usuarios_pendentes, 0, ',', '.'); ?></div>
                    <div class="stat-change negative">
                        <span>Requer atenção</span>
                    </div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-header">
                        <div class="stat-title">Saques Usuários Aprovados</div>
                        <svg class="stat-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="stat-value"><?php echo number_format($saques_usuarios_aprovados, 0, ',', '.'); ?></div>
                    <div class="stat-change positive">
                        <span>Hoje: <?php echo $saques_aprovados_hoje; ?></span>
                    </div>
                </div>
                
                <div class="stat-card red">
                    <div class="stat-header">
                        <div class="stat-title">Saques Afiliados Pendentes</div>
                        <svg class="stat-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="stat-value"><?php echo number_format($saques_afiliados_pendentes, 0, ',', '.'); ?></div>
                    <div class="stat-change negative">
                        <span>Requer atenção</span>
                    </div>
                </div>
                
                <div class="stat-card cyan">
                    <div class="stat-header">
                        <div class="stat-title">Saques Afiliados Aprovados</div>
                        <svg class="stat-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="stat-value"><?php echo number_format($saques_afiliados_aprovados, 0, ',', '.'); ?></div>
                    <div class="stat-change positive">
                        <span>Processados com sucesso</span>
                    </div>
                </div>
            </div>
            
            <div class="quick-actions">
                <a href="depositos.php" class="action-card">
                    <div class="action-icon">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="action-title">Gerenciar Depósitos</div>
                    <div class="action-description">Visualizar e processar depósitos pendentes</div>
                </a>
                
                <a href="games.php" class="action-card">
                    <div class="action-icon">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="action-title">Gerenciar Jogos</div>
                    <div class="action-description">Configurar e monitorar jogos ativos</div>
                </a>
                
                <a href="user_withdrawals.php" class="action-card">
                    <div class="action-icon">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 11-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="action-title">Saques de Usuários</div>
                    <div class="action-description">Aprovar ou rejeitar saques pendentes</div>
                </a>
                
                <a href="affiliate_withdrawals.php" class="action-card">
                    <div class="action-icon">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="action-title">Saques de Afiliados</div>
                    <div class="action-description">Processar comissões de afiliados</div>
                </a>
                
                <a href="users.php" class="action-card">
                    <div class="action-icon">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                    </div>
                    <div class="action-title">Gerenciar Usuários</div>
                    <div class="action-description">Visualizar e editar contas de usuários</div>
                </a>
            </div>
            
            <div class="activity-section">
                <div class="activity-header">
                    <h2 class="activity-title">Atividade Recente</h2>
                </div>
                <div class="activity-content">
                    <?php if ($jogadas_hoje > 0): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <svg width="20" height="20" fill="#f97316" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="activity-details">
                            <div class="activity-text"><?php echo $jogadas_hoje; ?> jogadas realizadas hoje</div>
                            <div class="activity-time">Hoje</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($depositos_hoje > 0): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <svg width="20" height="20" fill="#22c55e" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="activity-details">
                            <div class="activity-text"><?php echo $depositos_hoje; ?> novos depósitos processados hoje</div>
                            <div class="activity-time">Hoje</div>
                        </div>
                        <div class="activity-value">R$ <?php echo number_format($valor_depositos_hoje, 2, ',', '.'); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($usuarios_hoje > 0): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <svg width="20" height="20" fill="#3b82f6" viewBox="0 0 20 20">
                                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                            </svg>
                        </div>
                        <div class="activity-details">
                            <div class="activity-text"><?php echo $usuarios_hoje; ?> novos usuários registrados</div>
                            <div class="activity-time">Hoje</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($saques_aprovados_hoje > 0): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <svg width="20" height="20" fill="#06b6d4" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 11-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="activity-details">
                            <div class="activity-text"><?php echo $saques_aprovados_hoje; ?> saques aprovados hoje</div>
                            <div class="activity-time">Hoje</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($saques_usuarios_pendentes > 0 || $saques_afiliados_pendentes > 0): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <svg width="20" height="20" fill="#f59e0b" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="activity-details">
                            <div class="activity-text"><?php echo ($saques_usuarios_pendentes + $saques_afiliados_pendentes); ?> saques aguardando aprovação</div>
                            <div class="activity-time">Requer atenção</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>