<template>
    <div class="modal-overlay active" @click.self="$emit('close')">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h3 class="modal-title">Pagar Taxa de Saque</h3>
                <button class="modal-close" @click="$emit('close')">&times;</button>
            </div>
            <div class="modal-body">
                <!-- QR Code -->
                <div v-if="qrCodeUrl" style="text-align: center; margin: 20px 0;">
                    <p style="font-size: 14px; color: #6b7280; margin-bottom: 15px;">
                        Escaneie o QR Code ou copie o código PIX abaixo:
                    </p>
                    <img :src="qrCodeUrl" alt="QR Code PIX" style="max-width: 280px; width: 100%; border: 2px solid #e5e7eb; border-radius: 8px; padding: 15px; background: white; margin: 0 auto; display: block;" />
                    <div style="margin-top: 20px;">
                        <p style="font-size: 12px; color: #6b7280; margin-bottom: 8px; font-weight: 600;">Código PIX Copia e Cola:</p>
                        <div style="background: #f3f4f6; padding: 12px; border-radius: 8px; word-break: break-all; font-size: 11px; font-family: monospace; color: #374151; border: 1px solid #e5e7eb;">
                            {{ pixCode }}
                        </div>
                        <button 
                            @click="copyPixCode" 
                            style="margin-top: 12px; padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s;"
                            :style="{ background: copied ? '#10b981' : '#3b82f6' }"
                        >
                            {{ copied ? '✓ Código Copiado!' : '📋 Copiar Código PIX' }}
                        </button>
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="loading" style="text-align: center; padding: 60px 20px;">
                    <div style="font-size: 48px; margin-bottom: 15px;">⏳</div>
                    <p style="color: #6b7280; font-size: 16px;">Gerando QR Code...</p>
                </div>

                <!-- Status do pagamento -->
                <div v-if="checkingPayment && !loading" style="text-align: center; padding: 30px 20px; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 12px; margin-top: 20px; border: 2px solid #3b82f6;">
                    <div style="font-size: 48px; margin-bottom: 15px;">🔍</div>
                    <p style="color: #1e40af; font-weight: 600; margin-bottom: 8px; font-size: 16px;">Aguardando confirmação do pagamento...</p>
                    <p style="font-size: 13px; color: #3b82f6;">Verificando a cada 3 segundos</p>
                    <div style="margin-top: 15px;">
                        <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #3b82f6; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    </div>
                </div>

                <div v-if="!loading && !qrCodeUrl && !checkingPayment" style="text-align: center; padding: 20px;">
                    <p style="color: #6b7280;">Clique no botão abaixo para gerar o QR Code</p>
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
                const response = await internalApiRequest('/api/withdrawals/fee/payment', {
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
                    
                    if (response.success && response.fee_paid) {
                        // Taxa paga! Para verificação e fecha modal
                        clearInterval(paymentCheckInterval);
                        checkingPayment.value = false;
                        
                        if (window.showSuccessToast) {
                            window.showSuccessToast('Taxa de saque paga com sucesso! Seu saque foi enviado para análise.');
                        } else if (window.Notiflix) {
                            window.Notiflix.Report.success(
                                '✅ Taxa Paga!',
                                'Sua taxa de saque foi confirmada. Seu saque foi enviado para análise e será processado em breve.',
                                'OK'
                            );
                        }
                        
                        emit('fee-paid');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
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

