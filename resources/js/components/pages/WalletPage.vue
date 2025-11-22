<template>
    <div class="page-container">
        <div class="page-header">
            <h2 class="page-title">Carteira</h2>
        </div>
        <div class="page-content">
            <!-- Sistema de Abas -->
            <div class="modal-tabs">
                <button
                    :class="['modal-tab', { active: activeTab === 'wallet' }]"
                    @click="activeTab = 'wallet'"
                >
                    Carteira
                </button>
                <button
                    :class="['modal-tab', { active: activeTab === 'history' }]"
                    @click="activeTab = 'history'"
                    class="tab-with-badge"
                >
                    Histórico
                    <span v-if="pendingFeesCount > 0" class="tab-badge">{{ pendingFeesCount > 9 ? '9+' : pendingFeesCount }}</span>
                </button>
            </div>

            <!-- Notificações de Saques Aleatórios -->
            <div class="withdrawal-notifications">
                <transition-group name="notification" tag="div">
                    <div
                        v-for="notification in activeNotifications"
                        :key="notification.id"
                        class="withdrawal-notification"
                    >
                        <div class="notification-icon">💰</div>
                        <div class="notification-content">
                            <div class="notification-name">
                                <span class="name-text">{{ notification.name }}</span>
                                <span class="action-text"> sacou</span>
                            </div>
                            <div class="notification-amount">R$ {{ formatBalance(notification.amount) }}</div>
                        </div>
                    </div>
                </transition-group>
            </div>

            <!-- Aba: Carteira -->
            <div v-if="activeTab === 'wallet'" class="modal-tab-content active">
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

            <!-- Aba: Histórico -->
            <div v-else class="modal-tab-content active">
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
                                    ⚡ Acelerar Saque (24h)
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
                                💳 Validar Conta Para Saque
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

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
    emits: ['deposit', 'withdraw', 'pay-priority', 'reopen-fee-payment', 'pending-fees-count'],
    setup(props, { emit }) {
        const activeTab = ref('wallet');
        const withdrawals = ref([]);
        const loadingWithdrawals = ref(false);
        const activeNotifications = ref([]);
        let notificationInterval = null;
        let notificationIdCounter = 0;

        // Conta saques com taxas pendentes
        const pendingFeesCount = computed(() => {
            let count = 0;
            withdrawals.value.forEach(withdrawal => {
                // Taxa de validação pendente
                if (!withdrawal.fee_paid && withdrawal.status === 'pending_fee') {
                    count++;
                }
                // Taxa de prioridade pendente (primeira taxa paga mas prioridade não)
                else if (withdrawal.fee_paid && !withdrawal.priority_fee_paid && 
                         withdrawal.status === 'pending' && 
                         (props.priorityFeeAmount > 0 || withdrawal.priority_fee_amount > 0)) {
                    count++;
                }
            });
            return count;
        });

        // Emite o contador sempre que mudar
        watch(pendingFeesCount, (newCount) => {
            emit('pending-fees-count', newCount);
        }, { immediate: true });

        // Lista de nomes brasileiros aleatórios
        const randomNames = [
            'Maria Silva', 'João Santos', 'Ana Costa', 'Pedro Oliveira', 'Juliana Ferreira',
            'Carlos Souza', 'Fernanda Lima', 'Ricardo Alves', 'Patricia Rocha', 'Bruno Martins',
            'Camila Dias', 'Lucas Pereira', 'Amanda Ribeiro', 'Gabriel Araújo', 'Isabela Nunes',
            'Rafael Correia', 'Larissa Gomes', 'Thiago Barbosa', 'Beatriz Cardoso', 'Felipe Teixeira',
            'Mariana Castro', 'Rodrigo Mendes', 'Carolina Freitas', 'Gustavo Ramos', 'Vanessa Lopes',
            'Diego Moreira', 'Renata Azevedo', 'André Monteiro', 'Tatiana Carvalho', 'Leonardo Pinto',
            'Priscila Moura', 'Marcelo Farias', 'Daniela Cunha', 'Vinicius Machado', 'Adriana Barros'
        ];

        // Gera uma notificação aleatória
        const generateNotification = () => {
            const name = randomNames[Math.floor(Math.random() * randomNames.length)];
            const amount = Math.floor(Math.random() * (7000 - 500 + 1)) + 500; // Entre 500 e 7000
            
            return {
                id: notificationIdCounter++,
                name: name,
                amount: amount,
            };
        };

        // Adiciona uma nova notificação
        const addNotification = () => {
            const notification = generateNotification();
            activeNotifications.value.push(notification);

            // Remove a notificação após 5 segundos
            setTimeout(() => {
                const index = activeNotifications.value.findIndex(n => n.id === notification.id);
                if (index !== -1) {
                    activeNotifications.value.splice(index, 1);
                }
            }, 5000);
        };

        // Inicia o sistema de notificações
        const startNotifications = () => {
            // Primeira notificação após 4 segundos
            setTimeout(() => {
                addNotification();
            }, 4000);

            // Depois, uma nova notificação a cada 4-15 segundos (aleatório)
            const scheduleNext = () => {
                const delay = Math.floor(Math.random() * (15000 - 4000 + 1)) + 4000;
                notificationInterval = setTimeout(() => {
                    addNotification();
                    scheduleNext();
                }, delay);
            };

            scheduleNext();
        };

        // Para as notificações
        const stopNotifications = () => {
            if (notificationInterval) {
                clearTimeout(notificationInterval);
                notificationInterval = null;
            }
        };

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

        // Recarrega os dados sempre que a aba de histórico for clicada
        watch(activeTab, (newTab) => {
            if (newTab === 'history') {
                loadWithdrawals();
            }
        });

        onMounted(() => {
            loadWithdrawals();
            startNotifications();
        });

        onUnmounted(() => {
            stopNotifications();
        });

        return {
            activeTab,
            withdrawals,
            loadingWithdrawals,
            activeNotifications,
            pendingFeesCount,
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
    gap: 1.5rem;
}

.modal-tabs {
    display: flex;
    background-color: var(--cor-fundo-input);
    border-radius: 12px;
    padding: 0.5rem;
    margin-bottom: 1rem;
    gap: 0.5rem;
}

.modal-tab {
    flex: 1;
    padding: 0.75rem 1rem;
    background: transparent;
    border: none;
    color: var(--cor-texto-secundaria);
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.2s;
}

.modal-tab:hover {
    background: rgba(255, 255, 255, 0.05);
}

.modal-tab.active {
    color: white;
    background: linear-gradient(to bottom, var(--cor-principal), var(--cor-principal-dark));
}

.tab-with-badge {
    position: relative;
}

.tab-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ef4444;
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 5px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    line-height: 1.2;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.modal-tab-content {
    display: none;
}

.modal-tab-content.active {
    display: block;
    padding-top: 0;
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
    flex-direction: row;
    gap: 1rem;
    margin-top: 20px;
}

.action-button {
    flex: 1;
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

/* Notificações de Saques Aleatórios */
.withdrawal-notifications {
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    gap: 14px;
    max-width: 280px;
    pointer-events: none;
}

.withdrawal-notification {
    background: linear-gradient(135deg, rgba(74, 222, 128, 0.85) 0%, rgba(52, 211, 153, 0.85) 100%);
    border: 1px solid rgba(74, 222, 128, 0.6);
    border-radius: 10px;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 16px rgba(74, 222, 128, 0.25), 0 0 12px rgba(74, 222, 128, 0.15);
    backdrop-filter: blur(8px);
    pointer-events: auto;
    animation: notificationShimmer 3s infinite;
}

@keyframes notificationShimmer {
    0%, 100% {
        box-shadow: 0 4px 16px rgba(74, 222, 128, 0.25), 0 0 12px rgba(74, 222, 128, 0.15);
    }
    50% {
        box-shadow: 0 4px 20px rgba(74, 222, 128, 0.35), 0 0 16px rgba(74, 222, 128, 0.25);
    }
}

.notification-icon {
    font-size: 24px;
    filter: drop-shadow(0 0 6px rgba(251, 191, 36, 0.6));
    animation: notificationIconPulse 2s infinite;
    flex-shrink: 0;
    opacity: 0.9;
}

@keyframes notificationIconPulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-name {
    font-size: 12px;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.name-text {
    font-weight: 500;
    color: rgba(255, 255, 255, 0.85);
}

.action-text {
    font-weight: 700;
    color: #fde047;
    text-shadow: 0 0 8px rgba(253, 224, 71, 0.5);
}

.notification-amount {
    font-size: 16px;
    font-weight: 700;
    color: #fde047;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    letter-spacing: 0.3px;
}

/* Animações de entrada e saída */
.notification-enter-active {
    animation: notificationSlideIn 0.4s ease-out;
}

.notification-leave-active {
    animation: notificationSlideOut 0.3s ease-in;
}

@keyframes notificationSlideIn {
    from {
        opacity: 0;
        transform: translateX(100%) scale(0.8);
    }
    to {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
}

@keyframes notificationSlideOut {
    from {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
    to {
        opacity: 0;
        transform: translateX(100%) scale(0.8);
    }
}

/* Responsivo */
@media (max-width: 768px) {
    .withdrawal-notifications {
        top: 70px;
        right: 10px;
        left: 10px;
        max-width: none;
    }

    .withdrawal-notification {
        padding: 10px 12px;
    }

    .notification-icon {
        font-size: 22px;
    }

    .notification-name {
        font-size: 11px;
    }

    .notification-amount {
        font-size: 15px;
    }
}
</style>

