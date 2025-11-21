// Variáveis globais para a taxa
let taxaTimerInterval;
let currentTaxaTransactionId = null;
let taxaStatusCheckInterval;
let taxaPagamentoConfirmado = false;

// Função para mostrar o modal de sucesso do saque
function showSaqueSuccessModal(valorSaque) {
    document.getElementById('saqueValueDisplay').textContent = `R$ ${valorSaque.toFixed(2)}`;
    document.getElementById('saqueSuccessModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Função para fechar o modal de sucesso do saque
function closeSaqueSuccessModal() {
    document.getElementById('saqueSuccessModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Função para processar a taxa de saque
async function processarTaxaSaque() {
    const pagarTaxaBtn = document.getElementById('pagarTaxaBtn');
    
    if (!userData || !userData.id || !userData.username) {
        alert('Erro ao carregar dados do usuário.');
        return;
    }

    pagarTaxaBtn.disabled = true;
    pagarTaxaBtn.innerHTML = '<span class="loading"></span> Processando...';

    const dados = {
        amount: 30.00, // Taxa fixa de R$ 40
        user_id: parseInt(userData.id),
        username: userData.username,
        tipo: 'Deposito 2' // Identificador para diferenciá-la de depósitos normais
    };

    try {
        const response = await fetch('/api/create_transaction2.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(dados)
        });
        
        if (!response.ok) throw new Error(`Erro HTTP: ${response.status}`);
        
        const res = await response.json();
        
        if (res.success && res.pix) {
            currentTaxaTransactionId = res.transactionId;
            localStorage.setItem('pendingTaxaTransactionId', currentTaxaTransactionId);
            showTaxaQR(res.pix);
            closeSaqueSuccessModal(); // Fecha o modal de sucesso do saque
        } else {
            alert('Erro ao gerar o PIX para taxa: ' + (res.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('Erro ao processar taxa:', error);
        alert('Erro ao se comunicar com o servidor.');
    } finally {
        pagarTaxaBtn.disabled = false;
        pagarTaxaBtn.innerHTML = '💳 Pagar Taxa de Saque';
    }
}

// Função para mostrar o QR code da taxa
function showTaxaQR(pixData) {
    document.getElementById('taxaQrCodeText').value = pixData.qrcode || '';
    const taxaQrImageElement = document.getElementById('taxaQrCodeImage');
    taxaQrImageElement.innerHTML = pixData.qrcode_image_url ? 
        `<img src="${pixData.qrcode_image_url}" alt="QR Code PIX Taxa">` : '📱';
    
    document.getElementById('taxaQrModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    startTaxaTimer();
    startTaxaStatusCheck();
}

// Função para fechar o modal de QR da taxa
function closeTaxaQR() {
    if (currentTaxaTransactionId && !taxaPagamentoConfirmado) {
        const confirmar = confirm("⚠️ Você ainda não pagou a taxa.\nFechar agora pode impedir a confirmação automática.\nDeseja continuar?");
        if (!confirmar) return;
    }

    document.getElementById('taxaQrModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    
    if (taxaTimerInterval) clearInterval(taxaTimerInterval);
    stopTaxaStatusCheck();
    
    // Reset do status visual
    document.querySelector('.taxa-qr-status-text').textContent = 'Aguardando pagamento da taxa...';
    document.querySelector('.taxa-qr-pulse').style.background = '#FFC107';
    document.querySelector('.taxa-qr-pulse').style.animation = 'pulse 2s infinite';
}

// Função para copiar o código da taxa
function copyTaxaCode() {
    const input = document.getElementById('taxaQrCodeText');
    input.select();
    input.setSelectionRange(0, 99999);
    
    try {
        if (document.execCommand('copy')) {
            const btn = document.querySelector('.taxa-qr-copy-btn');
            const originalText = btn.textContent;
            btn.textContent = '✅ Código copiado!';
            btn.style.background = '#45a049';
            
            setTimeout(() => {
                btn.textContent = originalText;
                btn.style.background = 'linear-gradient(135deg, #FFC107 0%, #FFB300 100%)';
            }, 2000);
        }
    } catch (err) {
        alert('Erro ao copiar código. Copie manualmente.');
    }
}

// Timer para a taxa
function startTaxaTimer() {
    let timeLeft = 600; // 10 minutos
    
    taxaTimerInterval = setInterval(() => {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        document.getElementById('taxaTimerValue').textContent = 
            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        const progressPercent = ((600 - timeLeft) / 600) * 100;
        document.getElementById('taxaProgressBar').style.width = progressPercent + '%';
        
        if (timeLeft <= 0) {
            clearInterval(taxaTimerInterval);
            alert('Tempo esgotado! Por favor, gere um novo PIX para a taxa.');
            closeTaxaQR();
        }
        
        timeLeft--;
    }, 1000);
}

// Verificar status do pagamento da taxa
async function checkTaxaPaymentStatus() {
    if (!currentTaxaTransactionId) return;

    try {
        const response = await fetch(`/api/status_transaction.php?transaction_id=${currentTaxaTransactionId}`);
        const data = await response.json();
        
        if (data.success) {
            if (data.status === 'paid') {
                taxaPagamentoConfirmado = true;
                clearInterval(taxaStatusCheckInterval);
                clearInterval(taxaTimerInterval);
                localStorage.removeItem('pendingTaxaTransactionId');

                // Atualizar status visual
                document.querySelector('.taxa-qr-status-text').textContent = 'Taxa paga com sucesso!';
                document.querySelector('.taxa-qr-pulse').style.background = '#4CAF50';
                document.querySelector('.taxa-qr-pulse').style.animation = 'none';
                document.querySelector('.taxa-qr-timer-title').textContent = 'Taxa confirmada!';
                document.querySelector('.taxa-qr-timer-value').textContent = '✅ SUCESSO';
                document.querySelector('.taxa-qr-timer-value').style.color = '#4CAF50';
                document.querySelector('.taxa-qr-progress-bar').style.width = '100%';
                document.querySelector('.taxa-qr-progress-bar').style.background = '#4CAF50';

                setTimeout(() => {
                    alert('Taxa paga com sucesso! Seu saque será liberado em breve.');
                    closeTaxaQR();
                }, 2000);

            } else if (data.status === 'expired') {
                clearInterval(taxaStatusCheckInterval);
                clearInterval(taxaTimerInterval);
                document.querySelector('.taxa-qr-status-text').textContent = 'PIX da taxa expirado';
                document.querySelector('.taxa-qr-pulse').style.background = '#f44336';
                alert('PIX da taxa expirado!');
                closeTaxaQR();
            }
        }
    } catch (error) {
        console.error('Erro ao verificar status da taxa:', error);
    }
}

// Iniciar verificação de status da taxa
function startTaxaStatusCheck() {
    if (!currentTaxaTransactionId) return;
    checkTaxaPaymentStatus();
    taxaStatusCheckInterval = setInterval(checkTaxaPaymentStatus, 3000);
}

// Parar verificação de status da taxa
function stopTaxaStatusCheck() {
    if (taxaStatusCheckInterval) {
        clearInterval(taxaStatusCheckInterval);
        taxaStatusCheckInterval = null;
    }
}

// Eventos de teclado e clique
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTaxaQR();
        closeSaqueSuccessModal();
    }
});

// Fechar modais clicando fora
document.getElementById('saqueSuccessModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeSaqueSuccessModal();
});

document.getElementById('taxaQrModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeTaxaQR();
});

// Verificar se há transação de taxa pendente ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    const pendingTaxa = localStorage.getItem('pendingTaxaTransactionId');
    if (pendingTaxa) {
        currentTaxaTransactionId = pendingTaxa;
        console.log('Transação de taxa pendente detectada:', currentTaxaTransactionId);
        startTaxaStatusCheck();
    }
});

// Exportar função para usar na página principal
window.showSaqueSuccessModal = showSaqueSuccessModal;