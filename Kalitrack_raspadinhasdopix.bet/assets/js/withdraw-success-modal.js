class WithdrawSuccessModal {
    constructor() {
        this.modal = null;
        this.isShown = false;
        this.withdrawAmount = 0;
        this.withdrawMethod = 'PIX';
        this.transactionId = '';
        this.estimatedTime = '24-48 horas';
    }

    createModalHTML() {
        if (!this.transactionId) {
            this.transactionId = 'TXN' + Date.now().toString().slice(-8);
        }

        const currentDate = new Date();
        const formattedDate = currentDate.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
        const formattedTime = currentDate.toLocaleTimeString('pt-BR', {
            hour: '2-digit',
            minute: '2-digit'
        });

        return `
            <div class="withdraw-success-modal-overlay" id="withdrawSuccessModalOverlay">
                <div class="withdraw-success-modal">
                    <button class="withdraw-success-modal-close" onclick="withdrawSuccessModal.close()">×</button>
                    
                    <div class="withdraw-success-modal-header">
                        <div class="withdraw-success-check-icon">✓</div>
                        <h2 class="withdraw-success-title">Solicitação de Saque Concluída!</h2>
                        <p class="withdraw-success-subtitle">Sua solicitação foi processada com sucesso</p>
                    </div>

                    <div class="withdraw-success-modal-content">
                        <div class="withdraw-info-section">
                            <div class="withdraw-amount-card">
                                <div class="withdraw-amount-label">Valor Solicitado</div>
                                <div class="withdraw-amount-value">R$ ${this.withdrawAmount.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</div>
                                <div class="withdraw-method">via ${this.withdrawMethod}</div>
                            </div>

                            <div class="withdraw-details-card">
                                <div class="withdraw-detail-row">
                                    <span class="withdraw-detail-label">
                                        <span class="withdraw-detail-icon">🆔</span>
                                        ID da Transação
                                    </span>
                                    <span class="withdraw-detail-value">${this.transactionId}</span>
                                </div>
                                <div class="withdraw-detail-row">
                                    <span class="withdraw-detail-label">
                                        <span class="withdraw-detail-icon">📅</span>
                                        Data da Solicitação
                                    </span>
                                    <span class="withdraw-detail-value">${formattedDate}</span>
                                </div>
                                <div class="withdraw-detail-row">
                                    <span class="withdraw-detail-label">
                                        <span class="withdraw-detail-icon">⏰</span>
                                        Horário
                                    </span>
                                    <span class="withdraw-detail-value">${formattedTime}</span>
                                </div>
                                <div class="withdraw-detail-row">
                                    <span class="withdraw-detail-label">
                                        <span class="withdraw-detail-icon">💳</span>
                                        Método de Pagamento
                                    </span>
                                    <span class="withdraw-detail-value">${this.withdrawMethod}</span>
                                </div>
                            </div>

                            <div class="withdraw-status-card">
                                <span class="withdraw-status-icon">🕐</span>
                                <div class="withdraw-status-title">Em Processamento</div>
                                <div class="withdraw-status-text">
                                    Seu saque será processado em até ${this.estimatedTime}. 
                                    Você receberá uma confirmação por email quando o pagamento for efetuado.
                                </div>
                            </div>
                        </div>

                        <div class="withdraw-actions">
                            <button class="withdraw-action-btn primary" onclick="withdrawSuccessModal.goToHistory()">
                                📊 Ver Histórico de Transações
                            </button>
                            <button class="withdraw-action-btn secondary" onclick="withdrawSuccessModal.close()">
                                🏠 Voltar ao Início
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    show(withdrawAmount = 1100.00, withdrawMethod = 'PIX', transactionId = '', estimatedTime = '24-48 horas') {
        if (this.isShown) return;

        this.withdrawAmount = withdrawAmount;
        this.withdrawMethod = withdrawMethod;
        this.transactionId = transactionId;
        this.estimatedTime = estimatedTime;

        const modalContainer = document.createElement('div');
        modalContainer.innerHTML = this.createModalHTML();
        document.body.appendChild(modalContainer.firstElementChild);

        this.modal = document.getElementById('withdrawSuccessModalOverlay');
        this.isShown = true;

        setTimeout(() => {
            this.modal.classList.add('show');
        }, 100);

        this.setupEventListeners();
    }

    setupEventListeners() {
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });

        document.addEventListener('keydown', this.handleEscKey.bind(this));
    }

    close() {
        if (!this.modal) return;

        this.modal.classList.remove('show');

        setTimeout(() => {
            if (this.modal && this.modal.parentNode) {
                this.modal.parentNode.removeChild(this.modal);
            }
            this.modal = null;
            this.isShown = false;
        }, 300);

        document.removeEventListener('keydown', this.handleEscKey.bind(this));
    }

    handleEscKey(event) {
        if (event.key === 'Escape') {
            this.close();
        }
    }

    goToHistory() {
        window.location.href = '/historico/';
    }

    goToHome() {
        window.location.href = '/';
    }

    updateWithdrawInfo(withdrawAmount, withdrawMethod, transactionId, estimatedTime) {
        this.withdrawAmount = withdrawAmount;
        this.withdrawMethod = withdrawMethod;
        this.transactionId = transactionId;
        this.estimatedTime = estimatedTime;

        if (this.modal) {
            const modalContent = this.createModalHTML();
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = modalContent;

            const newModal = tempDiv.firstElementChild.querySelector('.withdraw-success-modal');
            this.modal.querySelector('.withdraw-success-modal').innerHTML = newModal.innerHTML;
        }
    }

    showConfirmation() {
        if (this.modal) {
            const statusCard = this.modal.querySelector('.withdraw-status-card');
            if (statusCard) {
                statusCard.innerHTML = `
                    <span class="withdraw-status-icon">✅</span>
                    <div class="withdraw-status-title">Pagamento Confirmado!</div>
                    <div class="withdraw-status-text">
                        Seu saque foi processado e o valor já foi enviado para sua conta.
                        Verifique seu extrato bancário.
                    </div>
                `;
                statusCard.style.background = 'linear-gradient(145deg, rgba(46, 204, 113, 0.2) 0%, rgba(39, 174, 96, 0.15) 100%)';
            }
        }
    }
}

const withdrawSuccessModal = new WithdrawSuccessModal();

function showWithdrawSuccessModal(withdrawAmount = 0, withdrawMethod = 'PIX', transactionId = '', estimatedTime = '24-48 horas') {
    withdrawSuccessModal.show(withdrawAmount, withdrawMethod, transactionId, estimatedTime);
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = WithdrawSuccessModal;
}