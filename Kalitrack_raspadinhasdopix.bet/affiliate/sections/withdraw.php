<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'withdraw') {
    $valor = floatval($_POST['valor'] ?? 0);
    $chavePix = trim($_POST['chave_pix'] ?? '');
    $tipoChave = $_POST['tipo_chave'] ?? '';
    $nomeTitular = trim($_POST['nome_titular'] ?? '');
    
    $resultado = solicitarSaque($afiliadoId, $valor, $chavePix, $tipoChave, $nomeTitular);
    
    if ($resultado['success']) {
        $successMessage = $resultado['message'];
        $stats = getAffiliateStats($afiliadoId);
    } else {
        $errorMessage = $resultado['message'];
    }
}

$saques = getAffiliateSaques($afiliadoId, 20);
?>

<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2">Saques</h1>
        <p class="text-gray-400">Solicite o saque das suas comissões</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-gray-300 text-sm font-medium">Disponível para Saque</h3>
                <i data-lucide="dollar-sign" class="h-5 w-5 text-green-400"></i>
            </div>
            <div class="text-3xl font-bold text-green-400"><?php echo formatarDinheiro($stats['comissao_disponivel']); ?></div>
            <p class="text-xs text-gray-400 mt-2">Valor líquido disponível</p>
        </div>

        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-gray-300 text-sm font-medium">Saques Pendentes</h3>
                <i data-lucide="clock" class="h-5 w-5 text-yellow-400"></i>
            </div>
            <div class="text-3xl font-bold text-yellow-400"><?php echo formatarDinheiro($stats['comissao_pendente'] ?? 0); ?></div>
            <p class="text-xs text-gray-400 mt-2">Em processamento</p>
        </div>

        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-gray-300 text-sm font-medium">Total Sacado</h3>
                <i data-lucide="check-circle" class="h-5 w-5 text-blue-400"></i>
            </div>
            <div class="text-3xl font-bold text-white"><?php echo formatarDinheiro($stats['comissao_paga'] ?? 0); ?></div>
            <p class="text-xs text-gray-400 mt-2">Saques já processados</p>
        </div>
    </div>

    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                <i data-lucide="credit-card" class="h-6 w-6 text-white"></i>
            </div>
            <div>
                <h3 class="text-white text-lg font-semibold">Solicitar Saque</h3>
                <p class="text-gray-400 text-sm">Preencha os dados para solicitar o saque via PIX</p>
            </div>
        </div>

        <?php if (isset($successMessage)): ?>
            <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-lg">
                <div class="flex items-center gap-3">
                    <i data-lucide="check-circle" class="h-5 w-5 text-green-400"></i>
                    <p class="text-green-400"><?php echo htmlspecialchars($successMessage); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg">
                <div class="flex items-center gap-3">
                    <i data-lucide="alert-circle" class="h-5 w-5 text-red-400"></i>
                    <p class="text-red-400"><?php echo htmlspecialchars($errorMessage); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($stats['comissao_disponivel'] >= MIN_WITHDRAWAL_AMOUNT): ?>
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="withdraw">
            
            <div class="space-y-2">
                <label for="valor" class="block text-gray-300 text-sm font-medium">Valor do Saque</label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-gray-400 text-sm">R$</span>
                    <input 
                        type="number" 
                        id="valor" 
                        name="valor" 
                        step="0.01" 
                        min="<?php echo MIN_WITHDRAWAL_AMOUNT; ?>" 
                        max="<?php echo $stats['comissao_disponivel']; ?>"
                        placeholder="0,00"
                        class="w-full pl-10 pr-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        required
                    >
                </div>
                <p class="text-xs text-gray-400">
                    Valor mínimo: <?php echo formatarDinheiro(MIN_WITHDRAWAL_AMOUNT); ?> | 
                    Disponível: <?php echo formatarDinheiro($stats['comissao_disponivel']); ?>
                </p>
            </div>

            <div class="space-y-2">
                <label for="tipo_chave" class="block text-gray-300 text-sm font-medium">Tipo de Chave PIX</label>
                <select 
                    id="tipo_chave" 
                    name="tipo_chave" 
                    class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                    required
                    onchange="updatePixPlaceholder()"
                >
                    <option value="">Selecione o tipo de chave</option>
                    <option value="cpf">CPF</option>
                </select>
            </div>

            <div class="space-y-2">
                <label for="chave_pix" class="block text-gray-300 text-sm font-medium">Chave PIX</label>
                <div class="relative">
                    <i data-lucide="key" class="absolute left-3 top-3 h-5 w-5 text-gray-400"></i>
                    <input 
                        type="text" 
                        id="chave_pix" 
                        name="chave_pix" 
                        placeholder="Digite sua chave PIX"
                        class="w-full pl-12 pr-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        required
                    >
                </div>
                <p id="chave_pix_help" class="text-xs text-gray-400">
                    Selecione o tipo de chave para ver o formato correto
                </p>
            </div>

            <div class="space-y-2">
                <label for="nome_titular" class="block text-gray-300 text-sm font-medium">Nome do Titular</label>
                <div class="relative">
                    <i data-lucide="user" class="absolute left-3 top-3 h-5 w-5 text-gray-400"></i>
                    <input 
                        type="text" 
                        id="nome_titular" 
                        name="nome_titular" 
                        placeholder="Nome completo do titular da conta PIX"
                        value="<?php echo htmlspecialchars($dadosAfiliado['nome_completo']); ?>"
                        class="w-full pl-12 pr-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        required
                    >
                </div>
            </div>

            <div class="flex gap-4">
                <button 
                    type="button" 
                    onclick="fillMaxAmount()" 
                    class="flex-1 border border-gray-600 text-gray-300 hover:bg-gray-700 bg-transparent px-6 py-3 rounded-lg transition-colors flex items-center justify-center gap-2"
                >
                    <i data-lucide="maximize" class="h-4 w-4"></i>
                    Sacar Tudo
                </button>
                <button 
                    type="submit" 
                    class="flex-1 bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg transition-colors flex items-center justify-center gap-2"
                >
                    <i data-lucide="send" class="h-4 w-4"></i>
                    Solicitar Saque
                </button>
            </div>
        </form>
        <?php else: ?>
        <div class="text-center py-8">
            <i data-lucide="alert-triangle" class="h-16 w-16 text-yellow-500 mx-auto mb-4"></i>
            <h3 class="text-white text-lg font-medium mb-2">Saldo Insuficiente</h3>
            <p class="text-gray-400 mb-4">
                Você precisa de pelo menos <?php echo formatarDinheiro(MIN_WITHDRAWAL_AMOUNT); ?> para solicitar um saque.
            </p>
            <p class="text-gray-400">
                Saldo atual: <?php echo formatarDinheiro($stats['comissao_disponivel']); ?>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($saques)): ?>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <h3 class="text-white text-lg font-semibold mb-6">Histórico de Saques</h3>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-gray-700">
                        <th class="pb-3 text-gray-300 font-medium">Data</th>
                        <th class="pb-3 text-gray-300 font-medium">Valor</th>
                        <th class="pb-3 text-gray-300 font-medium">Chave PIX</th>
                        <th class="pb-3 text-gray-300 font-medium">Status</th>
                        <th class="pb-3 text-gray-300 font-medium">Processado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($saques as $saque): ?>
                    <tr class="border-b border-gray-700 hover:bg-gray-700/50 transition-colors">
                        <td class="py-4 text-gray-300">
                            <?php echo formatarData($saque['criado_em']); ?>
                        </td>
                        <td class="py-4">
                            <span class="font-medium text-white">
                                <?php echo formatarDinheiro($saque['valor']); ?>
                            </span>
                        </td>
                        <td class="py-4">
                            <div class="text-gray-300">
                                <div class="font-medium"><?php echo htmlspecialchars($saque['chave_pix']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo strtoupper($saque['tipo_chave']); ?></div>
                            </div>
                        </td>
                        <td class="py-4">
                            <?php
                            $statusClass = '';
                            $statusIcon = '';
                            switch($saque['status']) {
                                case 'pendente':
                                    $statusClass = 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20';
                                    $statusIcon = 'clock';
                                    break;
                                case 'aprovado':
                                    $statusClass = 'bg-blue-500/10 text-blue-400 border-blue-500/20';
                                    $statusIcon = 'check';
                                    break;
                                case 'pago':
                                    $statusClass = 'bg-green-500/10 text-green-400 border-green-500/20';
                                    $statusIcon = 'check-circle';
                                    break;
                                case 'rejeitado':
                                    $statusClass = 'bg-red-500/10 text-red-400 border-red-500/20';
                                    $statusIcon = 'x-circle';
                                    break;
                                default:
                                    $statusClass = 'bg-gray-500/10 text-gray-400 border-gray-500/20';
                                    $statusIcon = 'help-circle';
                            }
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border <?php echo $statusClass; ?>">
                                <i data-lucide="<?php echo $statusIcon; ?>" class="h-3 w-3 mr-1"></i>
                                <?php echo ucfirst($saque['status']); ?>
                            </span>
                        </td>
                        <td class="py-4 text-gray-300">
                            <?php echo $saque['processado_em'] ? formatarData($saque['processado_em']) : '-'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <div class="text-center py-12">
            <i data-lucide="credit-card" class="h-16 w-16 text-gray-500 mx-auto mb-4"></i>
            <h3 class="text-gray-300 text-lg font-medium mb-2">Nenhum saque realizado</h3>
            <p class="text-gray-400">Quando você solicitar saques, eles aparecerão aqui</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    function fillMaxAmount() {
        const maxAmount = <?php echo $stats['comissao_disponivel']; ?>;
        document.getElementById('valor').value = maxAmount.toFixed(2);
    }

    function updatePixPlaceholder() {
        const tipoChave = document.getElementById('tipo_chave').value;
        const chavePix = document.getElementById('chave_pix');
        const helpText = document.getElementById('chave_pix_help');
        
        switch(tipoChave) {
            case 'cpf':
                chavePix.placeholder = '000.000.000-00';
                helpText.textContent = 'Digite seu CPF (apenas números ou com pontuação)';
                break;
            case 'cnpj':
                chavePix.placeholder = '00.000.000/0000-00';
                helpText.textContent = 'Digite seu CNPJ (apenas números ou com pontuação)';
                break;
            case 'email':
                chavePix.placeholder = 'seu@email.com';
                helpText.textContent = 'Digite um email válido';
                break;
            case 'telefone':
                chavePix.placeholder = '(11) 99999-9999';
                helpText.textContent = 'Digite seu telefone com DDD';
                break;
            case 'aleatoria':
                chavePix.placeholder = 'Cole sua chave aleatória aqui';
                helpText.textContent = 'Cole a chave aleatória gerada pelo seu banco';
                break;
            default:
                chavePix.placeholder = 'Digite sua chave PIX';
                helpText.textContent = 'Selecione o tipo de chave para ver o formato correto';
        }
    }

    function mascaraCPF(valor) {
        return valor
            .replace(/\D/g, '')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})/, '$1-$2')
            .replace(/(-\d{2})\d+?$/, '$1');
    }

    function mascaraCNPJ(valor) {
        return valor
            .replace(/\D/g, '')
            .replace(/(\d{2})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1/$2')
            .replace(/(\d{4})(\d{1,2})/, '$1-$2')
            .replace(/(-\d{2})\d+?$/, '$1');
    }

    function mascaraTelefone(valor) {
        return valor
            .replace(/\D/g, '')
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d)/, '$1-$2')
            .replace(/(\d{4})-(\d)(\d{4})/, '$1$2-$3')
            .replace(/(-\d{4})\d+?$/, '$1');
    }

    document.getElementById('chave_pix').addEventListener('input', function(e) {
        const tipoChave = document.getElementById('tipo_chave').value;
        let valor = e.target.value;
        
        switch(tipoChave) {
            case 'cpf':
                e.target.value = mascaraCPF(valor);
                break;
            case 'cnpj':
                e.target.value = mascaraCNPJ(valor);
                break;
            case 'telefone':
                e.target.value = mascaraTelefone(valor);
                break;
        }
    });

    document.querySelector('form').addEventListener('submit', function(e) {
        const valor = parseFloat(document.getElementById('valor').value);
        const maxAmount = <?php echo $stats['comissao_disponivel']; ?>;
        const minAmount = <?php echo MIN_WITHDRAWAL_AMOUNT; ?>;
        
        if (valor < minAmount) {
            e.preventDefault();
            alert(`Valor mínimo para saque é ${minAmount.toFixed(2).replace('.', ',')}`);
            return;
        }
        
        if (valor > maxAmount) {
            e.preventDefault();
            alert(`Valor máximo disponível é ${maxAmount.toFixed(2).replace('.', ',')}`);
            return;
        }
        
        if (!confirm(`Confirma o saque de R$ ${valor.toFixed(2).replace('.', ',')}?`)) {
            e.preventDefault();
        }
    });
</script>

<script>
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

document.querySelector('form').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i> Processando...';
    
    setTimeout(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i data-lucide="send" class="h-4 w-4"></i> Solicitar Saque';
        lucide.createIcons();
    }, 5000);
});
</script>