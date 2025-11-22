import { ref } from 'vue';

/**
 * Composable para autenticação e dados do usuário
 */
export function useUserAuth(internalApiRequest, initializeGame) {
    const isUserLoggedIn = ref(false);
    const username = ref('');
    const userEmail = ref('');
    const userPhone = ref('');
    const balance = ref(0);
    const balanceBonus = ref(0);
    const userBalanceRef = ref(0);
    const userReferralCode = ref('');
    const userCpa = ref(0);
    
    // Verificar autenticação ao carregar
    const checkAuthentication = async () => {
        try {
            const response = await fetch('/api/user');
            const data = await response.json();
            
            if (data.success && data.authenticated && data.user) {
                isUserLoggedIn.value = true;
                username.value = data.user.name || data.user.email;
                userEmail.value = data.user.email || '';
                userPhone.value = data.user.phone || '';
                balance.value = parseFloat(data.user.balance || 0);
                balanceBonus.value = parseFloat(data.user.balance_bonus || 0);
                userBalanceRef.value = parseFloat(data.user.balance_ref || 0);
                userReferralCode.value = data.user.referral_code || '';
                userCpa.value = parseFloat(data.user.cpa || 0);
            } else {
                isUserLoggedIn.value = false;
            }
        } catch (error) {
            console.error('Erro ao verificar autenticação:', error);
            isUserLoggedIn.value = false;
        }
    };
    
    const handleLoggedIn = (data) => {
        if (data.user) {
            isUserLoggedIn.value = true;
            username.value = data.user.name || data.user.email;
            userEmail.value = data.user.email || '';
            userPhone.value = data.user.phone || '';
            balance.value = parseFloat(data.user.balance || 0);
            balanceBonus.value = parseFloat(data.user.balance_bonus || 0);
            userBalanceRef.value = parseFloat(data.user.balance_ref || 0);
            userReferralCode.value = data.user.referral_code || '';
            userCpa.value = parseFloat(data.user.cpa || 0);
        }
        // initializeGame será chamado no wrapper do ClawGame
    };
    
    const handleRegistered = (data, priorityFeeAmount, closeRegisterModal, currentTour) => {
        // Após registro, pode fazer login automaticamente ou mostrar mensagem
        if (data.user) {
            isUserLoggedIn.value = true;
            username.value = data.user.name || data.user.email;
            userEmail.value = data.user.email || '';
            userPhone.value = data.user.phone || '';
            balance.value = parseFloat(data.user.balance || 0);
            balanceBonus.value = parseFloat(data.user.balance_bonus || 0);
            userBalanceRef.value = parseFloat(data.user.balance_ref || 0);
            userReferralCode.value = data.user.referral_code || '';
            userCpa.value = parseFloat(data.user.cpa || 0);
            if (priorityFeeAmount) {
                priorityFeeAmount.value = parseFloat(data.user.priority_fee_amount || 0);
            }
            if (initializeGame) initializeGame();
            
            // Para o tour se estiver ativo
            if (currentTour) {
                try {
                    if (typeof currentTour.isActive === 'function' && currentTour.isActive()) {
                        currentTour.complete();
                    } else if (currentTour.currentStep) {
                        currentTour.complete();
                    }
                    currentTour = null;
                } catch (e) {
                    console.log('Erro ao parar tour:', e);
                }
            }
            
            // Fecha o modal de registro
            if (closeRegisterModal) closeRegisterModal();
            
            // Adiciona parâmetro na URL e redireciona para a página principal
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('registered', 'true');
            window.location.href = currentUrl.toString();
        }
    };
    
    const handleLogout = async () => {
        try {
            const response = await fetch('/logout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (response.ok && data.success) {
                isUserLoggedIn.value = false;
                username.value = '';
                userEmail.value = '';
                userPhone.value = '';
                balance.value = 0;
                balanceBonus.value = 0;
                userBalanceRef.value = 0;
                userReferralCode.value = '';
                userCpa.value = 0;
                
                // Navega para a página do jogo e atualiza a URL
                const route = '/';
                window.history.pushState({ page: 'game' }, '', route);
                
                if (window.showSuccessToast) {
                    window.showSuccessToast('Logout realizado com sucesso!');
                } else if (window.Notiflix) {
                    window.Notiflix.Notify.success('🎄 Logout realizado com sucesso!');
                }
            }
        } catch (error) {
            console.error('Erro no logout:', error);
        }
    };
    
    return {
        isUserLoggedIn,
        username,
        userEmail,
        userPhone,
        balance,
        balanceBonus,
        userBalanceRef,
        userReferralCode,
        userCpa,
        checkAuthentication,
        handleLoggedIn,
        handleRegistered,
        handleLogout,
    };
}

