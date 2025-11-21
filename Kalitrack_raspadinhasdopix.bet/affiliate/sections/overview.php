<?php
?>

<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2">Visão Geral</h1>
        <p class="text-gray-400">Acompanhe o desempenho REAL das suas indicações e comissões</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-gray-300 text-sm font-medium">Total de Indicações</h3>
                <i data-lucide="users" class="h-5 w-5 text-blue-400"></i>
            </div>
            <div class="text-3xl font-bold text-white"><?php echo number_format($stats['total_indicados']); ?></div>
            <p class="text-xs text-gray-400 mt-2">Usuários cadastrados com seu link</p>
        </div>

        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-gray-300 text-sm font-medium">Depósitos dos Indicados</h3>
                <i data-lucide="trending-up" class="h-5 w-5 text-green-400"></i>
            </div>
            <div class="text-3xl font-bold text-white"><?php echo formatarDinheiro($stats['total_depositos']); ?></div>
            <p class="text-xs text-gray-400 mt-2">Total depositado pelos indicados</p>
        </div>

        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-gray-300 text-sm font-medium">Comissão Disponível</h3>
                <i data-lucide="dollar-sign" class="h-5 w-5 text-yellow-400"></i>
            </div>
            <div class="text-3xl font-bold text-green-400"><?php echo formatarDinheiro($stats['comissao_disponivel']); ?></div>
            <p class="text-xs text-gray-400 mt-2">Pronto para saque</p>
            <?php if ($stats['comissao_disponivel'] > 0): ?>
                <div class="mt-2">
                    <a href="?section=withdraw" class="text-xs text-green-300 hover:text-green-200">→ Solicitar saque</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-gray-300 text-sm font-medium">Comissão Total</h3>
                <i data-lucide="trophy" class="h-5 w-5 text-purple-400"></i>
            </div>
            <div class="text-3xl font-bold text-white"><?php echo formatarDinheiro($stats['comissao_total']); ?></div>
            <p class="text-xs text-gray-400 mt-2">Total de comissões geradas</p>
        </div>
    </div>

    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                <i data-lucide="link" class="h-6 w-6 text-white"></i>
            </div>
            <div>
                <h3 class="text-white text-lg font-semibold">Seu Link de Indicação</h3>
                <p class="text-gray-400 text-sm">Compartilhe este link para ganhar comissões</p>
            </div>
        </div>

        <div class="space-y-4">
            <div class="flex gap-2">
                <input 
                    type="text" 
                    id="referralLink" 
                    value="<?php echo $urlIndicacao; ?>" 
                    readonly 
                    class="flex-1 px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                >
                <button 
                    onclick="copyReferralLink()" 
                    class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors flex items-center gap-2"
                >
                    <i data-lucide="copy" class="h-4 w-4"></i>
                    <span id="copyText">Copiar</span>
                </button>
                <button 
                    onclick="shareReferralLink()" 
                    class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors flex items-center gap-2"
                >
                    <i data-lucide="share-2" class="h-4 w-4"></i>
                    Compartilhar
                </button>
            </div>

            <div class="bg-gray-700 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-white font-medium">Seu Código de Afiliado</div>
                        <div class="text-gray-400 text-sm">Use este código para identificação</div>
                    </div>
                    <div class="text-green-400 font-mono text-lg"><?php echo $dadosAfiliado['codigo_afiliado']; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 hidden">
    <?php
    $ultimosIndicados = getIndicatedUsers($_SESSION['afiliado_id'], 5);
    if (!empty($ultimosIndicados)):
    ?>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-white text-lg font-semibold">Últimos Indicados</h3>
            <a href="?section=referrals" class="text-green-400 hover:text-green-300 text-sm">Ver todos</a>
        </div>
        
        <div class="space-y-3">
            <?php foreach ($ultimosIndicados as $indicado): ?>
            <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                        <span class="text-white font-bold text-sm">
                            <?php echo strtoupper(substr($indicado['username'], 0, 2)); ?>
                        </span>
                    </div>
                    <div>
                        <div class="font-medium text-white"><?php echo htmlspecialchars($indicado['username']); ?></div>
                        <div class="text-sm text-gray-400"><?php echo formatarData($indicado['created_at']); ?></div>
                        <?php if ($indicado['total_jogadas'] > 0): ?>
                            <div class="text-xs text-blue-400"><?php echo $indicado['total_jogadas']; ?> jogadas realizadas</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-green-400"><?php echo formatarDinheiro($indicado['total_depositos']); ?></div>
                    <div class="text-xs text-gray-400">Depositado</div>
                    <?php if ($indicado['total_saques'] > 0): ?>
                        <div class="text-xs text-red-400"><?php echo formatarDinheiro($indicado['total_saques']); ?> sacado</div>
                    <?php endif; ?>
                    <?php if ($indicado['saldo'] > 0): ?>
                        <div class="text-xs text-yellow-400">Saldo: <?php echo formatarDinheiro($indicado['saldo']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <div class="text-center py-12">
            <i data-lucide="users" class="h-16 w-16 text-gray-500 mx-auto mb-4"></i>
            <h3 class="text-gray-300 text-lg font-medium mb-2">Nenhum indicado ainda</h3>
            <p class="text-gray-400 mb-4">Comece a compartilhar seu link para ver seus primeiros indicados aqui</p>
            <button onclick="copyReferralLink()" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition-colors">
                Copiar Link de Indicação
            </button>
        </div>
    </div>
    <?php endif; ?>
    </div>

    <?php if ($stats['total_indicados'] > 0): ?>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <h3 class="text-white text-lg font-semibold mb-4">📊 Seu Desempenho</h3>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gray-700 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-blue-400"><?php echo number_format($stats['total_indicados']); ?></div>
                <div class="text-sm text-gray-400">Indicados</div>
            </div>
            
            <?php 
            $mediaDeposito = $stats['total_indicados'] > 0 ? $stats['total_depositos'] / $stats['total_indicados'] : 0;
            ?>
            <div class="bg-gray-700 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-green-400"><?php echo formatarDinheiro($mediaDeposito); ?></div>
                <div class="text-sm text-gray-400">Média por Indicado</div>
            </div>
            
            <?php 
            $conversao = $stats['total_indicados'] > 0 ? ($stats['total_depositos'] > 0 ? count(array_filter($ultimosIndicados, function($u) { return $u['total_depositos'] > 0; })) / $stats['total_indicados'] * 100 : 0) : 0;
            ?>
            <div class="bg-gray-700 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-yellow-400"><?php echo number_format($conversao, 1); ?>%</div>
                <div class="text-sm text-gray-400">Taxa de Conversão</div>
            </div>
            
            <div class="bg-gray-700 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-purple-400"><?php echo formatarDinheiro($stats['comissao_total']); ?></div>
                <div class="text-sm text-gray-400">Total Comissões</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 hidden">
    <?php
    $ultimasTransacoes = getIndicatedUsersTransactions($_SESSION['afiliado_id'], 5);
    if (!empty($ultimasTransacoes)):
    ?>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-white text-lg font-semibold">Últimas Transações dos Indicados</h3>
            <span class="text-gray-400 text-sm"><?php echo count($ultimasTransacoes); ?> transações</span>
        </div>
        
        <div class="space-y-3">
            <?php foreach ($ultimasTransacoes as $transacao): ?>
            <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                <div>
                    <div class="font-medium text-white"><?php echo htmlspecialchars($transacao['username']); ?></div>
                    <div class="text-sm text-gray-400">ID: <?php echo $transacao['transaction_id']; ?></div>
                    <div class="text-xs text-gray-500"><?php echo formatarData($transacao['created_at']); ?></div>
                </div>
                <div class="text-right">
                    <div class="font-medium text-green-400"><?php echo formatarDinheiro($transacao['valor']); ?></div>
                    <div class="text-xs <?php echo $transacao['status'] === 'pago' ? 'text-green-400' : 'text-yellow-400'; ?>">
                        <?php echo ucfirst($transacao['status']); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    </div>

    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <h3 class="text-white text-lg font-semibold mb-4">Como Funciona o Sistema de Afiliados</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="mx-auto mb-4 w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center">
                    <i data-lucide="users" class="h-8 w-8 text-white"></i>
                </div>
                <h4 class="text-white font-semibold mb-2">1. Convide Pessoas</h4>
                <p class="text-gray-400 text-sm">Compartilhe seu link de indicação com amigos e conhecidos</p>
            </div>

            <div class="text-center">
                <div class="mx-auto mb-4 w-16 h-16 bg-green-500 rounded-full flex items-center justify-center">
                    <i data-lucide="credit-card" class="h-8 w-8 text-white"></i>
                </div>
                <h4 class="text-white font-semibold mb-2">2. Eles Depositam</h4>
                <p class="text-gray-400 text-sm">Quando seus indicados fazem depósitos, você ganha comissões</p>
            </div>

            <div class="text-center">
                <div class="mx-auto mb-4 w-16 h-16 bg-yellow-500 rounded-full flex items-center justify-center">
                    <i data-lucide="dollar-sign" class="h-8 w-8 text-white"></i>
                </div>
                <h4 class="text-white font-semibold mb-2">3. Você Recebe</h4>
                <p class="text-gray-400 text-sm">Ganhe <?php echo $stats['percentual_comissao'] ?? 50; ?>% do excedente entre depósitos e saques</p>
            </div>
        </div>
    </div>

</div>

<script>
    function copyReferralLink() {
        const linkInput = document.getElementById('referralLink');
        const copyText = document.getElementById('copyText');
        
        linkInput.select();
        linkInput.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(linkInput.value).then(function() {
            copyText.textContent = 'Copiado!';
            setTimeout(() => {
                copyText.textContent = 'Copiar';
            }, 2000);
        });
    }

    function shareReferralLink() {
        const link = document.getElementById('referralLink').value;
        
        if (navigator.share) {
            navigator.share({
                title: '<?php echo SITE_NAME; ?> - Venha Jogar!',
                text: 'Venha se divertir e ganhar prêmios incríveis comigo!',
                url: link
            });
        } else {
            copyReferralLink();
        }
    }

    setInterval(function() {
        if (document.visibilityState === 'visible') {
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    console.log('Estatísticas atualizadas');
                })
                .catch(error => {
                    console.log('Erro ao atualizar:', error);
                });
        }
    }, 30000);
</script>