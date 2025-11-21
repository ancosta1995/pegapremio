<template>
    <div class="modal-overlay active" @click.self="$emit('close')">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">Saque em Análise</h3>
                <button class="modal-close" @click="$emit('close')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); padding: 20px; border-radius: 12px; margin-bottom: 20px; border: 2px solid #3b82f6;">
                        <p style="font-size: 18px; color: #1e40af; margin-bottom: 12px; font-weight: 700;">
                            ✅ Taxa de Validação Paga!
                        </p>
                        <p style="font-size: 16px; color: #1e3a8a; line-height: 1.6; margin-bottom: 15px; font-weight: 600;">
                            Seu saque foi enviado para análise e será processado em até 
                            <strong>{{ priorityFeePaid ? '24 horas' : '7 dias úteis' }}</strong>.
                        </p>
                        <div style="background: white; padding: 15px; border-radius: 8px; margin-top: 15px;">
                            <p style="font-size: 14px; color: #64748b; margin-bottom: 8px;">Sua posição na fila:</p>
                            <p style="font-size: 32px; color: #3b82f6; font-weight: 700; margin: 0;">
                                #{{ priorityFeePaid ? queuePosition : (queuePosition + 5400) }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Botão de prioridade - sempre mostra se prioridade não foi paga -->
                    <div v-if="!priorityFeePaid" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 20px; border-radius: 12px; margin-bottom: 20px; border: 2px solid #fbbf24;">
                        <p style="font-size: 16px; color: #92400e; margin-bottom: 12px; font-weight: 700;">
                            ⚡ Acelere seu Saque!
                        </p>
                        <p style="font-size: 14px; color: #78350f; line-height: 1.6; margin-bottom: 15px;">
                            Pague uma taxa de prioridade de <strong style="color: #16a34a; font-size: 16px;">R$ {{ formatCurrency(priorityFeeAmount || 0) }}</strong> 
                            para ter seu saque processado com prioridade em <strong>24 horas</strong> e evitar a espera na fila.
                        </p>
                        <button
                            @click="$emit('pay-priority')"
                            class="modal-button"
                            style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); width: 100%; font-size: 16px; font-weight: 700; padding: 16px; margin-top: 10px;"
                        >
                            💳 Pagar Taxa de Prioridade
                        </button>
                    </div>

                    <div v-if="priorityFeePaid" style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); padding: 20px; border-radius: 12px; margin-bottom: 20px; border: 2px solid #10b981;">
                        <p style="font-size: 18px; color: #065f46; margin-bottom: 12px; font-weight: 700;">
                            ⚡ Prioridade Ativada!
                        </p>
                        <p style="font-size: 14px; color: #047857; line-height: 1.6; margin-bottom: 15px;">
                            Sua taxa de prioridade foi paga com sucesso! Seu saque será processado em até <strong>24 horas</strong>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'WithdrawalQueueModal',
    props: {
        queuePosition: {
            type: Number,
            required: true,
        },
        canPayPriority: {
            type: Boolean,
            default: false,
        },
        priorityFeeAmount: {
            type: Number,
            default: 0,
        },
        priorityFeePaid: {
            type: Boolean,
            default: false,
        },
        withdrawalId: {
            type: Number,
            default: null,
        },
    },
    emits: ['close', 'pay-priority'],
    methods: {
        formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(value || 0);
        },
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

.modal-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
}
</style>

