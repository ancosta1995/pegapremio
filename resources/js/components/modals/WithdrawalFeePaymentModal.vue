<template>
    <div class="modal-overlay active" @click.self="$emit('close')">
        <div class="modal-content" style="max-width: 380px;">
            <div class="modal-header">
                <h3 class="modal-title" style="font-size: 18px;">{{ isPriorityFee ? 'Pagar Taxa de Prioridade' : 'Pagar Taxa de Validação' }}</h3>
                <button class="modal-close" @click="$emit('close')">&times;</button>
            </div>
            <div class="modal-body" style="padding: 15px;">
                <!-- QR Code -->
                <div v-if="qrCodeUrl" style="text-align: center; margin: 10px 0;">
                    <p style="font-size: 12px; color: #6b7280; margin-bottom: 10px;">
                        Escaneie o QR Code ou copie o código PIX:
                    </p>
                    <img :src="qrCodeUrl" alt="QR Code PIX" style="max-width: 200px; width: 100%; border: 2px solid #e5e7eb; border-radius: 6px; padding: 10px; background: white; margin: 0 auto; display: block;" />
                    <div style="margin-top: 12px;">
                        <p style="font-size: 11px; color: #6b7280; margin-bottom: 6px; font-weight: 600;">Código PIX:</p>
                        <div style="background: #f3f4f6; padding: 8px; border-radius: 6px; word-break: break-all; font-size: 10px; font-family: monospace; color: #374151; border: 1px solid #e5e7eb; max-height: 60px; overflow-y: auto;">
                            {{ pixCode }}
                        </div>
                        <button 
                            @click="copyPixCode" 
                            style="margin-top: 8px; padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.3s; width: 100%;"
                            :style="{ background: copied ? '#10b981' : '#3b82f6' }"
                        >
                            {{ copied ? '✓ Copiado!' : '📋 Copiar Código PIX' }}
                        </button>
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="loading" style="text-align: center; padding: 30px 15px;">
                    <div style="font-size: 32px; margin-bottom: 10px;">⏳</div>
                    <p style="color: #6b7280; font-size: 14px;">Gerando QR Code...</p>
                </div>

                <!-- Status do pagamento -->
                <div v-if="checkingPayment && !loading" style="text-align: center; padding: 15px; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 8px; margin-top: 12px; border: 2px solid #3b82f6;">
                    <div style="font-size: 32px; margin-bottom: 8px;">🔍</div>
                    <p style="color: #1e40af; font-weight: 600; margin-bottom: 4px; font-size: 13px;">Aguardando confirmação...</p>
                    <p style="font-size: 11px; color: #3b82f6; margin-bottom: 8px;">Verificando a cada 3 segundos</p>
                    <div style="display: inline-block; width: 24px; height: 24px; border: 3px solid #3b82f6; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                </div>

                <div v-if="!loading && !qrCodeUrl && !checkingPayment" style="text-align: center; padding: 15px;">
                    <p style="color: #6b7280; font-size: 13px;">Gerando QR Code...</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue';

export default {
    name: 'WithdrawalFeePaymentModal',
    props: {
        withdrawalId: {
            type: Number,
            required: true,
        },
        feeAmount: {
            type: Number,
            required: true,
        },
        isPriorityFee: {
            type: Boolean,
            default: false,
        },
    },
    setup(props, { emit }) {
        const qrCodeUrl = ref(null);
        const pixCode = ref('');
        const loading = ref(false);
        const checkingPayment = ref(false);
        const copied = ref(false);
        let paymentCheckInterval = null;

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

        const generatePayment = async () => {
            loading.value = true;
            try {
                // O backend já verifica se existe transação pendente e reutiliza
                // ou cria uma nova se necessário
                const endpoint = props.isPriorityFee 
                    ? '/api/withdrawals/priority-fee/payment' 
                    : '/api/withdrawals/fee/payment';
                const response = await internalApiRequest(endpoint, {
                    method: 'POST',
                    body: JSON.stringify({
                        withdrawal_id: props.withdrawalId,
                        fee_amount: props.feeAmount,
                    }),
                });

                if (response.success) {
                    qrCodeUrl.value = response.qr_code_url || response.qr_code;
                    pixCode.value = response.pix_code || response.qr_code_text || '';
                    
                    // Inicia verificação de pagamento
                    checkingPayment.value = true;
                    startPaymentCheck();
                } else {
                    throw new Error(response.message || 'Erro ao gerar pagamento.');
                }
            } catch (error) {
                console.error('Erro ao gerar pagamento:', error);
                if (window.showErrorToast) {
                    window.showErrorToast(error.message || 'Erro ao gerar pagamento.');
                } else if (window.Notiflix) {
                    window.Notiflix.Notify.failure(`❄️ ${error.message || 'Erro ao gerar pagamento.'}`);
                }
            } finally {
                loading.value = false;
            }
        };

        const startPaymentCheck = () => {
            paymentCheckInterval = setInterval(async () => {
                try {
                    const response = await internalApiRequest(`/api/withdrawals/${props.withdrawalId}/fee/status`);
                    
                    const isPaid = props.isPriorityFee 
                        ? (response.success && response.priority_fee_paid)
                        : (response.success && response.fee_paid);
                    
                    if (isPaid) {
                        // Taxa paga! Para verificação e fecha modal
                        clearInterval(paymentCheckInterval);
                        checkingPayment.value = false;
                        
                        if (props.isPriorityFee) {
                            // Taxa de prioridade paga
                            if (window.showSuccessToast) {
                                window.showSuccessToast('Taxa de prioridade paga com sucesso! Seu saque terá prioridade no processamento.');
                            } else if (window.Notiflix) {
                                window.Notiflix.Report.success(
                                    '✅ Prioridade Ativada!',
                                    'Sua taxa de prioridade foi confirmada. Seu saque será processado com prioridade.',
                                    'OK'
                                );
                            }
                            emit('priority-fee-paid');
                        } else {
                            // Primeira taxa paga - mostra modal de fila
                            emit('fee-paid', response.queue_position);
                        }
                    }
                } catch (error) {
                    console.error('Erro ao verificar pagamento:', error);
                }
            }, 3000); // Verifica a cada 3 segundos
        };

        const copyPixCode = () => {
            if (pixCode.value) {
                navigator.clipboard.writeText(pixCode.value).then(() => {
                    copied.value = true;
                    setTimeout(() => {
                        copied.value = false;
                    }, 2000);
                });
            }
        };

        onUnmounted(() => {
            if (paymentCheckInterval) {
                clearInterval(paymentCheckInterval);
            }
        });

        // Gera pagamento automaticamente ao abrir o modal
        onMounted(() => {
            // Verifica se os valores necessários estão presentes
            if (!props.withdrawalId || !props.feeAmount || props.feeAmount <= 0) {
                console.error('WithdrawalFeePaymentModal: Valores inválidos', {
                    withdrawalId: props.withdrawalId,
                    feeAmount: props.feeAmount,
                    isPriorityFee: props.isPriorityFee,
                });
                if (window.showErrorToast) {
                    window.showErrorToast('Erro: Dados do pagamento inválidos. Tente novamente.');
                }
                return;
            }
            generatePayment();
        });

        return {
            qrCodeUrl,
            pixCode,
            loading,
            checkingPayment,
            copied,
            generatePayment,
            copyPixCode,
        };
    },
};
</script>

<style scoped>
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

