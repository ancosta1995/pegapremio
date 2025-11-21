<?php

$indicados = getIndicatedUsers($afiliadoId, 100);
?>

<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2">Suas Indicações</h1>
        <p class="text-gray-400">Gerencie e acompanhe todos os usuários que você indicou</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-gray-300 text-sm font-medium">Total de Indicados</h3>
                <i data-lucide="users" class="h-4 w-4 text-blue-400"></i>
            </div>
            <div class="text-2xl font-bold text-white"><?php echo count($indicados); ?></div>
            <p class="text-xs text-gray-400 mt-1">Usuários cadastrados</p>
        </div>

        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-gray-300 text-sm font-medium">Usuários Ativos</h3>
                <i data-lucide="activity" class="h-4 w-4 text-green-400"></i>
            </div>
            <div class="text-2xl font-bold text-green-400">
                <?php echo count(array_filter($indicados, function($indicado) { return $indicado['total_depositos'] > 0; })); ?>
            </div>
            <p class="text-xs text-gray-400 mt-1">Com depósitos realizados</p>
        </div>

        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-gray-300 text-sm font-medium">Total Depositado</h3>
                <i data-lucide="trending-up" class="h-4 w-4 text-yellow-400"></i>
            </div>
            <div class="text-2xl font-bold text-white">
                <?php echo formatarDinheiro(array_sum(array_column($indicados, 'total_depositos'))); ?>
            </div>
            <p class="text-xs text-gray-400 mt-1">Por todos os indicados</p>
        </div>
    </div>

    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <div class="flex items-center gap-3 mb-4">
            <i data-lucide="share-2" class="h-6 w-6 text-green-400"></i>
            <h3 class="text-white text-lg font-semibold">Compartilhe e Ganhe</h3>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="https://wa.me/?text=Venha se divertir e ganhar prêmios incríveis! Cadastre-se aqui: <?php echo urlencode($urlIndicacao); ?>" 
               target="_blank" 
               class="flex items-center gap-3 p-4 bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
                <i data-lucide="message-circle" class="h-5 w-5 text-white"></i>
                <div class="text-white">
                    <div class="font-medium">WhatsApp</div>
                    <div class="text-sm opacity-90">Compartilhar via WhatsApp</div>
                </div>
            </a>

            <a href="https://t.me/share/url?url=<?php echo urlencode($urlIndicacao); ?>&text=Venha se divertir e ganhar prêmios incríveis!" 
               target="_blank" 
               class="flex items-center gap-3 p-4 bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                <i data-lucide="send" class="h-5 w-5 text-white"></i>
                <div class="text-white">
                    <div class="font-medium">Telegram</div>
                    <div class="text-sm opacity-90">Compartilhar via Telegram</div>
                </div>
            </a>

            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($urlIndicacao); ?>" 
               target="_blank" 
               class="flex items-center gap-3 p-4 bg-blue-800 hover:bg-blue-900 rounded-lg transition-colors">
                <i data-lucide="facebook" class="h-5 w-5 text-white"></i>
                <div class="text-white">
                    <div class="font-medium">Facebook</div>
                    <div class="text-sm opacity-90">Compartilhar no Facebook</div>
                </div>
            </a>
        </div>
    </div>

    <?php if (!empty($indicados)): ?>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-white text-lg font-semibold">Lista de Indicados</h3>
            <div class="flex items-center gap-2">
                <input 
                    type="text" 
                    id="searchIndicados" 
                    placeholder="Buscar por nome ou email..." 
                    class="px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                >
                <i data-lucide="search" class="h-4 w-4 text-gray-400"></i>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-gray-700">
                        <th class="pb-3 text-gray-300 font-medium">Usuário</th>
                        <th class="pb-3 text-gray-300 font-medium">Data de Cadastro</th>
                        <th class="pb-3 text-gray-300 font-medium">Total Depositado</th>
                        <th class="pb-3 text-gray-300 font-medium">Total Sacado</th>
                        <th class="pb-3 text-gray-300 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody id="indicadosTable">
                    <?php foreach ($indicados as $indicado): ?>
                    <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition-colors indicado-row">
                        <td class="py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center">
                                    <span class="text-white font-bold text-sm">
                                        <?php echo strtoupper(substr($indicado['username'], 0, 2)); ?>
                                    </span>
                                </div>
                                <div>
                                    <div class="font-medium text-white indicado-username"><?php echo htmlspecialchars($indicado['username']); ?></div>
                                    <div class="text-sm text-gray-400 indicado-email"><?php echo htmlspecialchars($indicado['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 text-gray-300">
                            <?php echo formatarData($indicado['created_at']); ?>
                        </td>
                        <td class="py-4">
                            <span class="text-green-400 font-medium">
                                <?php echo formatarDinheiro($indicado['total_depositos']); ?>
                            </span>
                        </td>
                        <td class="py-4">
                            <span class="text-red-400 font-medium">
                                <?php echo formatarDinheiro($indicado['total_saques']); ?>
                            </span>
                        </td>
                        <td class="py-4">
                            <?php if ($indicado['total_depositos'] > 0): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500/10 text-green-400 border border-green-500/20">
                                    <i data-lucide="check-circle" class="h-3 w-3 mr-1"></i>
                                    Ativo
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-500/10 text-gray-400 border border-gray-500/20">
                                    <i data-lucide="clock" class="h-3 w-3 mr-1"></i>
                                    Pendente
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (count($indicados) === 0): ?>
        <div class="text-center py-12">
            <i data-lucide="users" class="h-16 w-16 text-gray-500 mx-auto mb-4"></i>
            <h3 class="text-gray-300 text-lg font-medium mb-2">Nenhum indicado ainda</h3>
            <p class="text-gray-400 mb-4">Comece a compartilhar seu link para ver seus primeiros indicados aqui</p>
            <button onclick="copyReferralLink()" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition-colors">
                Copiar Link de Indicação
            </button>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
    document.getElementById('searchIndicados').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('.indicado-row');
        
        rows.forEach(row => {
            const username = row.querySelector('.indicado-username').textContent.toLowerCase();
            const email = row.querySelector('.indicado-email').textContent.toLowerCase();
            
            if (username.includes(searchTerm) || email.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    function copyReferralLink() {
        const link = '<?php echo $urlIndicacao; ?>';
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
</script>