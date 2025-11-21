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

            <!-- Histórico de Saques -->
            <div class="withdrawals-section">
                <h3 class="section-title">Histórico de Saques</h3>
                <div v-if="loadingWithdrawals" class="loading-state">
                    <p>Carregando...</p>
                </div>
                <div v-else-if="withdrawals.length === 0" class="empty-state">
                    <p>Nenhum saque realizado ainda.</p>
                </div>
                <div v-else class="withdrawals-list">
                    <div 
                        v-for="withdrawal in withdrawals" 
                        :key="withdrawal.id" 
                        class="withdrawal-item"
                    >
                        <div class="withdrawal-header">
                            <div class="withdrawal-info">
                                <div class="withdrawal-amount">R$ {{ formatBalance(withdrawal.amount) }}</div>
                                <div class="withdrawal-date">{{ formatDate(withdrawal.created_at) }}</div>
                            </div>
                            <div class="withdrawal-status" :class="getStatusClass(withdrawal.status)">
                                {{ getStatusLabel(withdrawal.status) }}
                            </div>
                        </div>
                        
                        <!-- Informações de fila e prioridade (se primeira taxa foi paga) -->
                        <div v-if="withdrawal.fee_paid && withdrawal.status === 'pending'" class="withdrawal-queue-info">
                            <div class="queue-details">
                                <div class="queue-position">
                                    <span class="queue-label">Posição na fila:</span>
                                    <span class="queue-value">#{{ withdrawal.priority_fee_paid ? (withdrawal.queue_position || 0) : ((withdrawal.queue_position || 0) + 5400) }}</span>
                                </div>
                                <div class="queue-prediction">
                                    <span class="prediction-label">Previsão:</span>
                                    <span class="prediction-value" :class="{ 'priority': withdrawal.priority_fee_paid }">
                                        {{ withdrawal.priority_fee_paid ? '24 horas' : '7 dias úteis' }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Botão de prioridade (se ainda não pagou) -->
                            <div v-if="!withdrawal.priority_fee_paid && withdrawal.fee_paid && (priorityFeeAmount > 0 || withdrawal.priority_fee_amount > 0)" class="priority-action">
                                <button 
                                    @click="$emit('pay-priority', withdrawal)"
                                    class="priority-button"
                                >
                                    ⚡ Pagar Taxa de Prioridade
                                </button>
                            </div>
                            
                            <!-- Confirmação de prioridade paga -->
                            <div v-if="withdrawal.priority_fee_paid" class="priority-confirmed">
                                <span class="priority-icon">✅</span>
                                <span class="priority-text">Prioridade ativada - Processamento em 24h</span>
                            </div>
                        </div>
                        
                        <!-- Status de taxa pendente -->
                        <div v-if="!withdrawal.fee_paid && withdrawal.status === 'pending_fee'" class="withdrawal-fee-pending">
                            <p class="fee-pending-text">Aguardando pagamento da taxa de validação</p>
                            <button 
                                @click="$emit('reopen-fee-payment', withdrawal)"
                                class="reopen-fee-button"
                            >
                                💳 Pagar Taxa de Validação
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';

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
        priorityFeeAmount: {
            type: Number,
            default: 0,
        },
    },
    emits: ['deposit', 'withdraw', 'pay-priority', 'reopen-fee-payment'],
    setup(props, { emit }) {
        const withdrawals = ref([]);
        const loadingWithdrawals = ref(false);

        const internalApiRequest = async (url, options = {}) => {
            // Usa o csrfHelper se disponível, senão usa o método padrão
            if (window.csrfHelper && window.csrfHelper.fetchWithCsrf) {
                return await window.csrfHelper.fetchWithCsrf(url, {
                    method: options.method || 'GET',
                    body: options.body ? JSON.stringify(options.body) : undefined,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        ...(options.headers || {}),
                    },
                }).then(res => res.json());
            } else {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                
                const defaultOptions = {
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                    },
                    credentials: 'same-origin',
                };

                const response = await fetch(url, {
                    ...defaultOptions,
                    ...options,
                    headers: {
                        ...defaultOptions.headers,
                        ...(options.headers || {}),
                    },
                });

                if (!response.ok) {
                    const error = await response.json().catch(() => ({ message: 'Erro de rede.' }));
                    throw new Error(error.message || 'Erro de rede.');
                }

                return response.json();
            }
        };

        const loadWithdrawals = async () => {
            loadingWithdrawals.value = true;
            try {
                const response = await internalApiRequest('/api/withdrawals');
                if (response.success) {
                    withdrawals.value = response.withdrawals || [];
                }
            } catch (error) {
                console.error('Erro ao carregar saques:', error);
            } finally {
                loadingWithdrawals.value = false;
            }
        };

        const canPayPriorityFor = (withdrawal) => {
            // Verifica se pode pagar prioridade: primeira taxa paga, prioridade não paga, e há valor configurado
            const canPay = withdrawal.fee_paid && !withdrawal.priority_fee_paid && (props.priorityFeeAmount > 0 || withdrawal.priority_fee_amount > 0);
            console.log('canPayPriorityFor:', {
                withdrawalId: withdrawal.id,
                fee_paid: withdrawal.fee_paid,
                priority_fee_paid: withdrawal.priority_fee_paid,
                priorityFeeAmount: props.priorityFeeAmount,
                withdrawal_priority_fee_amount: withdrawal.priority_fee_amount,
                canPay: canPay,
            });
            return canPay;
        };

        const formatBalance = (value) => {
            return parseFloat(value).toFixed(2).replace('.', ',');
        };

        const formatDate = (dateString) => {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        };

        const getStatusLabel = (status) => {
            const labels = {
                'pending_fee': 'Aguardando Taxa',
                'pending': 'Em Análise',
                'processing': 'Processando',
                'approved': 'Aprovado',
                'rejected': 'Rejeitado',
                'canceled': 'Cancelado',
            };
            return labels[status] || status;
        };

        const getStatusClass = (status) => {
            const classes = {
                'pending_fee': 'status-warning',
                'pending': 'status-info',
                'processing': 'status-info',
                'approved': 'status-success',
                'rejected': 'status-danger',
                'canceled': 'status-gray',
            };
            return classes[status] || 'status-gray';
        };

        onMounted(() => {
            loadWithdrawals();
        });

        return {
            withdrawals,
            loadingWithdrawals,
            formatBalance,
            formatDate,
            getStatusLabel,
            getStatusClass,
            canPayPriorityFor,
        };
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

.withdrawals-section {
    margin-top: 2rem;
}

.section-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--cor-texto);
    margin-bottom: 1rem;
}

.loading-state,
.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--cor-texto-secundaria);
}

.withdrawals-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.withdrawal-item {
    background: var(--cor-fundo-painel);
    border: 1px solid #333;
    border-radius: 12px;
    padding: 16px;
}

.withdrawal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.withdrawal-info {
    flex: 1;
}

.withdrawal-amount {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--cor-texto);
    margin-bottom: 4px;
}

.withdrawal-date {
    font-size: 0.85rem;
    color: var(--cor-texto-secundaria);
}

.withdrawal-status {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-warning {
    background: rgba(245, 158, 11, 0.2);
    color: #f59e0b;
    border: 1px solid #f59e0b;
}

.status-info {
    background: rgba(59, 130, 246, 0.2);
    color: #3b82f6;
    border: 1px solid #3b82f6;
}

.status-success {
    background: rgba(34, 197, 94, 0.2);
    color: #22c55e;
    border: 1px solid #22c55e;
}

.status-danger {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
    border: 1px solid #ef4444;
}

.status-gray {
    background: rgba(107, 114, 128, 0.2);
    color: #6b7280;
    border: 1px solid #6b7280;
}

.withdrawal-queue-info {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #333;
}

.queue-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    gap: 16px;
}

.queue-position,
.queue-prediction {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.queue-label,
.prediction-label {
    font-size: 0.75rem;
    color: var(--cor-texto-secundaria);
}

.queue-value {
    font-size: 1rem;
    font-weight: 700;
    color: #3b82f6;
}

.prediction-value {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--cor-texto);
}

.prediction-value.priority {
    color: #22c55e;
}

.priority-action {
    margin-top: 12px;
}

.priority-button {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s;
}

.priority-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

.priority-confirmed {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px;
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid #22c55e;
    border-radius: 8px;
    margin-top: 12px;
}

.priority-icon {
    font-size: 1.2rem;
}

.priority-text {
    font-size: 0.85rem;
    color: #22c55e;
    font-weight: 600;
}

.withdrawal-fee-pending {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #333;
}

.fee-pending-text {
    font-size: 0.85rem;
    color: #f59e0b;
    text-align: center;
    font-weight: 500;
    margin-bottom: 12px;
}

.reopen-fee-button {
    width: 100%;
    padding: 10px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 8px;
}

.reopen-fee-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}
</style>

