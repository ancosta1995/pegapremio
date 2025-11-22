<template>
    <div class="modal-overlay active" @click.self="$emit('close')">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Depositar</h3>
                <button class="modal-close" @click="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <form v-if="!showQrCode" id="depositForm" @submit.prevent="generatePayment">
                    <div class="deposit-header">
                        <div class="deposit-icon">💰</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="label">Valor</label>
                        <div class="amount-input-wrapper">
                            <span class="currency-symbol">R$</span>
                            <input
                                type="text"
                                class="form-input amount-input"
                                v-model="amount"
                                placeholder="0,00"
                                @input="formatAmount"
                                required
                            />
                        </div>
                    </div>

                    <div class="quick-amounts">
                        <p class="quick-amounts-label">Valores Rápidos</p>
                        <div class="amount-buttons">
                            <button
                                type="button"
                                v-for="quickAmount in quickAmounts"
                                :key="quickAmount"
                                class="amount-btn"
                                :class="{ active: amount === formatAmountValue(quickAmount) }"
                                @click="selectAmount(quickAmount)"
                            >
                                R$ {{ formatAmountValue(quickAmount) }}
                            </button>
                        </div>
                    </div>

                    <div class="deposit-info">
                        <div class="info-item">
                            <span class="info-icon">⚡</span>
                            <span>PIX instantâneo</span>
                        </div>
                        <div class="info-item">
                            <span class="info-icon">🔒</span>
                            <span>100% seguro</span>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="modal-button deposit-button"
                        :disabled="loading || !amount"
                    >
                        <span v-if="!loading">🎄 Gerar PIX</span>
                        <span v-else>⏳ Gerando...</span>
                    </button>
                </form>
                <div v-else id="qr-code-display" class="qr-code-display">
                    <div class="qr-header">
                        <div class="qr-icon">📱</div>
                        <h4>Escaneie o QR Code</h4>
                        <p>ou copie o código PIX abaixo</p>
                    </div>
                    <div class="qr-code-wrapper">
                        <img :src="qrCodeImage" alt="QR Code" id="qr-code-image">
                    </div>
                    <div class="pix-code-wrapper">
                        <div id="pix-copy-paste" class="pix-code">{{ pixCode }}</div>
                        <button id="copyPixBtn" class="copy-pix-btn" @click="copyPix">
                            <span>📋</span> Copiar Código PIX
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, watch } from 'vue';

export default {
    name: 'DepositModal',
    props: {
        minDepositAmount: {
            type: Number,
            default: 10.00
        }
    },
    setup(props, { emit }) {
        const amount = ref('');
        const loading = ref(false);
        const showQrCode = ref(false);
        const qrCodeImage = ref('');
        const pixCode = ref('');
        const transactionId = ref(null);
        const statusCheckInterval = ref(null);

        // Obtém o token CSRF
        const getCsrfToken = () => {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        };

        // Calcula os valores rápidos dinamicamente baseado no mínimo
        const quickAmounts = computed(() => {
            const min = props.minDepositAmount || 10.00;
            const amounts = [];
            
            // Primeiro valor sempre é o mínimo
            amounts.push(min);
            
            // Calcula os próximos valores baseado no mínimo
            // Se mínimo <= 10: 10, 25, 50, 100, 200, 500
            // Se mínimo <= 25: min, 50, 100, 200, 500, 1000
            // Se mínimo <= 50: min, 100, 200, 500, 1000, 2000
            // Se mínimo > 50: min, min*2, min*5, min*10, min*20, min*50
            if (min <= 10) {
                amounts.push(25, 50, 100, 200, 500);
            } else if (min <= 25) {
                amounts.push(50, 100, 200, 500, 1000);
            } else if (min <= 50) {
                amounts.push(100, 200, 500, 1000, 2000);
            } else {
                // Para valores maiores, usa múltiplos do mínimo
                amounts.push(min * 2, min * 5, min * 10, min * 20, min * 50);
            }
            
            return amounts;
        });

        const formatAmount = (event) => {
            let value = event.target.value.replace(/\D/g, '');
            if (value) {
                value = (parseInt(value) / 100).toFixed(2);
                value = value.replace('.', ',');
                value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }
            amount.value = value;
        };

        const formatAmountValue = (value) => {
            return value.toFixed(2).replace('.', ',');
        };

        const selectAmount = (value) => {
            amount.value = formatAmountValue(value);
        };

        const getAmountAsNumber = () => {
            return parseFloat(amount.value.replace(/\./g, '').replace(',', '.')) || 0;
        };

        const generatePayment = async () => {
            loading.value = true;
            try {
                const amountValue = getAmountAsNumber();
                
                // Cria a transação de pagamento
                const response = await fetch('/api/payments/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                    body: JSON.stringify({
                        amount: amountValue,
                        payment_method: 'PIX',
                    }),
                });
                
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Erro ao criar transação');
                }
                
                const res = await response.json();
                
                if (res.success && res.transaction) {
                    transactionId.value = res.transaction.id;
                    
                    // Processa QR code - pode vir em diferentes formatos
                    if (res.transaction.qr_code) {
                        const qrCode = res.transaction.qr_code;
                        // Se já é uma URL completa de imagem
                        if (qrCode.startsWith('http://') || qrCode.startsWith('https://')) {
                            qrCodeImage.value = qrCode;
                        }
                        // Se já é data URI
                        else if (qrCode.startsWith('data:image')) {
                            qrCodeImage.value = qrCode;
                        }
                        // Se é base64 puro, adiciona o prefixo
                        else {
                            qrCodeImage.value = `data:image/png;base64,${qrCode}`;
                        }
                    }
                    
                    // Se tiver payment_url mas não tiver QR code, pode ser uma URL de pagamento
                    if (res.transaction.payment_url && !qrCodeImage.value) {
                        // Se for uma URL de imagem, usa como QR code
                        if (res.transaction.payment_url.match(/\.(jpg|jpeg|png|gif|webp)$/i)) {
                            qrCodeImage.value = res.transaction.payment_url;
                        }
                    }
                    
                    // Código PIX para copiar (prioriza qr_code_text, depois qr_code, depois payment_url)
                    pixCode.value = res.transaction.qr_code_text || res.transaction.qr_code || res.transaction.payment_url || '';
                    
                    // Se não tiver QR code nem código PIX, mostra erro
                    if (!qrCodeImage.value && !pixCode.value) {
                        throw new Error('Não foi possível gerar o QR Code. Tente novamente.');
                    }
                    
                    showQrCode.value = true;
                    
                    // Inicia verificação de status
                    startStatusCheck();
                } else {
                    throw new Error(res.message || 'Erro ao criar transação');
                }
            } catch (err) {
                if (window.showErrorToast) {
                    window.showErrorToast(err.message || 'Erro ao gerar pagamento');
                } else if (window.Notiflix) {
                    window.Notiflix.Notify.failure(`❄️ ${err.message || 'Erro ao gerar pagamento'}`);
                }
            } finally {
                loading.value = false;
            }
        };
        
        // Verifica o status da transação periodicamente
        const startStatusCheck = () => {
            if (statusCheckInterval.value) {
                clearInterval(statusCheckInterval.value);
            }
            
            console.log('Iniciando verificação de status da transação:', transactionId.value);
            
            statusCheckInterval.value = setInterval(async () => {
                if (!transactionId.value) {
                    console.warn('Transaction ID não encontrado, parando verificação');
                    clearInterval(statusCheckInterval.value);
                    statusCheckInterval.value = null;
                    return;
                }
                
                try {
                    console.log('Verificando status da transação:', transactionId.value);
                    const response = await fetch(`/api/payments/transaction/${transactionId.value}`, {
                        headers: {
                            'X-CSRF-TOKEN': getCsrfToken(),
                        },
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        if (data.success && data.transaction) {
                            console.log('Status da transação:', data.transaction.status);
                            
                            // Se foi aprovado, atualiza saldo e fecha modal
                            if (data.transaction.status === 'approved') {
                                clearInterval(statusCheckInterval.value);
                                statusCheckInterval.value = null;
                                
                                // Mostra toast de sucesso
                                if (window.showSuccessToast) {
                                    window.showSuccessToast('Pagamento aprovado! Saldo atualizado.');
                                } else if (window.Notiflix) {
                                    window.Notiflix.Notify.success('✅ Pagamento aprovado! Saldo atualizado.');
                                }
                                
                                // Fecha o modal imediatamente
                                setTimeout(() => {
                                    closeModal();
                                    
                                    // Recarrega dados do usuário para atualizar saldo
                                    if (window.location.reload) {
                                        setTimeout(() => {
                                            window.location.reload();
                                        }, 500);
                                    }
                                }, 1000);
                            } else if (data.transaction.status === 'rejected' || data.transaction.status === 'canceled') {
                                clearInterval(statusCheckInterval.value);
                                statusCheckInterval.value = null;
                                
                                if (window.showErrorToast) {
                                    window.showErrorToast('Pagamento rejeitado ou cancelado.');
                                } else if (window.Notiflix) {
                                    window.Notiflix.Notify.failure('❌ Pagamento rejeitado ou cancelado.');
                                }
                                
                                // Fecha o modal após mostrar o erro
                                setTimeout(() => {
                                    closeModal();
                                }, 2000);
                            }
                        } else {
                            console.warn('Resposta do status não tem transaction:', data);
                        }
                    } else {
                        console.error('Erro ao verificar status:', response.status, response.statusText);
                    }
                } catch (error) {
                    console.error('Erro ao verificar status:', error);
                }
            }, 5000); // Verifica a cada 5 segundos
        };

        const copyPix = () => {
            navigator.clipboard.writeText(pixCode.value).then(() => {
                    if (window.showSuccessToast) {
                        window.showSuccessToast('Copiado!');
                    } else if (window.Notiflix) {
                        window.Notiflix.Notify.success('🎄 Copiado!');
                    }
            });
        };

        const closeModal = () => {
            // Para a verificação de status se estiver ativa
            if (statusCheckInterval.value) {
                clearInterval(statusCheckInterval.value);
                statusCheckInterval.value = null;
            }
            
            showQrCode.value = false;
            amount.value = '';
            transactionId.value = null;
            emit('close');
        };

        return {
            amount,
            loading,
            showQrCode,
            qrCodeImage,
            pixCode,
            quickAmounts,
            formatAmount,
            formatAmountValue,
            selectAmount,
            generatePayment,
            copyPix,
            closeModal,
        };
    },
};
</script>

<style scoped>
.deposit-header {
    text-align: center;
    margin-bottom: 1rem;
}

.deposit-icon {
    font-size: 2rem;
    margin-bottom: 0;
    animation: bounce 2s ease-in-out infinite;
}

.amount-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.currency-symbol {
    position: absolute;
    left: 12px;
    color: var(--cor-texto-secundaria);
    font-weight: bold;
    font-size: 1rem;
    z-index: 1;
}

.amount-input {
    padding-left: 45px !important;
    font-size: 1.1rem !important;
    font-weight: bold !important;
    text-align: left !important;
    padding-top: 0.6rem !important;
    padding-bottom: 0.6rem !important;
}

.quick-amounts {
    margin: 1rem 0;
}

.quick-amounts-label {
    color: var(--cor-texto-secundaria);
    font-size: 0.8rem;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.amount-buttons {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
}

.amount-btn {
    background: var(--cor-fundo-input);
    border: 2px solid #444;
    border-radius: 6px;
    padding: 0.5rem 0.4rem;
    color: var(--cor-texto);
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.amount-btn:hover {
    background: var(--cor-fundo-painel);
    border-color: var(--cor-principal);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.amount-btn.active {
    background: linear-gradient(135deg, var(--cor-principal), var(--cor-principal-dark));
    border-color: var(--cor-principal);
    color: white;
    box-shadow: 0 4px 16px rgba(239, 68, 68, 0.4);
}

.deposit-info {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 6px;
    padding: 0.6rem;
    margin: 1rem 0;
    display: flex;
    justify-content: space-around;
    gap: 0.5rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    color: var(--cor-texto);
    font-size: 0.75rem;
    margin: 0;
}

.info-icon {
    font-size: 1rem;
}

.deposit-button {
    margin-top: 0.75rem;
    font-size: 1rem !important;
    padding: 0.75rem !important;
    font-weight: 700 !important;
    background: linear-gradient(135deg, #16a34a, #15803d) !important;
}

.deposit-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.qr-code-display {
    text-align: center;
}

.qr-header {
    margin-bottom: 0.75rem;
}

.qr-icon {
    font-size: 1.5rem;
    margin-bottom: 0.2rem;
}

.qr-header h4 {
    margin: 0.2rem 0;
    color: var(--cor-texto);
    font-size: 0.9rem;
}

.qr-header p {
    margin: 0;
    color: var(--cor-texto-secundaria);
    font-size: 0.75rem;
}

.qr-code-wrapper {
    background: white;
    padding: 0.5rem;
    border-radius: 8px;
    display: inline-block;
    margin: 0.75rem 0;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
}

.qr-code-wrapper img {
    max-width: 180px;
    width: 100%;
    height: auto;
    display: block;
}

.pix-code-wrapper {
    margin: 0.5rem 0;
}

.pix-code {
    background: var(--cor-fundo-input);
    border: 1px solid #444;
    border-radius: 6px;
    padding: 0.4rem 0.5rem;
    word-break: break-all;
    color: var(--cor-texto);
    font-size: 0.65rem;
    margin-bottom: 0.4rem;
    text-align: left;
    font-family: monospace;
    line-height: 1.3;
}

.copy-pix-btn {
    width: 100%;
    background: linear-gradient(135deg, #16a34a, #15803d);
    color: white;
    border: none;
    padding: 0.7rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    transition: all 0.3s ease;
}

.copy-pix-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(22, 163, 74, 0.4);
}

.copy-pix-btn span {
    font-size: 1.2rem;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
</style>

