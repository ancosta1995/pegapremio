class DepositSuccessModal {
   constructor() {
       this.modal = null;
       this.isShown = false;
       this.depositAmount = 0.00;
       this.depositMethod = 'PIX';
       this.transactionId = '';
       this.newBalance = 0.00;
      
       this.boundHandleEscKey = this.handleEscKey.bind(this);
   }
   createModalHTML() {
       if (!this.transactionId) {
           this.transactionId = 'DEP' + Date.now().toString().slice(-8);
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
           <div class="deposit-success-modal-overlay" id="depositSuccessModalOverlay">
               <div class="deposit-success-modal">
                   <button class="deposit-success-modal-close" onclick="depositSuccessModal.close()">×</button>
                  
                   <div class="deposit-success-modal-header">
                       <div class="deposit-success-check-icon"><i data-lucide="check-circle" style="width: 32px; height: 32px;"></i></div>
                       <h2 class="deposit-success-title">Depósito Realizado com Sucesso!</h2>
                       <p class="deposit-success-subtitle">Seu saldo foi já atualizado!</p>
                   </div>
                   <div class="deposit-success-modal-content">
                       <div class="deposit-info-section">
                           <div class="deposit-amount-card">
                               <div class="deposit-amount-label">Novo Saldo</div>
                               <div class="deposit-amount-value">R$ ${this.newBalance.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</div>
                           </div>
                           <div class="deposit-details-card">
                               <div class="deposit-detail-row">
                                   <span class="deposit-detail-label">
                                       <span class="deposit-detail-icon"><i data-lucide="shield-check"></i></span>
                                       ID da Transação
                                   </span>
                                   <span class="deposit-detail-value">${this.transactionId}</span>
                               </div>
                               <div class="deposit-detail-row">
                                   <span class="deposit-detail-label">
                                       <span class="deposit-detail-icon"><i data-lucide="calendar"></i></span>
                                       Data do Depósito
                                   </span>
                                   <span class="deposit-detail-value">${formattedDate}</span>
                               </div>
                               <div class="deposit-detail-row">
                                   <span class="deposit-detail-label">
                                       <span class="deposit-detail-icon"><i data-lucide="clock"></i></span>
                                       Horário
                                   </span>
                                   <span class="deposit-detail-value">${formattedTime}</span>
                               </div>
                           </div>
                           <div class="deposit-status-card">
                               <span class="deposit-status-icon"><i data-lucide="party-popper" style="width: 32px; height: 32px;"></i></span>
                               <div class="deposit-status-title">Pronto para Jogar!</div>
                               <div class="deposit-status-text">
                                   Agora você pode começar a jogar ou realizar saques quando desejar.
                               </div>
                           </div>
                       </div>
                       <div class="deposit-actions">
                           <button class="deposit-action-btn play" onclick="depositSuccessModal.goToPlay()">
                               <i data-lucide="gamepad-2"></i> Começar a Jogar
                           </button>
                       </div>
                   </div>
               </div>
           </div>
       `;
   }
  
   show(depositAmount = 0.00, depositMethod = 'PIX', transactionId = '', newBalance = 0.00) {
       try {
           if (this.isShown) {
               this.updateDepositInfo(depositAmount, depositMethod, transactionId, newBalance);
               return;
           }
           this.depositAmount = depositAmount;
           this.depositMethod = depositMethod;
           this.transactionId = transactionId;
           this.newBalance = newBalance;
           const modalContainer = document.createElement('div');
           modalContainer.innerHTML = this.createModalHTML();
           document.body.appendChild(modalContainer.firstElementChild);
           this.modal = document.getElementById('depositSuccessModalOverlay');
           this.isShown = true;
           setTimeout(() => {
               try {
                   if (window.lucide && typeof window.lucide.createIcons === 'function') {
                       window.lucide.createIcons();
                       console.log('Ícones Lucide inicializados no modal de sucesso');
                   } else {
                       console.warn('Lucide não encontrado ou não carregado');
                   }
               } catch (error) {
                   console.error('Erro ao inicializar ícones Lucide:', error);
               }
           }, 100);
           setTimeout(() => {
               if (this.modal) {
                   this.modal.classList.add('show');
               }
           }, 150);
           this.setupEventListeners();
           if (typeof fetchUserInfo === 'function') {
               setTimeout(() => {
                   fetchUserInfo();
               }, 1000);
           }
       } catch (error) {
           console.error('Erro ao mostrar modal de depósito:', error);
       }
   }

   setupEventListeners() {
       if (!this.modal) return;
       this.modal.addEventListener('click', (event) => {
           if (event.target === this.modal) {
               this.close();
           }
       });
       document.addEventListener('keydown', this.boundHandleEscKey);
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
       document.removeEventListener('keydown', this.boundHandleEscKey);
   }
   handleEscKey(event) {
       if (event.key === 'Escape') {
           this.close();
       }
   }
   goToWithdraw() {
       this.close();
       setTimeout(() => {
           window.location.href = '/saque/';
       }, 300);
   }
   goToPlay() {
       this.close();
       setTimeout(() => {
           window.location.href = '/#raspadinhas';
       }, 300);
   }
   updateDepositInfo(depositAmount, depositMethod, transactionId, newBalance) {
       this.depositAmount = depositAmount;
       this.depositMethod = depositMethod;
       this.transactionId = transactionId;
       this.newBalance = newBalance;
       if (this.modal) {
           try {
               const modalContent = this.createModalHTML();
               const tempDiv = document.createElement('div');
               tempDiv.innerHTML = modalContent;
               const newModal = tempDiv.firstElementChild.querySelector('.deposit-success-modal');
               this.modal.querySelector('.deposit-success-modal').innerHTML = newModal.innerHTML;
              
               setTimeout(() => {
                   if (window.lucide && typeof window.lucide.createIcons === 'function') {
                       window.lucide.createIcons();
                   }
               }, 50);
           } catch (error) {
               console.error('Erro ao atualizar informações do modal:', error);
           }
       }
   }
   highlightAction(action) {
       if (!this.modal) return;
       const withdrawBtn = this.modal.querySelector('.deposit-action-btn.withdraw');
       const playBtn = this.modal.querySelector('.deposit-action-btn.play');
       if (withdrawBtn) withdrawBtn.style.transform = '';
       if (playBtn) playBtn.style.transform = '';
       if (action === 'withdraw' && withdrawBtn) {
           withdrawBtn.style.transform = 'scale(1.05)';
           withdrawBtn.style.boxShadow = '0 12px 32px rgba(231, 76, 60, 0.5)';
       } else if (action === 'play' && playBtn) {
           playBtn.style.transform = 'scale(1.05)';
           playBtn.style.boxShadow = '0 12px 32px rgba(46, 204, 113, 0.5)';
       }
   }
   celebrate() {
       if (this.modal) {
           const modal = this.modal.querySelector('.deposit-success-modal');
           modal.style.animation = 'deposit-success-bounce 1s ease-in-out';
           this.createConfetti();
       }
   }
   createConfetti() {
       const colors = ['#1abc9c', '#2ecc71', '#f39c12', '#e74c3c', '#9b59b6'];
       const modal = this.modal.querySelector('.deposit-success-modal');
       for (let i = 0; i < 20; i++) {
           setTimeout(() => {
               const confetti = document.createElement('div');
               confetti.style.position = 'absolute';
               confetti.style.width = '8px';
               confetti.style.height = '8px';
               confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
               confetti.style.left = Math.random() * 100 + '%';
               confetti.style.top = '0';
               confetti.style.borderRadius = '50%';
               confetti.style.animation = 'confetti-fall 2s linear forwards';
               confetti.style.zIndex = '1';
               modal.appendChild(confetti);
               setTimeout(() => {
                   if (confetti.parentNode) {
                       confetti.parentNode.removeChild(confetti);
                   }
               }, 2000);
           }, i * 100);
       }
   }
}
const depositSuccessModal = new DepositSuccessModal();
function showDepositSuccessModal(depositAmount = 0.00, depositMethod = 'PIX', transactionId = '', newBalance = 0.00) {
   depositSuccessModal.show(depositAmount, depositMethod, transactionId, newBalance);
}
if (typeof module !== 'undefined' && module.exports) {
   module.exports = DepositSuccessModal;
}