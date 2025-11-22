import { ref } from 'vue';

/**
 * Composable para gerenciamento de modais
 */
export function useModals() {
    // Modals
    const showWinModal = ref(false);
    const showPresellWinModal = ref(false);
    const showLossModal = ref(false);
    const showDepositModal = ref(false);
    const showWithdrawModal = ref(false);
    const showWithdrawalFeeModal = ref(false);
    const showWithdrawalFeePaymentModal = ref(false);
    const showWithdrawalQueueModal = ref(false);
    const showLoginModal = ref(false);
    const showRegisterModal = ref(false);
    const showWelcomeModal = ref(false);
    
    // Funções de abertura/fechamento
    const openDepositModal = (isUserLoggedIn, openRegisterModal) => {
        if (!isUserLoggedIn.value) {
            if (openRegisterModal) openRegisterModal();
            return;
        }
        showDepositModal.value = true;
    };
    
    const closeDepositModal = () => {
        showDepositModal.value = false;
    };
    
    const openWithdrawModal = (isUserLoggedIn, openRegisterModal) => {
        if (!isUserLoggedIn.value) {
            if (openRegisterModal) openRegisterModal();
            return;
        }
        showWithdrawModal.value = true;
    };
    
    const closeWithdrawModal = () => {
        showWithdrawModal.value = false;
        closeWithdrawalQueueModal();
    };
    
    const closeWithdrawalFeeModal = () => {
        showWithdrawalFeeModal.value = false;
    };
    
    const closeWithdrawalFeePaymentModal = () => {
        showWithdrawalFeePaymentModal.value = false;
    };
    
    const closeWithdrawalQueueModal = () => {
        showWithdrawalQueueModal.value = false;
    };
    
    const openLoginModal = () => {
        showLoginModal.value = true;
    };
    
    const closeLoginModal = () => {
        showLoginModal.value = false;
    };
    
    const openRegisterModal = () => {
        showRegisterModal.value = true;
    };
    
    const closeRegisterModal = () => {
        showRegisterModal.value = false;
    };
    
    const showRegisterFromLogin = () => {
        closeLoginModal();
        openRegisterModal();
    };
    
    const showLoginFromRegister = () => {
        closeRegisterModal();
        openLoginModal();
    };
    
    const closeWelcomeModal = () => {
        showWelcomeModal.value = false;
    };
    
    const closeWinModal = () => {
        showWinModal.value = false;
    };
    
    const closePresellWinModal = () => {
        showPresellWinModal.value = false;
    };
    
    const closeLossModal = () => {
        showLossModal.value = false;
    };
    
    return {
        // States
        showWinModal,
        showPresellWinModal,
        showLossModal,
        showDepositModal,
        showWithdrawModal,
        showWithdrawalFeeModal,
        showWithdrawalFeePaymentModal,
        showWithdrawalQueueModal,
        showLoginModal,
        showRegisterModal,
        showWelcomeModal,
        // Functions
        openDepositModal,
        closeDepositModal,
        openWithdrawModal,
        closeWithdrawModal,
        closeWithdrawalFeeModal,
        closeWithdrawalFeePaymentModal,
        closeWithdrawalQueueModal,
        openLoginModal,
        closeLoginModal,
        openRegisterModal,
        closeRegisterModal,
        showRegisterFromLogin,
        showLoginFromRegister,
        closeWelcomeModal,
        closeWinModal,
        closePresellWinModal,
        closeLossModal,
    };
}

