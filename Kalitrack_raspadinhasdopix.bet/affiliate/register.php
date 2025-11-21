<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if (isset($_SESSION['afiliado_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    $dados = [
        'nome' => trim($_POST['nome'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'telefone' => trim($_POST['telefone'] ?? ''),
        'senha' => $_POST['senha'] ?? '',
        'confirmar_senha' => $_POST['confirmar_senha'] ?? ''
    ];
    
    if ($dados['senha'] !== $dados['confirmar_senha']) {
        $error = 'As senhas não coincidem';
    } else {
        $resultado = cadastrarAfiliado($dados);
        
        if ($resultado['success']) {
            $_SESSION['afiliado_id'] = $resultado['afiliado_id'];
            $_SESSION['afiliado_nome'] = $dados['nome'];
            $_SESSION['afiliado_email'] = $dados['email'];
            $_SESSION['codigo_afiliado'] = $resultado['codigo_afiliado'];
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = $resultado['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Afiliados - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        body { background-color: #111827; }
    </style>
</head>
<body class="min-h-screen bg-gray-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-gray-800 border border-gray-700 rounded-lg shadow-lg">
        <div class="p-6 text-center border-b border-gray-700">
            <div class="mx-auto mb-4 w-16 h-16 bg-green-500 rounded-full flex items-center justify-center">
                <i data-lucide="users" class="h-8 w-8 text-white"></i>
            </div>
            <h1 class="text-2xl font-bold text-white mb-2">Torne-se Afiliado</h1>
            <p class="text-gray-400">Cadastre-se e comece a ganhar comissões</p>
        </div>

        <div class="p-6">
            <?php if ($error): ?>
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg">
                    <p class="text-red-400 text-sm"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-4 p-3 bg-green-500/10 border border-green-500/20 rounded-lg">
                    <p class="text-green-400 text-sm"><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="register">
                
                <div class="space-y-2">
                    <label for="nome" class="block text-gray-300 text-sm font-medium">Nome Completo</label>
                    <div class="relative">
                        <i data-lucide="user" class="absolute left-3 top-3 h-4 w-4 text-gray-400"></i>
                        <input 
                            type="text" 
                            id="nome" 
                            name="nome" 
                            placeholder="Seu nome completo"
                            value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>"
                            class="w-full pl-10 pr-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            required
                        >
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="email" class="block text-gray-300 text-sm font-medium">Email</label>
                    <div class="relative">
                        <i data-lucide="mail" class="absolute left-3 top-3 h-4 w-4 text-gray-400"></i>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="seu@email.com"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            class="w-full pl-10 pr-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            required
                        >
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="telefone" class="block text-gray-300 text-sm font-medium">Telefone</label>
                    <div class="relative">
                        <i data-lucide="phone" class="absolute left-3 top-3 h-4 w-4 text-gray-400"></i>
                        <input 
                            type="tel" 
                            id="telefone" 
                            name="telefone" 
                            placeholder="(11) 99999-9999"
                            value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>"
                            class="w-full pl-10 pr-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        >
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="senha" class="block text-gray-300 text-sm font-medium">Senha</label>
                    <div class="relative">
                        <i data-lucide="lock" class="absolute left-3 top-3 h-4 w-4 text-gray-400"></i>
                        <input 
                            type="password" 
                            id="senha" 
                            name="senha" 
                            placeholder="Sua senha"
                            class="w-full pl-10 pr-10 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            required
                        >
                        <button type="button" onclick="togglePassword('senha')" class="absolute right-3 top-3 text-gray-400 hover:text-gray-300">
                            <i data-lucide="eye" id="senha-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="confirmar_senha" class="block text-gray-300 text-sm font-medium">Confirmar Senha</label>
                    <div class="relative">
                        <i data-lucide="lock" class="absolute left-3 top-3 h-4 w-4 text-gray-400"></i>
                        <input 
                            type="password" 
                            id="confirmar_senha" 
                            name="confirmar_senha" 
                            placeholder="Confirme sua senha"
                            class="w-full pl-10 pr-10 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            required
                        >
                        <button type="button" onclick="togglePassword('confirmar_senha')" class="absolute right-3 top-3 text-gray-400 hover:text-gray-300">
                            <i data-lucide="eye" id="confirmar_senha-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Criar Conta de Afiliado
                </button>

                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-600"></div>
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="bg-gray-800 px-2 text-gray-400">Ou</span>
                    </div>
                </div>

                <div class="text-center">
                    <span class="text-gray-400">Já tem uma conta? </span>
                    <a href="login.php" class="text-green-400 hover:text-green-300 font-medium">Entrar</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const eye = document.getElementById(fieldId + '-eye');
            
            if (field.type === 'password') {
                field.type = 'text';
                eye.setAttribute('data-lucide', 'eye-off');
            } else {
                field.type = 'password';
                eye.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                if (value.length <= 10) {
                    value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                } else {
                    value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                }
                e.target.value = value;
            }
        });
    </script>
</body>
</html>