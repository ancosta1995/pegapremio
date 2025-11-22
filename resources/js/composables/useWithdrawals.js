import { ref, nextTick } from 'vue';

/**
 * Composable para gerenciamento de saques e taxas
 */
export function useWithdrawals(internalApiRequest, modals) {
    const currentWithdrawalId = ref(null);
    const currentFeeAmount = ref(0);
    const isPriorityFee = ref(false);
    const currentQueuePosition = ref(0);
    const canPayPriority = ref(false);
    const priorityFeeAmount = ref(0);
    const priorityFeePaid = ref(false);
    const pendingFeesCount = ref(0);
    
    const handleFeeRequired = (data) => {
        modals.closeWithdrawModal();
        currentWithdrawalId.value = data.withdrawal_id;
        currentFeeAmount.value = data.fee_amount;
        isPriorityFee.value = false; // Primeira taxa (validação)
        // Abre modal imediatamente (o loading já foi feito no WithdrawModal)
        modals.showWithdrawalFeeModal.value = true;
    };
    
    const openFeePaymentModal = () => {
        console.log('openFeePaymentModal chamado', {
            withdrawalId: currentWithdrawalId.value,
            feeAmount: currentFeeAmount.value,
            isPriorityFee: isPriorityFee.value,
        });
        
        // Verifica se os valores estão presentes
        if (!currentWithdrawalId.value || !currentFeeAmount.value || currentFeeAmount.value <= 0) {
            console.error('Erro: Valores inválidos ao abrir modal de pagamento', {
                withdrawalId: currentWithdrawalId.value,
                feeAmount: currentFeeAmount.value,
            });
            if (window.showErrorToast) {
                window.showErrorToast('Erro: Dados do pagamento inválidos. Tente novamente.');
            }
            return;
        }
        
        // Preserva os valores antes de fechar o modal
        const withdrawalId = currentWithdrawalId.value;
        const feeAmount = currentFeeAmount.value;
        const isPriority = isPriorityFee.value;
        
        // Restaura os valores imediatamente
        currentWithdrawalId.value = withdrawalId;
        currentFeeAmount.value = feeAmount;
        isPriorityFee.value = isPriority;
        
        // Fecha o modal de taxa
        modals.showWithdrawalFeeModal.value = false;
        
        // Usa nextTick para garantir que o DOM foi atualizado antes de abrir o modal
        nextTick(() => {
            // Abre o modal de pagamento
            modals.showWithdrawalFeePaymentModal.value = true;
            
            console.log('Modal de pagamento aberto com valores:', {
                withdrawalId: currentWithdrawalId.value,
                feeAmount: currentFeeAmount.value,
                isPriorityFee: isPriorityFee.value,
            });
        });
    };
    
    const handleFeePaid = async (queuePosition) => {
        modals.closeWithdrawalFeePaymentModal();
        modals.closeWithdrawalFeeModal();
        
        // Busca informações do saque para verificar se pode pagar prioridade
        try {
            const response = await internalApiRequest(`/api/withdrawals/${currentWithdrawalId.value}/info`);
            if (response.success) {
                currentQueuePosition.value = queuePosition || response.withdrawal.queue_position || 0;
                priorityFeePaid.value = response.withdrawal.priority_fee_paid || false;
                
                // SEMPRE busca o valor da taxa do sistema ANTES de abrir o modal
                try {
                    const userResponse = await internalApiRequest('/api/user');
                    console.log('Resposta /api/user:', userResponse);
                    if (userResponse.success && userResponse.user) {
                        const systemFee = parseFloat(userResponse.user.priority_fee_amount || 0);
                        priorityFeeAmount.value = systemFee;
                        canPayPriority.value = systemFee > 0 && !priorityFeePaid.value;
                        console.log('Taxa de prioridade carregada do sistema:', systemFee);
                    } else {
                        // Se não conseguir buscar, usa o valor do withdrawal
                        const withdrawalFee = parseFloat(response.withdrawal.priority_fee_amount || 0);
                        priorityFeeAmount.value = withdrawalFee;
                        canPayPriority.value = withdrawalFee > 0 && !priorityFeePaid.value;
                        console.log('Taxa de prioridade do withdrawal:', withdrawalFee);
                    }
                } catch (e) {
                    console.error('Erro ao buscar taxa de prioridade:', e);
                    // Em caso de erro, usa o valor do withdrawal
                    const withdrawalFee = parseFloat(response.withdrawal.priority_fee_amount || 0);
                    priorityFeeAmount.value = withdrawalFee;
                    canPayPriority.value = withdrawalFee > 0 && !priorityFeePaid.value;
                }
                
                console.log('Modal de fila - valores FINAIS:', {
                    queuePosition: currentQueuePosition.value,
                    canPayPriority: canPayPriority.value,
                    priorityFeeAmount: priorityFeeAmount.value,
                    priorityFeePaid: priorityFeePaid.value,
                });
                
                // Mostra modal de fila
                modals.showWithdrawalQueueModal.value = true;
            }
        } catch (error) {
            console.error('Erro ao buscar informações do saque:', error);
            // Mesmo assim mostra o modal de fila
            currentQueuePosition.value = queuePosition || 0;
            priorityFeePaid.value = false;
            // Tenta buscar o valor da taxa de prioridade do sistema
            try {
                const userResponse = await internalApiRequest('/api/user');
                if (userResponse.success && userResponse.user) {
                    priorityFeeAmount.value = userResponse.user.priority_fee_amount || 0;
                    canPayPriority.value = priorityFeeAmount.value > 0;
                }
            } catch (e) {
                console.error('Erro ao buscar taxa de prioridade:', e);
            }
            modals.showWithdrawalQueueModal.value = true;
        }
    };
    
    const handlePriorityFeePaid = async () => {
        modals.closeWithdrawalFeePaymentModal();
        modals.closeWithdrawalFeeModal();
        
        // Atualiza o status da prioridade no modal de fila
        priorityFeePaid.value = true;
        
        // Se o modal de fila estiver aberto, atualiza as informações
        if (modals.showWithdrawalQueueModal.value) {
            try {
                const response = await internalApiRequest(`/api/withdrawals/${currentWithdrawalId.value}/info`);
                if (response.success) {
                    priorityFeePaid.value = response.withdrawal.priority_fee_paid || false;
                }
            } catch (error) {
                console.error('Erro ao atualizar informações do saque:', error);
            }
        } else {
            // Se não estiver aberto, abre o modal de fila atualizado
            try {
                const response = await internalApiRequest(`/api/withdrawals/${currentWithdrawalId.value}/info`);
                if (response.success) {
                    currentQueuePosition.value = response.withdrawal.queue_position || 0;
                    priorityFeePaid.value = response.withdrawal.priority_fee_paid || false;
                    modals.showWithdrawalQueueModal.value = true;
                }
            } catch (error) {
                console.error('Erro ao buscar informações do saque:', error);
            }
        }
        
        if (window.showSuccessToast) {
            window.showSuccessToast('Taxa de prioridade paga com sucesso! Previsão atualizada para 24 horas.');
        }
    };
    
    const openPriorityFeePayment = async () => {
        console.log('openPriorityFeePayment chamado', {
            currentWithdrawalId: currentWithdrawalId.value,
            priorityFeeAmount: priorityFeeAmount.value,
            tipo: typeof priorityFeeAmount.value,
        });
        
        // Verifica se tem withdrawalId
        if (!currentWithdrawalId.value) {
            console.error('Erro: currentWithdrawalId não está definido');
            if (window.showErrorToast) {
                window.showErrorToast('Erro: ID do saque não encontrado. Tente novamente.');
            }
            return;
        }
        
        // SEMPRE busca o valor do sistema para garantir que está atualizado
        let feeAmount = 0;
        try {
            const userResponse = await internalApiRequest('/api/user');
            console.log('Resposta completa /api/user:', userResponse);
            if (userResponse.success && userResponse.user) {
                feeAmount = parseFloat(userResponse.user.priority_fee_amount || 0);
                console.log('Valor da taxa buscado do sistema (parseFloat):', feeAmount);
                console.log('Valor original (string):', userResponse.user.priority_fee_amount);
                console.log('Tipo do valor original:', typeof userResponse.user.priority_fee_amount);
                
                // Se parseFloat retornar NaN, tenta converter de outra forma
                if (isNaN(feeAmount)) {
                    feeAmount = Number(userResponse.user.priority_fee_amount) || 0;
                    console.log('Tentativa 2 (Number):', feeAmount);
                }
                
                priorityFeeAmount.value = feeAmount;
            }
        } catch (e) {
            console.error('Erro ao buscar taxa de prioridade:', e);
            // Se falhar, usa o valor que já está em priorityFeeAmount
            feeAmount = parseFloat(priorityFeeAmount.value || 0);
        }
        
        // Se ainda estiver 0, tenta usar o valor que já estava
        if (feeAmount <= 0) {
            feeAmount = parseFloat(priorityFeeAmount.value || 0);
            console.log('Usando valor que já estava:', feeAmount);
        }
        
        // Verifica se tem valor válido
        if (!feeAmount || feeAmount <= 0 || isNaN(feeAmount)) {
            console.error('Erro: Taxa de prioridade inválida', {
                feeAmount: feeAmount,
                priorityFeeAmount: priorityFeeAmount.value,
                isNaN: isNaN(feeAmount),
            });
            if (window.showErrorToast) {
                window.showErrorToast('Erro: Taxa de prioridade não configurada ou inválida. Verifique no painel admin.');
            }
            return;
        }
        
        // Preserva o withdrawalId antes de fechar o modal
        const withdrawalId = currentWithdrawalId.value;
        
        modals.closeWithdrawalQueueModal();
        
        // Restaura os valores
        currentWithdrawalId.value = withdrawalId;
        isPriorityFee.value = true;
        currentFeeAmount.value = feeAmount;
        priorityFeeAmount.value = feeAmount;
        
        console.log('Abrindo modal de prioridade com valores FINAIS:', {
            withdrawalId: currentWithdrawalId.value,
            feeAmount: currentFeeAmount.value,
            priorityFeeAmount: priorityFeeAmount.value,
            isPriorityFee: isPriorityFee.value,
        });
        
        modals.showWithdrawalFeeModal.value = true;
    };
    
    const handlePayPriorityFromHistory = async (withdrawal) => {
        console.log('handlePayPriorityFromHistory chamado:', withdrawal);
        // Define o saque atual
        currentWithdrawalId.value = withdrawal.id;
        isPriorityFee.value = true;
        
        // Busca o valor da taxa do sistema se não tiver
        if (priorityFeeAmount.value <= 0) {
            try {
                const userResponse = await internalApiRequest('/api/user');
                if (userResponse.success && userResponse.user) {
                    priorityFeeAmount.value = parseFloat(userResponse.user.priority_fee_amount || 0);
                    console.log('Taxa de prioridade carregada:', priorityFeeAmount.value);
                }
            } catch (e) {
                console.error('Erro ao buscar taxa:', e);
            }
        }
        
        // Usa o valor do withdrawal se o do sistema for 0
        if (priorityFeeAmount.value <= 0) {
            priorityFeeAmount.value = parseFloat(withdrawal.priority_fee_amount || 0);
        }
        
        currentFeeAmount.value = priorityFeeAmount.value;
        console.log('Abrindo modal de prioridade com valor:', currentFeeAmount.value);
        // Abre modal de explicação primeiro
        modals.showWithdrawalFeeModal.value = true;
    };
    
    const handleReopenFeePayment = async (withdrawal) => {
        console.log('handleReopenFeePayment chamado:', withdrawal);
        // SEMPRE abre o modal explicativo primeiro
        currentWithdrawalId.value = withdrawal.id;
        isPriorityFee.value = false;
        
        // Busca o valor da taxa de validação
        try {
            const response = await internalApiRequest(`/api/withdrawals/${withdrawal.id}/info`);
            console.log('Resposta do saque:', response);
            if (response.success) {
                currentFeeAmount.value = response.withdrawal.fee_amount || 50.00;
            } else {
                currentFeeAmount.value = 50.00;
            }
        } catch (error) {
            console.error('Erro ao buscar informações do saque:', error);
            // Usa valor padrão
            currentFeeAmount.value = 50.00;
        }
        
        // SEMPRE abre o modal explicativo primeiro
        console.log('Abrindo modal de explicação primeiro');
        modals.showWithdrawalFeeModal.value = true;
    };
    
    const handlePendingFeesCount = (count) => {
        pendingFeesCount.value = count;
    };
    
    /**
     * Carrega o contador de taxas pendentes diretamente da API
     * Pode ser chamado na inicialização para atualizar o badge do menu
     */
    const loadPendingFeesCount = async (priorityFeeAmount = 0) => {
        try {
            const response = await internalApiRequest('/api/withdrawals');
            if (response.success && response.withdrawals) {
                let count = 0;
                response.withdrawals.forEach(withdrawal => {
                    // Taxa de validação pendente
                    if (!withdrawal.fee_paid && withdrawal.status === 'pending_fee') {
                        count++;
                    }
                    // Taxa de prioridade pendente (primeira taxa paga mas prioridade não)
                    else if (withdrawal.fee_paid && !withdrawal.priority_fee_paid && 
                             withdrawal.status === 'pending' && 
                             (priorityFeeAmount > 0 || withdrawal.priority_fee_amount > 0)) {
                        count++;
                    }
                });
                pendingFeesCount.value = count;
                return count;
            }
        } catch (error) {
            console.error('Erro ao carregar contador de taxas pendentes:', error);
        }
        return 0;
    };
    
    return {
        currentWithdrawalId,
        currentFeeAmount,
        isPriorityFee,
        currentQueuePosition,
        canPayPriority,
        priorityFeeAmount,
        priorityFeePaid,
        pendingFeesCount,
        handleFeeRequired,
        openFeePaymentModal,
        handleFeePaid,
        handlePriorityFeePaid,
        openPriorityFeePayment,
        handlePayPriorityFromHistory,
        handleReopenFeePayment,
        handlePendingFeesCount,
        loadPendingFeesCount,
    };
}

