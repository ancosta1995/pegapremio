<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'update_profile') {
        $nome = trim($_POST['nome'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        
        if (empty($nome)) {
            $profileError = 'Nome é obrigatório';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE afiliados SET nome_completo = ?, telefone = ? WHERE id = ?");
                $stmt->execute([$nome, $telefone, $afiliadoId]);
                
                $_SESSION['afiliado_nome'] = $nome;
                $profileSuccess = 'Perfil atualizado com sucesso!';
                
                $dadosAfiliado['nome_completo'] = $nome;
                $dadosAfiliado['telefone'] = $telefone;
                
            } catch(PDOException $e) {
                $profileError = 'Erro ao atualizar perfil';
                error_log("Erro ao atualizar perfil: " . $e->getMessage());
            }
        }
    } elseif (($_POST['action'] ?? '') === 'change_password') {
        $senhaAtual = $_POST['senha_atual'] ?? '';
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';
        
        if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
            $passwordError = 'Todos os campos são obrigatórios';
        } elseif ($novaSenha !== $confirmarSenha) {
            $passwordError = 'Nova senha e confirmação não coincidem';
        } elseif (strlen($novaSenha) < 6) {
            $passwordError = 'Nova senha deve ter pelo menos 6 caracteres';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT senha_hash FROM afiliados WHERE id = ?");
                $stmt->execute([$afiliadoId]);
                $senhaHashAtual = $stmt->fetchColumn();
                
                if (!password_verify($senhaAtual, $senhaHashAtual)) {
                    $passwordError = 'Senha atual incorreta';
                } else {
                    $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE afiliados SET senha_hash = ? WHERE id = ?");
                    $stmt->execute([$novaSenhaHash, $afiliadoId]);
                    
                    $passwordSuccess = 'Senha alterada com sucesso!';
                }
            } catch(PDOException $e) {
                $passwordError = 'Erro ao alterar senha';
                error_log("Erro ao alterar senha: " . $e->getMessage());
            }
        }
    }
}
?>

<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2">Configurações</h1>
        <p class="text-gray-400">Gerencie seu perfil e configurações da conta</p>
    </div>

    <div class="bg-gray-800 border border-gray-700 rounded-lg">
        <div class="flex border-b border-gray-700">
            <button onclick="showTab('profile')" class="tab-button flex-1 px-6 py-4 text-center text-white bg-gray-600 transition-colors active" data-tab="profile">
                <i data-lucide="user" class="h-4 w-4 inline mr-2"></i>
                Perfil
            </button>
            <button onclick="showTab('security')" class="tab-button flex-1 px-6 py-4 text-center text-gray-300 hover:bg-gray-700 transition-colors" data-tab="security">
                <i data-lucide="shield" class="h-4 w-4 inline mr-2"></i>
                Segurança
            </button>
            <button onclick="showTab('account')" class="tab-button flex-1 px-6 py-4 text-center text-gray-300 hover:bg-gray-700 transition-colors" data-tab="account">
                <i data-lucide="settings" class="h-4 w-4 inline mr-2"></i>
                Conta
            </button>
        </div>

        <div id="tab-profile" class="tab-content p-6">
            <div class="space-y-6">
                <div>
                    <h3 class="text-lg font-semibold text-white mb-2">Informações Pessoais</h3>
                    <p class="text-gray-400 text-sm mb-4">Atualize suas informações pessoais</p>
                </div>

                <?php if (isset($profileSuccess)): ?>
                    <div class="p-4 bg-green-500/10 border border-green-500/20 rounded-lg">
                        <p class="text-green-400 text-sm"><?php echo htmlspecialchars($profileSuccess); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (isset($profileError)): ?>
                    <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-lg">
                        <p class="text-red-400 text-sm"><?php echo htmlspecialchars($profileError); ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label for="nome" class="block text-gray-300 text-sm font-medium">Nome Completo</label>
                            <div class="relative">
                                <i data-lucide="user" class="absolute left-3 top-3 h-4 w-4 text-gray-400"></i>
                                <input 
                                    type="text" 
                                    id="nome" 
                                    name="nome" 
                                    value="<?php echo htmlspecialchars($dadosAfiliado['nome_completo']); ?>"
                                    class="w-full pl-10 pr-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
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
                                    value="<?php echo htmlspecialchars($dadosAfiliado['telefone'] ?? ''); ?>"
                                    placeholder="(11) 99999-9999"
                                    class="w-full pl-10 pr-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="email" class="block text-gray-300 text-sm font-medium">Email</label>
                        <div class="relative">
                            <i data-lucide="mail" class="absolute left-3 top-3 h-4 w-4 text-gray-400"></i>
                            <input 
                                type="email" 
                                id="email" 
                                value="<?php echo htmlspecialchars($dadosAfiliado['email']); ?>"
                                class="w-full pl-10 pr-4 py-2 bg-gray-600 border border-gray-600 rounded-lg text-gray-300 cursor-not-allowed"
                                readonly
                            >
                        </div>
                        <p class="text-xs text-gray-400">O email não pode ser alterado</p>
                    </div>

                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition-colors">
                        Salvar Alterações
                    </button>
                </form>
            </div>
        </div>

        <div id="tab-security" class="tab-content p-6 hidden">
            <div class="space-y-6">
                <div>
                    <h3 class="text-lg font-semibold text-white mb-2">Alterar Senha</h3>
                    <p class="text-gray-400 text-sm mb-4">Mantenha sua conta segura alterando sua senha regularmente</p>
                </div>

                <?php if (isset($passwordSuccess)): ?>
                    <div class="p-4 bg-green-500/10 border border-green-500/20 rounded-lg">
                        <p class="text-green-400 text-sm"><?php echo htmlspecialchars($passwordSuccess); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (isset($passwordError)): ?>
                    <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-lg">
                        <p class="text-red-400 text-sm"><?php echo htmlspecialchars($passwordError); ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="space-y-2">
                        <label for="senha_atual" class="block text-gray-300 text-sm font-medium">Senha Atual</label>
                        <div class="relative">
                            <i data-lucide="shield" class="absolute left-3 top-3 h-4 w-4 text-gray-400"></i>
                            <input 
                                type="password" 
                                id="senha_atual" 
                                name="senha_atual"
                                class="w-full pl-10 pr-10 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                required
                            >
                            <button type="button" onclick="togglePassword('senha_atual')" class="absolute right-3 top-3 text-gray-400 hover:text-gray-300">
                                <i data-lucide="eye" id="senha_atual-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="nova_senha" class="block text-gray-300 text-sm font-medium">Nova Senha</label>
                        <div class="relative">
                            <i data-lucide="lock" class="absolute left-3 top-3 h-4 w-4 text-gray-400"></i>
                            <input 
                                type="password" 
                                id="nova_senha" 
                                name="nova_senha"
                                class="w-full pl-10 pr-10 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                required
                            >
                            <button type="button" onclick="togglePassword('nova_senha')" class="absolute right-3 top-3 text-gray-400 hover:text-gray-300">
                                <i data-lucide="eye" id="nova_senha-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="confirmar_senha" class="block text-gray-300 text-sm font-medium">Confirmar Nova Senha</label>
                        <div class="relative">
                            <i data-lucide="lock" class="absolute left-3 top-3 h-4 w-4 text-gray-400"></i>
                            <input 
                                type="password" 
                                id="confirmar_senha" 
                                name="confirmar_senha"
                                class="w-full pl-10 pr-10 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                required
                            >
                            <button type="button" onclick="togglePassword('confirmar_senha')" class="absolute right-3 top-3 text-gray-400 hover:text-gray-300">
                                <i data-lucide="eye" id="confirmar_senha-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition-colors">
                        Alterar Senha
                    </button>
                </form>
            </div>
        </div>

        <div id="tab-account" class="tab-content p-6 hidden">
            <div class="space-y-6">
                <div>
                    <h3 class="text-lg font-semibold text-white mb-2">Informações da Conta</h3>
                    <p class="text-gray-400 text-sm mb-4">Detalhes sobre sua conta de afiliado</p>
                </div>

                <div class="bg-gray-700 rounded-lg p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-white font-medium">Código de Afiliado</div>
                            <div class="text-gray-400 text-sm">Seu identificador único</div>
                        </div>
                        <div class="text-green-400 font-mono text-lg"><?php echo $dadosAfiliado['codigo_afiliado']; ?></div>
                    </div>

                    <div class="border-t border-gray-600 pt-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-white font-medium">Data de Cadastro</div>
                                <div class="text-gray-400 text-sm">Quando você se tornou afiliado</div>
                            </div>
                            <div class="text-gray-300"><?php echo formatarData($dadosAfiliado['criado_em']); ?></div>
                        </div>
                    </div>

                    <div class="border-t border-gray-600 pt-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-white font-medium">Último Login</div>
                                <div class="text-gray-400 text-sm">Sua última atividade</div>
                            </div>
                            <div class="text-gray-300"><?php echo formatarData($dadosAfiliado['ultimo_login']); ?></div>
                        </div>
                    </div>

                    <div class="border-t border-gray-600 pt-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-white font-medium">Status da Conta</div>
                                <div class="text-gray-400 text-sm">Estado atual da sua conta</div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500/10 text-green-400 border border-green-500/20">
                                <i data-lucide="check-circle" class="h-3 w-3 mr-1"></i>
                                Ativa
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-700 rounded-lg p-6">
                    <h4 class="text-white font-medium mb-3">Seu Link de Indicação</h4>
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            value="<?php echo $urlIndicacao; ?>" 
                            readonly 
                            class="flex-1 px-4 py-2 bg-gray-600 border border-gray-600 rounded-lg text-white text-sm focus:outline-none"
                        >
                        <button 
                            onclick="copyLink('<?php echo $urlIndicacao; ?>')" 
                            class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors"
                        >
                            <i data-lucide="copy" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-700 rounded-lg p-4">
                        <div class="text-2xl font-bold text-white"><?php echo $stats['total_indicados']; ?></div>
                        <div class="text-gray-400 text-sm">Total de Indicados</div>
                    </div>
                    <div class="bg-gray-700 rounded-lg p-4">
                        <div class="text-2xl font-bold text-green-400"><?php echo formatarDinheiro($stats['comissao_total']); ?></div>
                        <div class="text-gray-400 text-sm">Comissão Total</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function showTab(tabName) {
        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(tab => tab.classList.add('hidden'));
        
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            button.classList.remove('active', 'bg-gray-600', 'text-white');
            button.classList.add('text-gray-300');
        });
        
        document.getElementById('tab-' + tabName).classList.remove('hidden');
        
        const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
        activeButton.classList.add('active', 'bg-gray-600', 'text-white');
        activeButton.classList.remove('text-gray-300');
    }

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

    function copyLink(link) {
        navigator.clipboard.writeText(link).then(function() {
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
            notification.textContent = 'Link copiado com sucesso!';
            document.body.appendChild(notification);
            
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 3000);
        });
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

    document.querySelector('form[action*="change_password"]').addEventListener('submit', function(e) {
        const novaSenha = document.getElementById('nova_senha').value;
        const confirmarSenha = document.getElementById('confirmar_senha').value;
        
        if (novaSenha !== confirmarSenha) {
            e.preventDefault();
            alert('Nova senha e confirmação não coincidem');
            return;
        }
        
        if (novaSenha.length < 6) {
            e.preventDefault();
            alert('Nova senha deve ter pelo menos 6 caracteres');
            return;
        }
    });
</script>