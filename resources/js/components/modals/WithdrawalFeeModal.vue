<template>
    <div class="modal-overlay active" @click.self="$emit('close')">
        <!-- MODAL DE PRIORIDADE - Design Completamente Diferente -->
        <div v-if="isPriorityFee" class="priority-modal-content">
            <div class="priority-modal-header">
                <div class="priority-icon-container">
                    <div class="priority-icon">⚡</div>
                    <div class="priority-pulse"></div>
                </div>
                <h2 class="priority-title">ACELERE SEU SAQUE AGORA!</h2>
                <button class="priority-close" @click="$emit('close')">&times;</button>
            </div>
            
            <div class="priority-modal-body">
                <div class="priority-badge">
                    <span class="priority-badge-text">OPORTUNIDADE EXCLUSIVA</span>
                </div>
                
                <div class="priority-main-content">
                    <div class="priority-time-comparison">
                        <div class="time-box normal">
                            <div class="time-icon">⏳</div>
                            <div class="time-label">SEM PRIORIDADE</div>
                            <div class="time-value">7 dias úteis</div>
                        </div>
                        <div class="time-arrow">→</div>
                        <div class="time-box priority">
                            <div class="time-icon">⚡</div>
                            <div class="time-label">COM PRIORIDADE</div>
                            <div class="time-value">24 horas</div>
                        </div>
                    </div>
                    
                    <div class="priority-benefits">
                        <div class="benefit-item">
                            <span class="benefit-icon">🚀</span>
                            <span class="benefit-text">Processamento em <strong>24 horas</strong></span>
                        </div>
                        <div class="benefit-item">
                            <span class="benefit-icon">🎯</span>
                            <span class="benefit-text">Pule a fila de espera</span>
                        </div>
                        <div class="benefit-item">
                            <span class="benefit-icon">💎</span>
                            <span class="benefit-text">Atendimento prioritário</span>
                        </div>
                    </div>
                    
                    <div class="priority-price-box">
                        <div class="price-label">Taxa de Prioridade</div>
                        <div class="price-value">R$ {{ formatCurrency(feeAmount) }}</div>
                        <div class="price-note">Pague agora e receba em 24h!</div>
                    </div>
                </div>
                
                <button
                    @click="handlePayFee"
                    class="priority-pay-button"
                >
                    <span class="button-icon">💳</span>
                    <span class="button-text">PAGAR AGORA E ACELERAR</span>
                    <span class="button-arrow">→</span>
                </button>
                
                <p class="priority-disclaimer">
                    ⚠️ Esta é uma <strong>opção opcional</strong> para acelerar seu saque. 
                    Seu saque será processado normalmente em 7 dias úteis sem esta taxa.
                </p>
            </div>
        </div>
        
        <!-- MODAL DE TAXA DE VALIDAÇÃO - Design Original -->
        <div v-else class="modal-content fee-modal-compact" style="max-width: 450px;">
            <div class="modal-header" style="padding: 12px 16px;">
                <h3 class="modal-title" style="font-size: 16px;">Taxa de Saque - Validação</h3>
                <button class="modal-close" @click="$emit('close')">&times;</button>
            </div>
            <div class="modal-body" style="padding: 12px 16px;">
                <div style="text-align: center; margin-bottom: 12px;">
                    <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 12px; border-radius: 10px; margin-bottom: 12px; border: 2px solid #fbbf24;">
                        <p style="font-size: 14px; color: #92400e; margin-bottom: 8px; font-weight: 700;">
                            ⚠️ Taxa de Saque Obrigatória
                        </p>
                        <p style="font-size: 12px; color: #78350f; line-height: 1.4; margin-bottom: 10px;">
                            Para concluir seu saque, é necessário efetuar o pagamento de uma pequena taxa de processamento de segurança de 
                            <strong style="color: #16a34a; font-size: 14px;">R$ {{ formatCurrency(feeAmount) }}</strong>.
                            Essa taxa funciona como uma medida antifraude, garantindo que o valor seja liberado com total segurança.
                        </p>
                        <p style="font-size: 11px; color: #78350f; line-height: 1.3; font-weight: 600;">
                            💰 Este valor é <strong>100% reembolsado</strong> junto com seu saldo.
                        </p>
                    </div>
                </div>

                <!-- Botão Pagar Taxa -->
                <button
                    @click="handlePayFee"
                    class="modal-button"
                    style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); width: 100%; font-size: 14px; font-weight: 700; padding: 12px;"
                >
                    💳 Pagar Taxa de Saque
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

/* ============================================
   ESTILOS DO MODAL DE PRIORIDADE
   Design completamente diferente e chamativo
   ============================================ */

.priority-modal-content {
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    background: var(--cor-fundo-painel, #1a1a1a);
    border-radius: 14px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    border: 2px solid #fbbf24;
    position: relative;
    overflow-x: hidden;
    animation: priorityModalEntrance 0.5s ease-out;
}

/* Estilização da barra de rolagem - tema natalino */
.priority-modal-content::-webkit-scrollbar {
    width: 8px;
}

.priority-modal-content::-webkit-scrollbar-track {
    background: var(--cor-fundo-input, #2a2a2a);
    border-radius: 4px;
}

.priority-modal-content::-webkit-scrollbar-thumb {
    background: #fbbf24;
    border-radius: 4px;
}

.priority-modal-content::-webkit-scrollbar-thumb:hover {
    background: #f59e0b;
}

/* Removido ::before que causava problema na barra de rolagem */

@keyframes priorityModalEntrance {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Animação removida - estava causando problema na barra de rolagem */

.priority-modal-header {
    position: relative;
    padding: 12px 16px 10px;
    text-align: center;
    background: rgba(251, 191, 36, 0.1);
    border-bottom: 2px solid #fbbf24;
}

.priority-icon-container {
    position: relative;
    display: inline-block;
    margin-bottom: 6px;
}

.priority-icon {
    font-size: 32px;
    color: #fbbf24;
    animation: priorityIconPulse 2s infinite;
    position: relative;
    z-index: 2;
}

.priority-pulse {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 50px;
    height: 50px;
    border: 2px solid #fbbf24;
    border-radius: 50%;
    animation: priorityPulse 2s infinite;
}

@keyframes priorityIconPulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

@keyframes priorityPulse {
    0% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
    }
    100% {
        transform: translate(-50%, -50%) scale(1.5);
        opacity: 0;
    }
}

.priority-title {
    font-size: 15px;
    font-weight: 900;
    color: #fbbf24;
    margin: 0;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    line-height: 1.2;
}

.priority-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(42, 42, 42, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: var(--cor-texto, #ffffff);
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-size: 20px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.priority-close:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(90deg);
}

.priority-modal-body {
    padding: 12px 16px;
    position: relative;
    z-index: 1;
}

.priority-badge {
    text-align: center;
    margin-bottom: 12px;
}

.priority-badge-text {
    display: inline-block;
    background: #fbbf24;
    color: var(--cor-fundo, #131313);
    padding: 5px 15px;
    border-radius: 15px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.priority-main-content {
    background: var(--cor-fundo-input, #2a2a2a);
    border-radius: 8px;
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid rgba(251, 191, 36, 0.3);
}

.priority-time-comparison {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 10px;
}

.time-box {
    flex: 1;
    padding: 8px 6px;
    border-radius: 6px;
    text-align: center;
    transition: all 0.3s;
}

.time-box.normal {
    background: var(--cor-fundo-input, #2a2a2a);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.time-box.priority {
    background: rgba(251, 191, 36, 0.15);
    border: 2px solid #fbbf24;
    box-shadow: 0 0 10px rgba(251, 191, 36, 0.3);
    transform: scale(1.05);
}

.time-icon {
    font-size: 18px;
    margin-bottom: 3px;
}

.time-label {
    font-size: 9px;
    color: rgba(255, 255, 255, 0.7);
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-bottom: 4px;
    font-weight: 600;
}

.time-value {
    font-size: 12px;
    font-weight: 800;
    color: var(--cor-texto, #ffffff);
}

.time-box.priority .time-value {
    color: #fbbf24;
}

.time-arrow {
    font-size: 18px;
    color: #fbbf24;
    font-weight: 900;
    animation: priorityArrowPulse 1.5s infinite;
}

@keyframes priorityArrowPulse {
    0%, 100% {
        transform: translateX(0);
    }
    50% {
        transform: translateX(5px);
    }
}

.priority-benefits {
    margin-bottom: 10px;
}

.benefit-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 6px;
    margin-bottom: 3px;
    background: rgba(42, 42, 42, 0.5);
    border-radius: 5px;
    border-left: 3px solid #fbbf24;
}

.benefit-item:nth-child(2) {
    border-left-color: #f59e0b;
}

.benefit-item:nth-child(3) {
    border-left-color: #eab308;
}

.benefit-icon {
    font-size: 16px;
    flex-shrink: 0;
}

.benefit-text {
    color: var(--cor-texto, #ffffff);
    font-size: 11px;
    font-weight: 600;
    line-height: 1.3;
}

.benefit-text strong {
    color: #fbbf24;
}

.priority-price-box {
    text-align: center;
    padding: 10px;
    background: rgba(251, 191, 36, 0.1);
    border-radius: 6px;
    border: 2px solid #fbbf24;
}

.price-label {
    font-size: 9px;
    color: var(--cor-texto-secundaria, #aaa);
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-bottom: 4px;
}

.price-value {
    font-size: 20px;
    font-weight: 900;
    color: #fbbf24;
    margin-bottom: 3px;
    line-height: 1.2;
}

.price-note {
    font-size: 10px;
    color: var(--cor-texto-secundaria, #aaa);
    line-height: 1.3;
}

.priority-pay-button {
    width: 100%;
    padding: 11px;
    background: #fbbf24;
    border: none;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 900;
    color: var(--cor-fundo, #131313);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    box-shadow: 0 5px 20px rgba(251, 191, 36, 0.4);
    transition: all 0.3s;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-bottom: 8px;
    position: relative;
    overflow: hidden;
    line-height: 1.2;
}

.priority-pay-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
}

.priority-pay-button:hover::before {
    left: 100%;
}

.priority-pay-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 40px rgba(251, 191, 36, 0.6);
    background: #f59e0b;
}

.priority-pay-button:active {
    transform: translateY(-1px);
}

.button-icon {
    font-size: 18px;
    flex-shrink: 0;
}

.button-text {
    flex: 1;
    font-size: 12px;
}

.button-arrow {
    font-size: 18px;
    transition: transform 0.3s;
    flex-shrink: 0;
}

.priority-pay-button:hover .button-arrow {
    transform: translateX(5px);
}

.priority-disclaimer {
    text-align: center;
    font-size: 9px;
    color: var(--cor-texto-secundaria, #aaa);
    line-height: 1.3;
    margin: 0;
    padding: 0 5px;
}

.priority-disclaimer strong {
    color: var(--cor-texto, #ffffff);
}

/* Modal compacto para taxa de saque */
.fee-modal-compact {
    max-height: 90vh;
    overflow-y: auto;
}

.fee-modal-compact .modal-body {
    max-height: calc(90vh - 60px);
}
</style>

