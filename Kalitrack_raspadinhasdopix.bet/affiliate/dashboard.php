<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['afiliado_id'])) {
    header('Location: login.php');
    exit;
}

if ($_GET['action'] ?? '' === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

$afiliadoId = $_SESSION['afiliado_id'];
$dadosAfiliado = getDadosAfiliado($afiliadoId);

if (!$dadosAfiliado) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$_SESSION['afiliado_nome'] = $dadosAfiliado['nome_completo'];
$_SESSION['afiliado_email'] = $dadosAfiliado['email'];
$_SESSION['codigo_afiliado'] = $dadosAfiliado['codigo_afiliado'];

$stats = getAffiliateStats($afiliadoId);
$urlIndicacao = gerarUrlIndicacao($dadosAfiliado['codigo_afiliado']);

$activeSection = $_GET['section'] ?? 'overview';
$validSections = ['overview', 'referrals', 'withdraw', 'settings'];
if (!in_array($activeSection, $validSections)) {
    $activeSection = 'overview';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Painel de Afiliados</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        body { background-color: #111827; }
        .sidebar-transition { transition: transform 0.2s ease-in-out; }
    </style>
</head>
<body class="min-h-screen bg-gray-900">
    <header class="bg-gray-800 border-b border-gray-700 px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden text-gray-400 hover:text-white">
                    <i data-lucide="menu" class="h-5 w-5"></i>
                </button>
                
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-green-500 rounded flex items-center justify-center">
                        <span class="text-white font-bold text-sm">777</span>
                    </div>
                    <span class="text-white font-semibold hidden sm:block"><?php echo SITE_NAME; ?> - Afiliados</span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="text-center">
                    <div class="text-sm text-gray-400">Comissão Disponível</div>
                    <div class="text-lg font-bold text-green-400"><?php echo formatarDinheiro($stats['comissao_disponivel']); ?></div>
                </div>
            </div>

            <div class="relative">
                <button onclick="toggleUserMenu()" class="flex items-center gap-2 text-white hover:bg-gray-700 px-3 py-2 rounded-lg transition-colors">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <span class="text-white font-bold text-sm">
                            <?php echo strtoupper(substr($dadosAfiliado['nome_completo'], 0, 2)); ?>
                        </span>
                    </div>
                    <div class="hidden sm:block text-left">
                        <div class="font-medium"><?php echo htmlspecialchars($dadosAfiliado['nome_completo']); ?></div>
                        <div class="text-xs text-gray-400">Afiliado</div>
                    </div>
                    <i data-lucide="chevron-down" class="h-4 w-4"></i>
                </button>

                <div id="userMenu" class="hidden absolute right-0 mt-2 w-64 bg-gray-800 border border-gray-700 rounded-lg shadow-lg z-50">
                    <div class="px-4 py-3 border-b border-gray-700">
                        <div class="font-medium text-white"><?php echo htmlspecialchars($dadosAfiliado['nome_completo']); ?></div>
                        <div class="text-sm text-gray-400"><?php echo htmlspecialchars($dadosAfiliado['email']); ?></div>
                        <div class="text-xs text-green-400 mt-1">Código: <?php echo $dadosAfiliado['codigo_afiliado']; ?></div>
                    </div>

                    <a href="?section=settings" class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-gray-700 transition-colors">
                        <i data-lucide="settings" class="h-4 w-4"></i>
                        <div>Configurações</div>
                    </a>

                    <div class="border-t border-gray-700">
                        <a href="?action=logout" class="flex items-center gap-3 px-4 py-3 text-red-400 hover:bg-gray-700 hover:text-red-300 transition-colors">
                            <i data-lucide="log-out" class="h-4 w-4"></i>
                            <div>Sair</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="flex">
        <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden" onclick="toggleSidebar()"></div>

        <aside id="sidebar" class="fixed left-0 top-16 h-[calc(100vh-4rem)] w-80 bg-gray-800 border-r border-gray-700 transform -translate-x-full transition-transform duration-200 ease-in-out z-50 lg:relative lg:top-0 lg:translate-x-0">
            <div class="p-4">
                <div class="flex justify-end lg:hidden mb-4">
                    <button onclick="toggleSidebar()" class="text-gray-400 hover:text-white">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <nav class="space-y-2">
                    <a href="?section=overview" class="flex items-center gap-3 w-full p-3 rounded-lg text-left transition-colors <?php echo $activeSection === 'overview' ? 'bg-green-500/10 text-green-400 border-l-2 border-green-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i data-lucide="home" class="h-5 w-5 flex-shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium">Visão Geral</div>
                            <div class="text-xs text-gray-400 mt-1">Estatísticas e resumo</div>
                        </div>
                    </a>

                    <a href="?section=withdraw" class="flex items-center gap-3 w-full p-3 rounded-lg text-left transition-colors <?php echo $activeSection === 'withdraw' ? 'bg-green-500/10 text-green-400 border-l-2 border-green-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i data-lucide="credit-card" class="h-5 w-5 flex-shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium">Saques</div>
                            <div class="text-xs text-gray-400 mt-1">Solicitar saques</div>
                        </div>
                    </a>

                    <a href="?section=settings" class="flex items-center gap-3 w-full p-3 rounded-lg text-left transition-colors <?php echo $activeSection === 'settings' ? 'bg-green-500/10 text-green-400 border-l-2 border-green-500' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i data-lucide="settings" class="h-5 w-5 flex-shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium">Configurações</div>
                            <div class="text-xs text-gray-400 mt-1">Perfil e segurança</div>
                        </div>
                    </a>
                </nav>
            </div>
        </aside>

        <main class="flex-1 p-6">
            <?php
            switch ($activeSection) {
                case 'withdraw':
                    include 'sections/withdraw.php';
                    break;
                case 'settings':
                    include 'sections/settings.php';
                    break;
                case 'overview':
                default:
                    include 'sections/overview.php';
                    break;
            }
            ?>
        </main>
    </div>

    <script>
        lucide.createIcons();

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        function toggleUserMenu() {
            const menu = document.getElementById('userMenu');
            menu.classList.toggle('hidden');
        }

        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('userMenu');
            const userButton = event.target.closest('button[onclick="toggleUserMenu()"]');
            
            if (!userButton && !userMenu.contains(event.target)) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>