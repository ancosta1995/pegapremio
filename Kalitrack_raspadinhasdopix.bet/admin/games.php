<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Gerenciar Jogos</title>
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
        
        .card-content {
            padding: 24px;
        }
        
        /* Game Selector */
        .game-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .game-tab {
            background: #27272a;
            border: 1px solid #3f3f46;
            color: #a1a1aa;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        .game-tab.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .game-tab:hover:not(.active) {
            background: #3f3f46;
            border-color: #52525b;
        }
        
        .game-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }
        
        .game-info h3 {
            color: #3b82f6;
            margin-bottom: 8px;
            font-size: 18px;
        }
        
        .game-info p {
            color: #a1a1aa;
            margin: 0;
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
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
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
        
        /* Symbol Items */
        .symbol-item {
            background: #18181b;
            border: 1px solid #27272a;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .symbol-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .symbol-image {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            background: #27272a;
            object-fit: cover;
        }
        
        .symbol-details h4 {
            color: #fafafa;
            margin-bottom: 4px;
            font-size: 16px;
        }
        
        .symbol-details p {
            color: #a1a1aa;
            font-size: 12px;
            margin: 0;
        }
        
        .symbol-actions {
            display: flex;
            gap: 8px;
        }
        
        /* Frequency Items */
        .frequency-item {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
            padding: 12px;
            background: #18181b;
            border-radius: 6px;
            border: 1px solid #27272a;
        }
        
        .frequency-item label {
            min-width: 120px;
            margin-bottom: 0;
            font-weight: 600;
        }
        
        .frequency-item input {
            width: 100px;
            margin-bottom: 0;
        }
        
        .frequency-total {
            background: #18181b;
            border: 1px solid #27272a;
            border-radius: 6px;
            padding: 16px;
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }
        
        .stat-card {
            background: #18181b;
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
        
        /* Loading */
        .loading {
            text-align: center;
            color: #a1a1aa;
            padding: 40px;
            font-style: italic;
        }
        
        /* Empty State */
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
            
            .game-tabs {
                flex-direction: column;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .tab {
                text-align: left;
                padding: 12px 16px;
            }
            
            .symbol-item {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }
            
            .symbol-actions {
                width: 100%;
                justify-content: flex-start;
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
                    <a href="games.php" class="nav-link active">
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
                        Rastreamento
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
                        <h1>Gerenciar Jogos de Raspadinha</h1>
                        <div class="header-info">
                            <div>Controle completo sobre todos os jogos</div>
                        </div>
                    </div>
                </div>
                
                <div class="main-content">
                    <div id="alerts"></div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                                </svg>
                                Selecionar Jogo
                            </h2>
                        </div>
                        <div class="card-content">
                            <div class="game-tabs" id="gameTabs">
                                <div class="loading">Carregando jogos disponíveis...</div>
                            </div>
                            
                            <div class="game-info" id="gameInfo">
                                <h3 id="currentGameName">Carregando...</h3>
                                <p id="currentGameDescription">Selecione um jogo para começar</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-content" style="padding: 0;">
                            <div class="tabs">
                                <button class="tab active" onclick="showTab('general')">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="margin-right: 8px;">
                                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                                    </svg>
                                    Configurações Gerais
                                </button>
                                <button class="tab" onclick="showTab('symbols')">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="margin-right: 8px;">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    Símbolos/Prêmios
                                </button>
                                <button class="tab" onclick="showTab('frequency')">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="margin-right: 8px;">
                                        <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                                    </svg>
                                    Frequência de Prêmios
                                </button>
                                <button class="tab" onclick="showTab('stats')">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="margin-right: 8px;">
                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                    </svg>
                                    Estatísticas
                                </button>
                            </div>
                            
                            <div id="general" class="tab-content active">
                                <div style="padding: 24px;">
                                    <form id="generalForm">
                                        <div class="form-grid">
                                            <div class="form-group">
                                                <label class="form-label">Valor da Aposta (R$)</label>
                                                <input type="number" step="0.01" id="betCost" name="bet_cost" class="form-input" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Chance de Vitória (%)</label>
                                                <input type="number" step="0.1" min="0" max="100" id="winChance" name="win_chance" class="form-input" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">% Mínimo para Finalizar Raspagem</label>
                                                <input type="number" min="1" max="100" id="minScratch" name="min_scratch_percent" class="form-input" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Status do Jogo</label>
                                                <select id="gameActive" name="game_active" class="form-input">
                                                    <option value="1">Ativo</option>
                                                    <option value="0">Inativo</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            Salvar Configurações
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <div id="symbols" class="tab-content">
                                <div style="padding: 24px;">
                                    <div style="margin-bottom: 32px;">
                                        <h3 style="margin-bottom: 16px; color: #fafafa;">Adicionar Novo Símbolo</h3>
                                        <form id="symbolForm">
                                            <div class="form-grid">
                                                <div class="form-group">
                                                    <label class="form-label">Valor do Prêmio (R$)</label>
                                                    <input type="number" step="0.01" id="symbolValue" class="form-input" required>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Caminho da Imagem</label>
                                                    <input type="text" id="symbolImage" placeholder="/images/exemplo.webp" class="form-input" required>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-success">
                                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                                                </svg>
                                                Adicionar Símbolo
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <h3 style="margin-bottom: 16px; color: #fafafa;">Símbolos Existentes</h3>
                                    <div id="symbolsList">
                                        <div class="loading">Carregando símbolos...</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="frequency" class="tab-content">
                                <div style="padding: 24px;">
                                    <div style="margin-bottom: 24px;">
                                        <h3 style="margin-bottom: 8px; color: #fafafa;">Configurar Frequência de Prêmios</h3>
                                        <p style="color: #a1a1aa; margin: 0;">
                                            Configure a probabilidade de cada prêmio aparecer quando o jogador ganha no jogo selecionado.
                                        </p>
                                    </div>
                                    
                                    <form id="frequencyForm">
                                        <div id="frequencyList">
                                            <div class="loading">Carregando frequências...</div>
                                        </div>
                                        <div class="frequency-total">
                                            <span>Total: <strong id="totalFrequency">0</strong>%</span>
                                            <button type="submit" class="btn btn-primary">
                                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                                Salvar Frequências
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <div id="stats" class="tab-content">
                                <div style="padding: 24px;">
                                    <div id="statsContent">
                                        <div class="loading">Carregando estatísticas...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let currentGameId = 1;
        let availableGames = [];
        let currentConfig = {};
        let currentSymbols = [];
        let currentFrequencies = {};
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
        
        async function loadAvailableGames() {
            try {
                const response = await fetch('/api/multi_game_logic.php?action=get_games');
                const data = await response.json();
                
                if (data.success) {
                    availableGames = data.games;
                    populateGameTabs();
                    selectGame(availableGames[0]?.id || 1); 
                } else {
                    showAlert('Erro ao carregar jogos: ' + (data.error || 'Erro desconhecido'), 'error');
                }
            } catch (error) {
                console.error('Erro ao carregar jogos:', error);
                showAlert('Erro ao carregar jogos', 'error');
            }
        }
        
        function populateGameTabs() {
            const container = document.getElementById('gameTabs');
            container.innerHTML = '';
            
            availableGames.forEach(game => {
                const tab = document.createElement('button');
                tab.className = 'game-tab';
                tab.textContent = game.game_name;
                tab.onclick = () => selectGame(game.id);
                tab.dataset.gameId = game.id;
                
                if (game.id === currentGameId) {
                    tab.classList.add('active');
                }
                
                container.appendChild(tab);
            });
        }
        
        async function selectGame(gameId) {
            currentGameId = gameId;
            
            document.querySelectorAll('.game-tab').forEach(tab => {
                tab.classList.remove('active');
                if (parseInt(tab.dataset.gameId) === gameId) {
                    tab.classList.add('active');
                }
            });
            
            const game = availableGames.find(g => g.id === gameId);
            if (game) {
                document.getElementById('currentGameName').textContent = game.game_name;
                document.getElementById('currentGameDescription').textContent = game.description;
            }
            
            await loadGameConfig();
        }
        
        async function loadGameConfig() {
            try {
                const response = await fetch(`/api/admin_game_config.php?game_id=${currentGameId}`);
                const data = await response.json();
                
                if (data.success) {
                    currentConfig = data.config;
                    currentSymbols = data.symbols;
                    currentFrequencies = data.frequencies;
                    
                    populateGeneralForm();
                    populateSymbolsList();
                    populateFrequencyList();
                } else {
                    showAlert('Erro ao carregar configurações: ' + (data.error || 'Erro desconhecido'), 'error');
                }
            } catch (error) {
                console.error('Erro ao carregar configurações:', error);
                showAlert('Erro ao carregar configurações', 'error');
            }
        }
        
        function populateGeneralForm() {
            document.getElementById('betCost').value = currentConfig.bet_cost || '';
            document.getElementById('winChance').value = (parseFloat(currentConfig.win_chance || 0) * 100).toFixed(1);
            document.getElementById('minScratch').value = currentConfig.min_scratch_percent || '';
            document.getElementById('gameActive').value = currentConfig.game_active || '1';
        }
        
        function populateSymbolsList() {
            const container = document.getElementById('symbolsList');
            
            if (!currentSymbols || currentSymbols.length === 0) {
                container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">🎯</div><h3>Nenhum símbolo encontrado</h3><p>Adicione símbolos para este jogo usando o formulário acima.</p></div>';
                return;
            }
            
            container.innerHTML = '';
            
            currentSymbols.forEach(symbol => {
                const item = document.createElement('div');
                item.className = 'symbol-item';
                
                item.innerHTML = `
                    <div class="symbol-info">
                        <img src="${symbol.symbol_image}" alt="Símbolo" class="symbol-image" onerror="this.style.display='none'">
                        <div class="symbol-details">
                            <h4>R$ ${parseFloat(symbol.symbol_value).toFixed(2)}</h4>
                            <p>${symbol.symbol_image}</p>
                        </div>
                    </div>
                    <div class="symbol-actions">
                        <button class="btn btn-primary btn-sm" onclick="editSymbol(${symbol.id})">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                            </svg>
                            Editar
                        </button>
                        <button class="btn ${symbol.is_active ? 'btn-secondary' : 'btn-success'} btn-sm" onclick="toggleSymbol(${symbol.id}, ${symbol.is_active ? 0 : 1})">
                            ${symbol.is_active ? '❌ Desativar' : '✅ Ativar'}
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteSymbol(${symbol.id})">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd"/>
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 012 0v4a1 1 0 11-2 0V7zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V7a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Excluir
                        </button>
                    </div>
                `;
                
                container.appendChild(item);
            });
        }
        
        function populateFrequencyList() {
            const container = document.getElementById('frequencyList');
            
            if (!currentSymbols || currentSymbols.length === 0) {
                container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📊</div><h3>Nenhum símbolo disponível</h3><p>Adicione símbolos primeiro para configurar as frequências.</p></div>';
                return;
            }
            
            container.innerHTML = '';
            let total = 0;
            
            currentSymbols.forEach(symbol => {
                const frequency = currentFrequencies[symbol.symbol_value] || 0;
                total += parseFloat(frequency);
                
                const item = document.createElement('div');
                item.className = 'frequency-item';
                
                item.innerHTML = `
                    <label class="form-label">R$ ${parseFloat(symbol.symbol_value).toFixed(2)}</label>
                    <input type="number" 
                           step="0.1" 
                           min="0" 
                           max="100" 
                           value="${frequency}" 
                           data-symbol-value="${symbol.symbol_value}"
                           class="form-input"
                           onchange="updateFrequencyTotal()">
                    <span style="color: #a1a1aa;">%</span>
                `;
                
                container.appendChild(item);
            });
            
            updateFrequencyTotal();
        }
        
        function updateFrequencyTotal() {
            let total = 0;
            document.querySelectorAll('#frequencyList input').forEach(input => {
                total += parseFloat(input.value) || 0;
            });
            
            document.getElementById('totalFrequency').textContent = total.toFixed(1);
            
            const totalElement = document.getElementById('totalFrequency');
            if (total > 100) {
                totalElement.style.color = '#ef4444';
            } else if (total < 99) {
                totalElement.style.color = '#f59e0b';
            } else {
                totalElement.style.color = '#22c55e';
            }
        }
        
        function showTab(tabName) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            document.querySelector(`[onclick="showTab('${tabName}')"]`).classList.add('active');
            document.getElementById(tabName).classList.add('active');
            
            if (tabName === 'stats') {
                loadGameStats();
            }
        }
        
        async function loadGameStats() {
            try {
                const response = await fetch(`/api/admin_game_config.php?action=stats&game_id=${currentGameId}`);
                const data = await response.json();
                
                if (data.success) {
                    populateStats(data.stats);
                } else {
                    document.getElementById('statsContent').innerHTML = '<div class="empty-state"><div class="empty-state-icon">❌</div><h3>Erro ao carregar estatísticas</h3></div>';
                }
            } catch (error) {
                console.error('Erro ao carregar estatísticas:', error);
                document.getElementById('statsContent').innerHTML = '<div class="empty-state"><div class="empty-state-icon">❌</div><h3>Erro ao carregar estatísticas</h3></div>';
            }
        }
        
        function populateStats(stats) {
            const container = document.getElementById('statsContent');
            container.innerHTML = `
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value">${stats.total_games || 0}</div>
                        <div class="stat-label">Total de Jogadas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${stats.wins || 0}</div>
                        <div class="stat-label">Vitórias</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${stats.win_rate || 0}%</div>
                        <div class="stat-label">Taxa de Vitória</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">R$ ${stats.total_bet || '0,00'}</div>
                        <div class="stat-label">Total Apostado</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">R$ ${stats.total_prize || '0,00'}</div>
                        <div class="stat-label">Total Pago</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">R$ ${stats.house_edge || '0,00'}</div>
                        <div class="stat-label">Lucro da Casa</div>
                    </div>
                </div>
            `;
        }
        
        document.getElementById('generalForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const config = {};
            
            for (let [key, value] of formData.entries()) {
                if (key === 'win_chance') {
                    config[key] = (parseFloat(value) / 100).toString();
                } else {
                    config[key] = value;
                }
            }
            
            try {
                const response = await fetch('/api/admin_game_config.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update_config',
                        game_id: currentGameId,
                        config: config
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Configurações salvas com sucesso!', 'success');
                    await loadGameConfig();
                } else {
                    showAlert('Erro ao salvar configurações: ' + (result.error || 'Erro desconhecido'), 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                showAlert('Erro ao salvar configurações', 'error');
            }
        });
        
        document.getElementById('symbolForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const value = document.getElementById('symbolValue').value;
            const image = document.getElementById('symbolImage').value;
            
            try {
                const response = await fetch('/api/admin_game_config.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'add_symbol',
                        game_id: currentGameId,
                        symbol: { value: value, image: image }
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Símbolo adicionado com sucesso!', 'success');
                    this.reset();
                    await loadGameConfig();
                } else {
                    showAlert('Erro ao adicionar símbolo: ' + (result.error || 'Erro desconhecido'), 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                showAlert('Erro ao adicionar símbolo', 'error');
            }
        });
        
        document.getElementById('frequencyForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const frequencies = {};
            document.querySelectorAll('#frequencyList input').forEach(input => {
                frequencies[input.dataset.symbolValue] = parseFloat(input.value) || 0;
            });
            
            try {
                const response = await fetch('/api/admin_game_config.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update_frequencies',
                        game_id: currentGameId,
                        frequencies: frequencies
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Frequências salvas com sucesso!', 'success');
                    await loadGameConfig();
                } else {
                    showAlert('Erro ao salvar frequências: ' + (result.error || 'Erro desconhecido'), 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                showAlert('Erro ao salvar frequências', 'error');
            }
        });
        
        async function deleteSymbol(symbolId) {
            if (!confirm('Tem certeza que deseja excluir este símbolo?')) {
                return;
            }
            
            try {
                const response = await fetch('/api/admin_game_config.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete_symbol',
                        game_id: currentGameId,
                        id: symbolId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Símbolo excluído com sucesso!', 'success');
                    await loadGameConfig();
                } else {
                    showAlert('Erro ao excluir símbolo: ' + (result.error || 'Erro desconhecido'), 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                showAlert('Erro ao excluir símbolo', 'error');
            }
        }
        
        async function toggleSymbol(symbolId, isActive) {
            try {
                const response = await fetch('/api/admin_game_config.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'toggle_symbol',
                        game_id: currentGameId,
                        id: symbolId,
                        is_active: isActive
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Status do símbolo alterado com sucesso!', 'success');
                    await loadGameConfig();
                } else {
                    showAlert('Erro ao alterar status: ' + (result.error || 'Erro desconhecido'), 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                showAlert('Erro ao alterar status', 'error');
            }
        }
        
        function editSymbol(symbolId) {
            const symbol = currentSymbols.find(s => s.id === symbolId);
            if (!symbol) return;
            
            const newValue = prompt('Novo valor do símbolo:', symbol.symbol_value);
            const newImage = prompt('Nova imagem do símbolo:', symbol.symbol_image);
            
            if (newValue !== null && newImage !== null) {
                updateSymbol(symbolId, { value: newValue, image: newImage });
            }
        }
        
        async function updateSymbol(symbolId, symbolData) {
            try {
                const response = await fetch('/api/admin_game_config.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update_symbol',
                        game_id: currentGameId,
                        id: symbolId,
                        symbol: symbolData
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Símbolo atualizado com sucesso!', 'success');
                    await loadGameConfig();
                } else {
                    showAlert('Erro ao atualizar símbolo: ' + (result.error || 'Erro desconhecido'), 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                showAlert('Erro ao atualizar símbolo', 'error');
            }
        }
        
        function showAlert(message, type) {
            const alertsContainer = document.getElementById('alerts');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            
            const icon = type === 'success' ? 
                '<svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>' :
                '<svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>';
            
            alert.innerHTML = `${icon} ${message}`;
            
            alertsContainer.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
        
        document.addEventListener('DOMContentLoaded', loadAvailableGames);
    </script>
</body>
</html>