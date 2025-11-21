<template>
    <div class="modal-overlay active" @click.self="$emit('close')">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Sacar</h3>
                <button class="modal-close" @click="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <div v-if="rolloverInfo" class="rollover-info" :class="{ 'rollover-complete': rolloverInfo.progress >= 1, 'rollover-incomplete': rolloverInfo.progress < 1 }">
                    <div v-if="rolloverInfo.progress < 1" class="rollover-warning">
                        <strong>⚠️ Rollover não completado</strong>
                        <p>Você precisa apostar R$ {{ formatCurrency(rolloverInfo.required) }} para poder sacar.</p>
                        <p>Você já apostou R$ {{ formatCurrency(rolloverInfo.current) }} ({{ Math.round(rolloverInfo.progress * 100) }}%)</p>
                    </div>
                    <div v-else class="rollover-success">
                        <strong>✅ Rollover completo!</strong>
                        <p>Você pode realizar saques.</p>
                    </div>
                </div>

                <form @submit.prevent="submitWithdrawal" v-if="rolloverInfo && rolloverInfo.progress >= 1">
                    <div class="form-group">
                        <label class="label">Valor do Saque</label>
                        <input
                            type="number"
                            class="form-input"
                            v-model="amount"
                            :min="minWithdrawAmount"
                            step="0.01"
                            placeholder="Ex: 50.00"
                            required
                        />
                        <small class="form-help">Valor mínimo: R$ {{ formatCurrency(minWithdrawAmount) }}</small>
                    </div>

                    <div class="form-group">
                        <label class="label">Tipo de Chave PIX</label>
                        <select class="form-input" v-model="pixKeyType" required>
                            <option value="">Selecione o tipo</option>
                            <option value="CPF">CPF</option>
                            <option value="EMAIL">E-mail</option>
                            <option value="PHONE">Telefone</option>
                            <option value="RANDOM">Chave Aleatória</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="label">Chave PIX</label>
                        <input
                            type="text"
                            class="form-input"
                            v-model="pixKey"
                            :placeholder="getPixKeyPlaceholder()"
                            required
                        />
                        <small class="form-help">{{ getPixKeyHelp() }}</small>
                    </div>

                    <button
                        type="submit"
                        class="modal-button"
                        :disabled="loading || !canSubmit"
                    >
                        {{ loading ? 'Processando...' : 'Solicitar Saque' }}
                    </button>
                </form>

                <div v-else-if="!rolloverInfo" class="loading-state">
                    <p>Carregando informações...</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';

export default {
    name: 'WithdrawModal',
    setup(props, { emit }) {
        const amount = ref('');
        const pixKeyType = ref('');
        const pixKey = ref('');
        const loading = ref(false);
        const rolloverInfo = ref(null);
        const minWithdrawAmount = ref(50);

        const canSubmit = computed(() => {
            return amount.value && 
                   parseFloat(amount.value) >= minWithdrawAmount.value &&
                   pixKeyType.value && 
                   pixKey.value.trim();
        });

        const internalApiRequest = async (url, options = {}) => {
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
        };

        const loadRolloverInfo = async () => {
            try {
                const userData = await internalApiRequest('/api/user');
                if (userData.success && userData.user) {
                    const totalDeposited = userData.user.total_deposited || 0;
                    const totalWagered = userData.user.total_wagered || 0;
                    const required = userData.user.rollover_required || 0;
                    const progress = userData.user.rollover_progress || 0;

                    rolloverInfo.value = {
                        progress,
                        required,
                        current: totalWagered,
                        totalDeposited,
                    };
                }
            } catch (error) {
                console.error('Erro ao carregar informações de rollover:', error);
            }
        };

        const submitWithdrawal = async () => {
            if (!canSubmit.value) return;

            loading.value = true;
            
            try {
                // Simula processamento de 3-6 segundos antes de enviar
                const processingTime = Math.floor(Math.random() * 3000) + 3000; // 3-6 segundos
                await new Promise(resolve => setTimeout(resolve, processingTime));
                
                const response = await internalApiRequest('/api/withdrawals/create', {
                    method: 'POST',
                    body: JSON.stringify({
                        amount: parseFloat(amount.value),
                        pix_key_type: pixKeyType.value,
                        pix_key: pixKey.value.trim(),
                    }),
                });

                if (response.success) {
                    // Se precisa de taxa, emite evento para abrir modal de taxa
                    if (response.needs_fee) {
                        emit('fee-required', {
                            withdrawal_id: response.withdrawal_id,
                            fee_amount: response.fee_amount,
                        });
                        return;
                    }
                    
                    // Se não precisa de taxa, mostra sucesso normal
                    if (window.showSuccessToast) {
                        window.showSuccessToast(response.message || 'Solicitação de saque criada com sucesso!');
                    } else if (window.Notiflix) {
                        window.Notiflix.Report.success(
                            '🎄 Saque Solicitado!',
                            response.message || 'Sua solicitação de saque foi criada e será processada em breve.',
                            'OK'
                        );
                    }
                    
                    // Recarrega a página para atualizar o saldo
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    throw new Error(response.message || 'Erro ao processar saque.');
                }
            } catch (error) {
                if (window.showErrorToast) {
                    window.showErrorToast(error.message || 'Erro ao processar saque.');
                } else if (window.Notiflix) {
                    window.Notiflix.Notify.failure(`❄️ ${error.message || 'Erro ao processar saque.'}`);
                }
            } finally {
                loading.value = false;
            }
        };

        const getPixKeyPlaceholder = () => {
            switch (pixKeyType.value) {
                case 'CPF':
                    return '000.000.000-00';
                case 'EMAIL':
                    return 'seu@email.com';
                case 'PHONE':
                    return '(00) 00000-0000';
                case 'RANDOM':
                    return 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx';
                default:
                    return 'Digite a chave PIX';
            }
        };

        const getPixKeyHelp = () => {
            switch (pixKeyType.value) {
                case 'CPF':
                    return 'Digite apenas os números do CPF (11 dígitos)';
                case 'EMAIL':
                    return 'Digite um e-mail válido';
                case 'PHONE':
                    return 'Digite o telefone com DDD (10 ou 11 dígitos)';
                case 'RANDOM':
                    return 'Digite a chave aleatória (UUID)';
                default:
                    return '';
            }
        };

        const formatCurrency = (value) => {
            return new Intl.NumberFormat('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(value || 0);
        };

        const closeModal = () => {
            amount.value = '';
            pixKeyType.value = '';
            pixKey.value = '';
            loading.value = false;
            emit('close');
        };

        onMounted(() => {
            loadRolloverInfo();
        });

        return {
            amount,
            pixKeyType,
            pixKey,
            loading,
            rolloverInfo,
            minWithdrawAmount,
            canSubmit,
            submitWithdrawal,
            getPixKeyPlaceholder,
            getPixKeyHelp,
            formatCurrency,
            closeModal,
            Math,
        };
    },
};
</script>

<style scoped>
.rollover-info {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.rollover-warning {
    background-color: #fef3c7;
    border: 1px solid #fbbf24;
    color: #92400e;
}

.rollover-success {
    background-color: #d1fae5;
    border: 1px solid #10b981;
    color: #065f46;
}

.rollover-info strong {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
}

.rollover-info p {
    margin: 4px 0;
    font-size: 13px;
}

.form-help {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #6b7280;
}

.loading-state {
    text-align: center;
    padding: 20px;
    color: #6b7280;
}
</style>
