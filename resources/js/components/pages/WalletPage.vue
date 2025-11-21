<template>
    <div class="page-container">
        <div class="page-header">
            <h2 class="page-title">Carteira</h2>
        </div>
        <div class="page-content">
            <div class="wallet-balances">
                <div class="balance-card">
                    <div class="balance-label">Saldo Principal</div>
                    <div class="balance-value">R$ {{ formatBalance(balance) }}</div>
                </div>
                <div class="balance-card">
                    <div class="balance-label">Saldo Bônus</div>
                    <div class="balance-value bonus">R$ {{ formatBalance(balanceBonus) }}</div>
                </div>
                <div class="balance-card">
                    <div class="balance-label">Saldo de Afiliado</div>
                    <div class="balance-value affiliate">R$ {{ formatBalance(balanceRef) }}</div>
                </div>
            </div>
            <div class="wallet-actions">
                <button class="action-button deposit-button" @click="$emit('deposit')">
                    <span class="button-icon">💰</span>
                    <span class="button-text">Depositar</span>
                </button>
                <button class="action-button withdraw-button" @click="$emit('withdraw')">
                    <span class="button-icon">💸</span>
                    <span class="button-text">Sacar</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'WalletPage',
    props: {
        balance: {
            type: Number,
            default: 0,
        },
        balanceBonus: {
            type: Number,
            default: 0,
        },
        balanceRef: {
            type: Number,
            default: 0,
        },
    },
    emits: ['deposit', 'withdraw'],
    methods: {
        formatBalance(value) {
            return parseFloat(value).toFixed(2).replace('.', ',');
        },
    },
};
</script>

<style scoped>
.page-container {
    width: 100%;
    max-width: 600px;
    margin: 0 auto;
    padding: 1rem;
}

.page-header {
    margin-bottom: 2rem;
}

.page-title {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--cor-texto);
    margin: 0;
}

.page-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.wallet-balances {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.balance-card {
    background-color: var(--cor-fundo-input);
    padding: 1.5rem;
    border-radius: 12px;
    text-align: center;
}

.balance-label {
    font-size: 0.9rem;
    color: var(--cor-texto-secundaria);
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.balance-value {
    font-size: 1.8rem;
    font-weight: bold;
    color: #22c55e;
}

.balance-value.bonus {
    color: #fbbf24;
}

.balance-value.affiliate {
    color: #3b82f6;
}

.wallet-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.action-button {
    width: 100%;
    padding: 1.2rem;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    font-size: 1.1rem;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    transition: transform 0.2s, box-shadow 0.2s;
}

.action-button:active {
    transform: scale(0.98);
}

.deposit-button {
    background-image: linear-gradient(to bottom, var(--cor-principal), var(--cor-principal-dark));
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.withdraw-button {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
}

.button-icon {
    font-size: 1.5rem;
}

.button-text {
    font-size: 1rem;
}
</style>

