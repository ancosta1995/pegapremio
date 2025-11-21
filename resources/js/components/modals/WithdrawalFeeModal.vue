<template>
    <div class="modal-overlay active" @click.self="$emit('close')">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">{{ isPriorityFee ? 'Taxa de Prioridade de Saque' : 'Taxa de Saque - Validação' }}</h3>
                <button class="modal-close" @click="$emit('close')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 20px; border-radius: 12px; margin-bottom: 20px; border: 2px solid #fbbf24;">
                        <p style="font-size: 18px; color: #92400e; margin-bottom: 12px; font-weight: 700;">
                            <span v-if="isPriorityFee">
                                ⚠️ Sistema Sobrecarregado
                            </span>
                            <span v-else>
                                ⚠️ Sistema de Validação
                            </span>
                        </p>
                        <p v-if="!isPriorityFee" style="font-size: 14px; color: #78350f; line-height: 1.6; margin-bottom: 15px;">
                            Para validar sua conta e processar seu saque, é necessário pagar uma taxa de validação de 
                            <strong style="color: #16a34a; font-size: 16px;">R$ {{ formatCurrency(feeAmount) }}</strong>.
                            Esta taxa garante a segurança e autenticidade da sua solicitação.
                        </p>
                        <p v-else style="font-size: 14px; color: #78350f; line-height: 1.6; margin-bottom: 15px;">
                            Nosso sistema está recebendo um volume muito alto de solicitações de saque no momento. 
                            Para garantir que você tenha <strong>prioridade no processamento</strong> e seu saque seja 
                            analisado com urgência, você pode pagar uma taxa de prioridade de 
                            <strong style="color: #16a34a; font-size: 16px;">R$ {{ formatCurrency(feeAmount) }}</strong>.
                        </p>
                        <p v-if="!isPriorityFee" style="font-size: 14px; color: #78350f; line-height: 1.6; font-weight: 600;">
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
                    @click="handlePayFee"
                    class="modal-button"
                    style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); width: 100%; font-size: 16px; font-weight: 700; padding: 16px;"
                >
                    💳 {{ isPriorityFee ? 'Pagar Taxa de Prioridade' : 'Pagar Taxa de Validação' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>

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
        isPriorityFee: {
            type: Boolean,
            default: false,
        },
    },
    setup(props, { emit }) {
        const formatCurrency = (value) => {
            return new Intl.NumberFormat('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(value || 0);
        };

        const handlePayFee = (event) => {
            event.preventDefault();
            event.stopPropagation();
            console.log('WithdrawalFeeModal: Botão clicado, emitindo pay-fee', {
                withdrawalId: props.withdrawalId,
                feeAmount: props.feeAmount,
                isPriorityFee: props.isPriorityFee,
            });
            try {
                emit('pay-fee');
                console.log('WithdrawalFeeModal: Evento pay-fee emitido com sucesso');
            } catch (error) {
                console.error('WithdrawalFeeModal: Erro ao emitir evento pay-fee', error);
            }
        };

        return {
            formatCurrency,
            handlePayFee,
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

