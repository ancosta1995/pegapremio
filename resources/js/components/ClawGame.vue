<template>
    <div>
        <!-- Confetti Container -->
        <div id="confetti-container" ref="confettiContainer"></div>

        <!-- Audio Elements -->
        <!-- <audio ref="backgroundMusic" :src="asset('assets/sounds/panto-clowns-jingle-271283.mp3')" loop></audio> -->
        <audio ref="winSound" :src="asset('assets/sounds/win.wav')"></audio>
        <audio ref="lossSound" :src="asset('assets/sounds/loss.wav')"></audio>

        <!-- Header -->
        <div class="game-header">
            <div class="header-left">
                <img :src="asset('assets/logo-1.png')" alt="Logo" style="height: 35px;">
            </div>
            <div class="header-right">
                <template v-if="isPresellMode">
                    <a href="#" @click.prevent="openRegisterModal" class="header-profile" id="presell-balance" style="cursor: pointer;">
                        <span class="balance" v-if="!presellLoading && presellBetAmount !== null">
                            R$ {{ formatBalance(presellFakeBalance) }}
                        </span>
                        <span class="balance" v-else style="opacity: 0.7; font-size: 14px;">
                            Carregando...
                        </span>
                    </a>
                    <button id="presell-deposit-btn" class="header-btn deposit-btn" style="cursor: default;">
                        Depositar
                    </button>
                    <button @click="openRegisterModal" class="header-btn register-btn" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                        Criar Conta e Jogar
                    </button>
                </template>
                <template v-else-if="!isUserLoggedIn">
                    <button @click="openLoginModal" class="header-btn login-btn">Entrar</button>
                    <button @click="openRegisterModal" class="header-btn register-btn">Registrar</button>
                </template>
                <template v-else>
                    <a href="#" @click.prevent="navigateTo('wallet')" class="header-profile">
                        <span class="balance">R$ {{ formatBalance(balance) }}</span>
                    </a>
                    <button @click="openDepositModal" class="header-btn deposit-btn">Depositar</button>
                </template>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Game View -->
            <template v-if="currentPage === 'game'">
            <div class="game-scene">
                <div class="scene-top">
                    <img :src="asset('assets/border12.png')" class="background-trees" alt="Fundo de Natal">
                    <img :src="asset('assets/papainelupdate.png')" class="santa-character" alt="Papai Noel">
                </div>
                <div
                    id="game-area"
                    ref="gameArea"
                    :style="{ backgroundImage: `url(${asset('assets/moldurared.png')})` }"
                >
                    <div id="claw-pivot" ref="clawPivot" :style="{ transform: `rotate(${clawRotation}deg)` }">
                        <div
                            id="claw-rope"
                            ref="clawRope"
                            :class="{ stretching: isRopeStretching }"
                            :style="{ backgroundImage: `url(${asset('assets/rop2.png')})` }"
                        ></div>
                        <div id="claw-hook">
                            <img v-if="hasHookLeft" :src="asset('assets/hook_left-sheet0.png')" alt="">
                            <img :src="asset('assets/hook_right-sheet0.png')" alt="">
                        </div>
                    </div>
                    <img
                        v-for="(item, index) in items"
                        :key="index"
                        :ref="el => { if (el) itemElements[index] = el }"
                        :src="item.src"
                        class="game-item"
                        :style="{
                            transform: `translate3d(${item.x}px, ${item.y}px, 0)`,
                            opacity: item.exploding ? 0 : 1
                        }"
                    />
                </div>
            </div>

            <!-- Control Panel -->
            <div class="control-panel">
                <div class="tabs">
                    <button
                        :class="['tab-btn', { active: activeTab === 'bet' }]"
                        @click="activeTab = 'bet'"
                    >
                        Apostar
                    </button>
                    <button
                        v-if="!isPresellMode"
                        :class="['tab-btn', { active: activeTab === 'history' }]"
                        @click="loadHistory"
                    >
                        Jogadas
                    </button>
                </div>

                <div v-if="activeTab === 'bet'" id="bet-panel">
                    <!-- Destaque de rodada grátis em modo presell -->
                    <div v-if="isPresellMode" class="presell-badge" style="
                        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
                        color: white;
                        padding: 12px 20px;
                        border-radius: 12px;
                        text-align: center;
                        margin-bottom: 15px;
                        font-weight: 700;
                        font-size: 16px;
                        box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
                        animation: pulse 2s infinite;
                    ">
                        🎁 {{ presellFreeRounds - presellRoundsPlayed }} RODADAS GRÁTIS RESTANTES 🎁
                    </div>
                    <div class="bet-controls">
                        <div id="bet-amount-display" :style="isPresellMode ? 'opacity: 0.7;' : ''">
                            <template v-if="isPresellMode && (presellLoading || presellBetAmount === null)">
                                Carregando...
                            </template>
                            <template v-else>
                                R$ {{ formatBet(isPresellMode ? presellBetAmount : betLevels[currentBetIndex]) }}
                            </template>
                        </div>
                        <button
                            v-if="!isPresellMode"
                            id="increase-bet"
                            class="bet-adjust-btn plus"
                            @click="increaseBet"
                            :disabled="isPlaying || currentBetIndex >= betLevels.length - 1"
                        >
                            +
                        </button>
                        <button
                            v-if="!isPresellMode"
                            id="decrease-bet"
                            class="bet-adjust-btn minus"
                            @click="decreaseBet"
                            :disabled="isPlaying || currentBetIndex === 0"
                        >
                            -
                        </button>
                    </div>
                    <button
                        id="play-button"
                        @click="handlePlayButtonClick"
                        :disabled="isPlaying || (isPresellMode && (presellLoading || presellBetAmount === null))"
                    >
                        {{ (isPresellMode && (presellLoading || presellBetAmount === null)) ? 'Carregando...' : playButtonText }}
                    </button>
                </div>

                <div v-else id="play-history-panel">
                    <div v-if="historyLoading">Carregando...</div>
                    <div v-else-if="playHistory.length === 0">
                        <p>Sem histórico.</p>
                    </div>
                    <table v-else class="commission-history-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th style="text-align: right;">Aposta</th>
                                <th style="text-align: right;">Ganho</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(play, index) in playHistory" :key="play.id || index">
                                <td>{{ formatTime(play.created_at) }}</td>
                                <td style="text-align:right">R$ {{ formatBet(play.bet_amount) }}</td>
                                <td
                                    style="text-align:right"
                                    :style="{ color: play.is_win ? '#22c55e' : '#aaa' }"
                                >
                                    {{ play.is_win ? `R$ ${formatBet(play.win_amount)} (${play.multiplier}x)` : '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            </template>

            <!-- Profile Page -->
            <ProfilePage
                v-if="currentPage === 'profile'"
                :balance="balance"
                :name="username"
                :email="userEmail"
                :phone="userPhone"
                @withdraw="openWithdrawModal"
                @logout="handleLogout"
            />

            <!-- Wallet Page -->
            <WalletPage
                v-if="currentPage === 'wallet'"
                :balance="balance"
                :balance-bonus="balanceBonus"
                :balance-ref="userBalanceRef"
                :priority-fee-amount="priorityFeeAmount"
                @deposit="openDepositModal"
                @withdraw="openWithdrawModal"
                @pay-priority="handlePayPriorityFromHistory"
                @reopen-fee-payment="handleReopenFeePayment"
                @pending-fees-count="handlePendingFeesCount"
            />

            <!-- Affiliate Page -->
            <AffiliatePage
                v-if="currentPage === 'affiliate'"
                :balance-ref="userBalanceRef"
                :referral-code="userReferralCode"
                :cpa="userCpa"
            />
        </div>

        <!-- Bottom Navigation -->
        <div class="bottom-nav">
            <button
                class="nav-btn"
                :class="{ active: currentPage === 'game' }"
                @click="navigateTo('game')"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 11l-3.1 3.1a2.2 2.2 0 0 1-3.1 0l-1.6-1.6a2.2 2.2 0 0 1 0-3.1L9 7m5 4l1.6-1.6a2.2 2.2 0 0 0 0-3.1l-1.6-1.6a2.2 2.2 0 0 0-3.1 0L7 9.8m7-3.3l-2.5 2.5"/>
                    <path d="M15 7.5c.7-1.1 2.4-1.6 4-1s2.6 2.4 2 4-2.4 2.6-4 2-1.6-2.4-2-4"/>
                    <path d="M7.5 16c-1.1.7-1.6 2.4-1 4s2.4 2.6 4 2 2.6-2.4 2-4-2.4-2.6-4-2"/>
                </svg>
                <span>Pegar</span>
            </button>
            <button
                class="nav-btn nav-btn-with-badge"
                :class="{ active: currentPage === 'wallet' }"
                @click="navigateTo('wallet')"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M21 4H3C1.9 4 1 4.9 1 6v12c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-1 14H4c-.55 0-1-.45-1-1V7h18v10c0 .55-.45 1-1 1zm-1-8H5v2h14v-2zM4 6h16v1H4z"/>
                </svg>
                <span>Carteira</span>
                <span v-if="pendingFeesCount > 0" class="nav-badge">{{ pendingFeesCount > 9 ? '9+' : pendingFeesCount }}</span>
            </button>
            <button
                class="nav-btn"
                :class="{ active: currentPage === 'affiliate' }"
                @click="navigateTo('affiliate')"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                </svg>
                <span>Afiliados</span>
            </button>
            <button
                class="nav-btn"
                :class="{ active: currentPage === 'profile' }"
                @click="navigateTo('profile')"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 4c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm0 14c-2.03 0-4.43-.82-6.14-2.88a9.947 9.947 0 0 1 12.28 0C16.43 19.18 14.03 20 12 20z"/>
                </svg>
                <span>Perfil</span>
            </button>
        </div>

        <!-- Modals -->
        <WinModal
            v-if="showWinModal"
            :win-amount="winAmount"
            :multiplier="winMultiplier"
            @close="closeWinModal"
        />
        <PresellWinModal
            v-if="showPresellWinModal"
            :win-amount="winAmount"
            :multiplier="winMultiplier"
            @close="closePresellWinModal"
            @register="handlePresellRegister"
        />

        <LossModal
            v-if="showLossModal"
            @close="closeLossModal"
        />

        <DepositModal
            v-if="showDepositModal"
            @close="closeDepositModal"
        />

        <WithdrawModal
            v-if="showWithdrawModal"
            @close="closeWithdrawModal"
            @fee-required="handleFeeRequired"
        />
        <WithdrawalFeeModal
            v-if="showWithdrawalFeeModal"
            :withdrawal-id="currentWithdrawalId"
            :fee-amount="currentFeeAmount"
            :is-priority-fee="isPriorityFee"
            @close="closeWithdrawalFeeModal"
            @pay-fee="openFeePaymentModal"
        />
        <WithdrawalQueueModal
            v-if="showWithdrawalQueueModal"
            :queue-position="currentQueuePosition"
            :can-pay-priority="canPayPriority"
            :priority-fee-amount="priorityFeeAmount"
            :priority-fee-paid="priorityFeePaid"
            :withdrawal-id="currentWithdrawalId"
            @close="closeWithdrawalQueueModal"
            @pay-priority="openPriorityFeePayment"
        />
        <WithdrawalFeePaymentModal
            v-if="showWithdrawalFeePaymentModal"
            :withdrawal-id="currentWithdrawalId"
            :fee-amount="currentFeeAmount"
            :is-priority-fee="isPriorityFee"
            @close="closeWithdrawalFeePaymentModal"
            @fee-paid="handleFeePaid"
            @priority-fee-paid="handlePriorityFeePaid"
        />

        <LoginModal
            v-if="showLoginModal"
            @close="closeLoginModal"
            @showRegister="showRegisterFromLogin"
            @loggedIn="handleLoggedIn"
        />

        <RegisterModal
            v-if="showRegisterModal"
            @close="closeRegisterModal"
            @showLogin="showLoginFromRegister"
            @registered="handleRegistered"
        />

        <WelcomeModal
            v-if="showWelcomeModal"
            :free-rounds="presellFreeRounds"
            @close="closeWelcomeModal"
            @play="handleWelcomePlay"
        />
    </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import WinModal from './modals/WinModal.vue';
import PresellWinModal from './modals/PresellWinModal.vue';
import LossModal from './modals/LossModal.vue';
import DepositModal from './modals/DepositModal.vue';
import WithdrawModal from './modals/WithdrawModal.vue';
import WithdrawalFeeModal from './modals/WithdrawalFeeModal.vue';
import WithdrawalFeePaymentModal from './modals/WithdrawalFeePaymentModal.vue';
import WithdrawalQueueModal from './modals/WithdrawalQueueModal.vue';
import LoginModal from './modals/LoginModal.vue';
import RegisterModal from './modals/RegisterModal.vue';
import WelcomeModal from './modals/WelcomeModal.vue';
import ProfilePage from './pages/ProfilePage.vue';
import WalletPage from './pages/WalletPage.vue';
import AffiliatePage from './pages/AffiliatePage.vue';
import { useApi } from '../composables/useApi.js';
import { useNavigation } from '../composables/useNavigation.js';
import { useModals } from '../composables/useModals.js';
import { useUserAuth } from '../composables/useUserAuth.js';
import { useWithdrawals } from '../composables/useWithdrawals.js';
import { usePresell } from '../composables/usePresell.js';
import { useGameLogic } from '../composables/useGameLogic.js';

export default {
    name: 'ClawGame',
    components: {
        WinModal,
        PresellWinModal,
        LossModal,
        DepositModal,
        WithdrawModal,
        WithdrawalFeeModal,
        WithdrawalFeePaymentModal,
        WithdrawalQueueModal,
        LoginModal,
        RegisterModal,
        WelcomeModal,
        ProfilePage,
        WalletPage,
        AffiliatePage,
    },
    setup() {
        // Inicializa composables
        const { internalApiRequest, formatBalance, formatBet, formatTime, asset } = useApi();
        
        // Refs do DOM
        const gameArea = ref(null);
        const clawPivot = ref(null);
        const clawRope = ref(null);
        const confettiContainer = ref(null);
        const backgroundMusic = ref(null);
        const winSound = ref(null);
        const lossSound = ref(null);
        const itemElements = ref([]);
        
        // Inicializa modais
        const modals = useModals();
        const { 
            showWinModal, showPresellWinModal, showLossModal, showDepositModal, showWithdrawModal,
            showWithdrawalFeeModal, showWithdrawalFeePaymentModal, showWithdrawalQueueModal,
            showLoginModal, showRegisterModal, showWelcomeModal
        } = modals;
        
        // Inicializa presell (precisa vir antes de navigation e userAuth)
        const presell = usePresell(internalApiRequest, asset);
        const { isPresellMode, presellBetAmount, presellFreeRounds, presellRoundsPlayed, presellMultipliers, presellLoading, presellFakeBalance, startPresellTour: startPresellTourFromComposable, loadPresellConfig } = presell;
        
        // Inicializa autenticação
        const userAuth = useUserAuth(internalApiRequest);
        const { isUserLoggedIn, username, userEmail, userPhone, balance, balanceBonus, userBalanceRef, userReferralCode, userCpa, checkAuthentication, handleLoggedIn: handleLoggedInAuth, handleRegistered: handleRegisteredAuth, handleLogout } = userAuth;
        
        // Inicializa navegação (precisa do isUserLoggedIn)
        const navigation = useNavigation(isUserLoggedIn);
        const { currentPage, initializeRoute, handlePopState, pageToRoute, getPageFromRoute } = navigation;
        
        // Inicializa saques
        const withdrawals = useWithdrawals(internalApiRequest, modals);
        const { 
            currentWithdrawalId, currentFeeAmount, isPriorityFee, currentQueuePosition, 
            canPayPriority, priorityFeeAmount, priorityFeePaid, pendingFeesCount,
            loadPendingFeesCount
        } = withdrawals;
        
        // Estados do jogo
        const betLevels = ref([0.50, 1.00, 2.00, 5.00, 10.00]);
        const currentBetIndex = ref(0);
        const activeTab = ref('bet');
        const playHistory = ref([]);
        const historyLoading = ref(false);
        const winAmount = ref(0);
        const winMultiplier = ref(0);
        let currentTour = null; // Referência ao tour ativo
        
        const prizeImages = ref([
            asset('assets/prize1.png'),
            asset('assets/prize1.png'),
            asset('assets/prize1.png'),
            asset('assets/prize1.png'),
        ]);
        
        // Helper functions para toasts natalinos (precisam estar antes de useGameLogic)
        const showSuccessToast = (message) => {
            if (window.Notiflix) {
                window.Notiflix.Notify.success(`🎄 ${message}`, {
                    position: 'right-top',
                    timeout: 4000,
                    distance: '20px',
                    borderRadius: '12px',
                    fontFamily: 'Onest, sans-serif',
                });
            }
        };

        const showErrorToast = (message) => {
            if (window.Notiflix) {
                window.Notiflix.Notify.failure(`❄️ ${message}`, {
                    position: 'right-top',
                    timeout: 4000,
                    distance: '20px',
                    borderRadius: '12px',
                    fontFamily: 'Onest, sans-serif',
                });
            }
        };

        const showInfoToast = (message) => {
            if (window.Notiflix) {
                window.Notiflix.Notify.info(`🎁 ${message}`, {
                    position: 'right-top',
                    timeout: 4000,
                    distance: '20px',
                    borderRadius: '12px',
                    fontFamily: 'Onest, sans-serif',
                });
            }
        };

        // Disponibilizar globalmente para outros componentes
        window.showSuccessToast = showSuccessToast;
        window.showErrorToast = showErrorToast;
        window.showInfoToast = showInfoToast;
        
        // Inicializa lógica do jogo
        const gameLogic = useGameLogic(
            gameArea,
            clawPivot,
            confettiContainer,
            winSound,
            lossSound,
            itemElements,
            asset,
            prizeImages,
            currentBetIndex,
            isPresellMode,
            isUserLoggedIn,
            balance,
            betLevels,
            presellBetAmount,
            presellLoading,
            presellRoundsPlayed,
            presellFreeRounds,
            presellMultipliers,
            internalApiRequest,
            showErrorToast,
            modals.openRegisterModal,
            modals,
            winAmount,
            winMultiplier
        );
        const { 
            items, clawRotation, isRopeStretching, isPlaying, playButtonText, hasHookLeft,
            createItems, animateItems, moveClawSway, triggerConfetti, playGame, resetGame,
            increaseBet, decreaseBet, handlePlayButtonClick, cleanup
        } = gameLogic;
        
        // Funções de jogo já estão no composable useGameLogic
        // createItems, animateItems, moveClawSway, triggerConfetti, playGame, resetGame, increaseBet, decreaseBet, handlePlayButtonClick

        const loadHistory = async () => {
            // Em modo presell, não mostra histórico
            if (isPresellMode.value) {
                activeTab.value = 'bet';
                return;
            }
            if (!isUserLoggedIn.value) {
                openRegisterModal();
                return;
            }
            activeTab.value = 'history';
            // Sempre recarrega o histórico para ter os dados mais recentes
            // if (playHistory.value.length > 0) return; // Removido para sempre atualizar
            
            historyLoading.value = true;
            try {
                const res = await internalApiRequest('get_play_history');
                if (res.success && res.history) {
                    playHistory.value = res.history;
                }
            } catch (e) {
                console.error('Erro ao carregar histórico:', e);
            } finally {
                historyLoading.value = false;
            }
        };

        const initializeGame = async () => {
            moveClawSway();
            
            // O botão sempre mostra "PEGAR", mas ao clicar verifica se está logado
            playButtonText.value = 'PEGAR';
            
            // Em modo presell, carrega configuração e multiplicadores
            if (isPresellMode.value) {
                await loadPresellConfig();
                // Inicia tour após carregar configuração
                setTimeout(() => {
                    startPresellTour();
                }, 500);
            }
            
            // Tenta carregar configuração do usuário se estiver logado (sem bloquear)
            if (isUserLoggedIn.value) {
                internalApiRequest('get_claw_config')
                    .then(config => {
                        if (config.success) {
                            balance.value = parseFloat(config.balance || 0);
                            if (config.bet_values && Array.isArray(config.bet_values)) {
                                betLevels.value = config.bet_values;
                            }
                        }
                    })
                    .catch(error => {
                        // Não mostra erro se a rota não existir, apenas usa valores padrão
                        console.log('Configuração do jogo não disponível, usando valores padrão');
                    });
            }
            
            // Sempre cria os itens, independente de estar logado ou não
            // Usa nextTick e setTimeout para garantir que o DOM está renderizado
            await nextTick();
            
            // Aguarda um pouco mais para garantir que o gameArea está totalmente renderizado
            setTimeout(() => {
                if (gameArea.value) {
                    createItems();
                    animateItems();
                } else {
                    // Se ainda não estiver disponível, tenta novamente
                    setTimeout(() => {
                        createItems();
                        animateItems();
                    }, 300);
                }
            }, 300);
        };

        // Funções de navegação, modais e saques já estão nos composables
        // navigateTo, initializeRoute, handlePopState já estão em useNavigation
        // openDepositModal, closeDepositModal, openWithdrawModal, closeWithdrawModal já estão em useModals
        // handleFeeRequired, openFeePaymentModal, handleFeePaid, etc. já estão em useWithdrawals
        
        // Wrappers para manter compatibilidade com o template
        const openFeePaymentModal = () => withdrawals.openFeePaymentModal();
        const closeWithdrawalFeePaymentModal = () => modals.closeWithdrawalFeePaymentModal();
        const closeWithdrawalFeeModal = () => modals.closeWithdrawalFeeModal();
        const closeWithdrawalQueueModal = () => modals.closeWithdrawalQueueModal();
        const openDepositModal = () => modals.openDepositModal(isUserLoggedIn, () => {
            // Para o tour se estiver ativo antes de abrir o modal de registro
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
            modals.openRegisterModal();
        });
        const closeDepositModal = () => modals.closeDepositModal();
        const openWithdrawModal = () => modals.openWithdrawModal(isUserLoggedIn, () => {
            // Para o tour se estiver ativo antes de abrir o modal de registro
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
            modals.openRegisterModal();
        });
        const closeWithdrawModal = () => modals.closeWithdrawModal();
        const openLoginModal = () => modals.openLoginModal();
        const closeLoginModal = () => modals.closeLoginModal();
        const openRegisterModal = () => {
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
            modals.openRegisterModal();
        };
        const closeRegisterModal = () => modals.closeRegisterModal();
        const showRegisterFromLogin = () => modals.showRegisterFromLogin();
        const showLoginFromRegister = () => modals.showLoginFromRegister();
        const navigateTo = (page) => navigation.navigateTo(page, openRegisterModal);
        
        // Funções já estão nos composables - código duplicado removido
        
        // Wrappers para funções dos composables
        const handleFeeRequired = (data) => withdrawals.handleFeeRequired(data);
        const handleFeePaid = async (queuePosition) => {
            await withdrawals.handleFeePaid(queuePosition);
            // Recarregar contador após pagar taxa
            if (isUserLoggedIn.value) {
                try {
                    const userResponse = await internalApiRequest('/api/user');
                    const feeAmount = userResponse.success && userResponse.user 
                        ? parseFloat(userResponse.user.priority_fee_amount || 0) 
                        : 0;
                    await loadPendingFeesCount(feeAmount);
                } catch (error) {
                    console.error('Erro ao recarregar contador:', error);
                }
            }
        };
        const handlePriorityFeePaid = async () => {
            await withdrawals.handlePriorityFeePaid();
            // Recarregar contador após pagar taxa de prioridade
            if (isUserLoggedIn.value) {
                try {
                    const userResponse = await internalApiRequest('/api/user');
                    const feeAmount = userResponse.success && userResponse.user 
                        ? parseFloat(userResponse.user.priority_fee_amount || 0) 
                        : 0;
                    await loadPendingFeesCount(feeAmount);
                } catch (error) {
                    console.error('Erro ao recarregar contador:', error);
                }
            }
        };
        const openPriorityFeePayment = () => withdrawals.openPriorityFeePayment();
        const handlePayPriorityFromHistory = (withdrawal) => withdrawals.handlePayPriorityFromHistory(withdrawal);
        const handleReopenFeePayment = (withdrawal) => withdrawals.handleReopenFeePayment(withdrawal);
        const handlePendingFeesCount = (count) => withdrawals.handlePendingFeesCount(count);
        
        // Todas as funções de saques já estão nos wrappers acima

        // handleLogout, checkAuthentication já estão no composable useUserAuth
        // handleLoggedIn e handleRegistered precisam de lógica adicional
        
        // Wrapper para handleLoggedIn que adiciona initializeGame
        const handleLoggedIn = async (data) => {
            handleLoggedInAuth(data);
            initializeGame();
            // Carregar contador de taxas pendentes após login
            try {
                const userResponse = await internalApiRequest('/api/user');
                const feeAmount = userResponse.success && userResponse.user 
                    ? parseFloat(userResponse.user.priority_fee_amount || 0) 
                    : 0;
                await loadPendingFeesCount(feeAmount);
            } catch (error) {
                console.error('Erro ao carregar contador de taxas pendentes:', error);
                await loadPendingFeesCount(0);
            }
        };
        
        // Wrapper para handleRegistered que passa os parâmetros corretos (o composable já faz tudo)
        const handleRegistered = async (data) => {
            await handleRegisteredAuth(data, priorityFeeAmount, closeRegisterModal, currentTour);
            // Carregar contador de taxas pendentes após registro
            try {
                const userResponse = await internalApiRequest('/api/user');
                const feeAmount = userResponse.success && userResponse.user 
                    ? parseFloat(userResponse.user.priority_fee_amount || 0) 
                    : 0;
                await loadPendingFeesCount(feeAmount);
            } catch (error) {
                console.error('Erro ao carregar contador de taxas pendentes:', error);
                await loadPendingFeesCount(0);
            }
        };

        // Funções do modal de boas-vindas
        const closeWelcomeModal = () => {
            showWelcomeModal.value = false;
        };

        const handleWelcomePlay = () => {
            closeWelcomeModal();
            // Redireciona para a presell
            window.location.href = '/presell';
        };

        // Verifica se é o primeiro acesso e mostra o modal de boas-vindas
        const checkFirstAccess = async () => {
            // Só mostra se não estiver em modo presell e não estiver logado
            if (isPresellMode.value || isUserLoggedIn.value) {
                return;
            }

            // Verifica se já viu o modal antes
            const hasSeenWelcome = localStorage.getItem('welcome_modal_seen');
            
            // Se não viu, carrega a configuração e mostra o modal
            if (!hasSeenWelcome) {
                try {
                    // Carrega a configuração de rodadas grátis do painel admin
                    const config = await internalApiRequest('get_presell_config');
                    if (config.success && config.free_rounds) {
                        presellFreeRounds.value = parseInt(config.free_rounds);
                    }
                } catch (error) {
                    console.log('Erro ao carregar configuração de rodadas grátis:', error);
                    // Mantém o valor padrão (3)
                }
                
                // Aguarda um pouco para garantir que a página carregou completamente
                setTimeout(() => {
                    showWelcomeModal.value = true;
                }, 500);
            }
        };

        const closeWinModal = () => {
            resetGame();
        };

        const closePresellWinModal = () => {
            resetGame();
        };

        const handlePresellRegister = () => {
            closePresellWinModal();
            openRegisterModal();
        };

        // presellFakeBalance já está no composable usePresell
        // Wrapper para startPresellTour que retorna o tour e salva em currentTour
        const startPresellTour = () => {
            const tour = startPresellTourFromComposable(asset);
            if (tour) {
                currentTour = tour;
            }
            return tour;
        };

        // closeWinModal, closePresellWinModal, handlePresellRegister já estão definidas acima
        const closeLossModal = () => {
            resetGame();
        };
        // Funções de toast já estão declaradas acima (linhas 478-502)

        // Função para trackear Content View
        const trackContentView = () => {
            if (window.KwaiEventAPI && typeof window.KwaiEventAPI.trackContentView === 'function') {
                // Passa a página atual para o tracking
                window.KwaiEventAPI.trackContentView(currentPage.value);
            }
        };

        // Watcher para trackear Content View quando a página muda
        watch(currentPage, (newPage, oldPage) => {
            if (newPage !== oldPage) {
                // Aguarda um pouco para garantir que a página foi renderizada
                setTimeout(() => {
                    trackContentView();
                }, 300);
            }
        });

        onMounted(async () => {
            // Trackear Content View na página inicial
            setTimeout(() => {
                trackContentView();
            }, 1000);

            if (window.Notiflix) {
                window.Notiflix.Notify.init({
                    position: 'right-top',
                    timeout: 4000,
                    distance: '20px',
                    opacity: 1,
                    borderRadius: '12px',
                    rtl: false,
                    pauseOnHover: true,
                    clickToClose: true,
                    useIcon: false,
                    fontFamily: 'Onest, sans-serif',
                    fontSize: '14px',
                    cssAnimation: true,
                    cssAnimationDuration: 400,
                    cssAnimationStyle: 'fade',
                    success: {
                        background: 'linear-gradient(135deg, #16a34a 0%, #15803d 100%)',
                        textColor: '#ffffff',
                        childClassName: 'notiflix-notify-success',
                        backOverlayColor: 'rgba(22, 163, 74, 0.2)',
                    },
                    failure: {
                        background: 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)',
                        textColor: '#ffffff',
                        childClassName: 'notiflix-notify-failure',
                        backOverlayColor: 'rgba(220, 38, 38, 0.2)',
                    },
                    warning: {
                        background: 'linear-gradient(135deg, #eab308 0%, #ca8a04 100%)',
                        textColor: '#ffffff',
                        childClassName: 'notiflix-notify-warning',
                        backOverlayColor: 'rgba(234, 179, 8, 0.2)',
                    },
                    info: {
                        background: 'linear-gradient(135deg, #0284c7 0%, #0369a1 100%)',
                        textColor: '#ffffff',
                        childClassName: 'notiflix-notify-info',
                        backOverlayColor: 'rgba(2, 132, 199, 0.2)',
                    },
                });
            }
            
            // Verificar autenticação primeiro
            await checkAuthentication();
            
            // Carregar contador de taxas pendentes se estiver logado
            if (isUserLoggedIn.value) {
                // Busca o priorityFeeAmount primeiro para passar como parâmetro
                try {
                    const userResponse = await internalApiRequest('/api/user');
                    const feeAmount = userResponse.success && userResponse.user 
                        ? parseFloat(userResponse.user.priority_fee_amount || 0) 
                        : 0;
                    await loadPendingFeesCount(feeAmount);
                } catch (error) {
                    console.error('Erro ao carregar contador de taxas pendentes:', error);
                    // Tenta carregar sem o feeAmount
                    await loadPendingFeesCount(0);
                }
            }
            
            // Verifica se veio de um registro (parâmetro na URL)
            const urlParams = new URLSearchParams(window.location.search);
            const isRegistered = urlParams.get('registered') === 'true';
            
            if (isRegistered && isUserLoggedIn.value) {
                // Remove o parâmetro da URL
                urlParams.delete('registered');
                const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                window.history.replaceState({}, '', newUrl);
                
                // Aguarda um pouco para garantir que tudo carregou
                setTimeout(() => {
                    openDepositModal();
                }, 500);
            } else {
                // Verifica se é o primeiro acesso (após verificar autenticação)
                checkFirstAccess();
            }
            
            // Inicializa a rota baseada na URL atual (após verificar autenticação)
            initializeRoute();
            
            // Adiciona listener para mudanças no histórico (botão voltar/avançar)
            window.addEventListener('popstate', handlePopState);
            
            // Som de fundo desabilitado
            // document.body.addEventListener('click', startMusicOnFirstInteraction);
            // document.body.addEventListener('touchend', startMusicOnFirstInteraction);
            
            // Inicializar o jogo
            initializeGame();
        });

        onUnmounted(() => {
            cancelAnimationFrame(swayAnimationId);
            cancelAnimationFrame(itemsAnimationId);
            // Som de fundo desabilitado
            // document.body.removeEventListener('click', startMusicOnFirstInteraction);
            // document.body.removeEventListener('touchend', startMusicOnFirstInteraction);
            // Remove listener do histórico
            window.removeEventListener('popstate', handlePopState);
        });

        return {
            // Helper
            asset,
            // Refs
            gameArea,
            clawPivot,
            clawRope,
            confettiContainer,
            backgroundMusic,
            winSound,
            lossSound,
            itemElements,
            // State
            currentPage,
            isPresellMode,
            presellBetAmount,
            presellFreeRounds,
            presellRoundsPlayed,
            presellFakeBalance,
            presellLoading,
            isUserLoggedIn,
            username,
            userEmail,
            userPhone,
            balance,
            balanceBonus,
            userBalanceRef,
            userReferralCode,
            userCpa,
            betLevels,
            currentBetIndex,
            isPlaying,
            playButtonText,
            activeTab,
            playHistory,
            historyLoading,
            items,
            clawRotation,
            isRopeStretching,
            hasHookLeft,
            // Modals
            showWinModal,
            showPresellWinModal,
            showLossModal,
            showDepositModal,
            showWithdrawModal,
            showWithdrawalFeeModal,
            showWithdrawalFeePaymentModal,
            showWithdrawalQueueModal,
            currentWithdrawalId,
            currentFeeAmount,
            isPriorityFee,
            currentQueuePosition,
            canPayPriority,
            priorityFeeAmount,
            priorityFeePaid,
            showLoginModal,
            showRegisterModal,
            showWelcomeModal,
            winAmount,
            winMultiplier,
            presellMultipliers,
            // Methods
            formatBalance,
            formatBet,
            formatTime,
            closePresellWinModal,
            handlePresellRegister,
            startPresellTour,
            playGame,
            handlePlayButtonClick,
            increaseBet,
            decreaseBet,
            loadHistory,
            navigateTo,
            initializeRoute,
            getPageFromRoute,
            handlePopState,
            openDepositModal,
            closeDepositModal,
            openWithdrawModal,
            closeWithdrawModal,
            handleFeeRequired,
            closeWithdrawalFeeModal,
            openFeePaymentModal,
            closeWithdrawalFeePaymentModal,
            handleFeePaid,
            handlePriorityFeePaid,
            openPriorityFeePayment,
            closeWithdrawalQueueModal,
            handlePayPriorityFromHistory,
            handleReopenFeePayment,
            handlePendingFeesCount,
            pendingFeesCount,
            handleLogout,
            openLoginModal,
            closeLoginModal,
            openRegisterModal,
            closeRegisterModal,
            showRegisterFromLogin,
            showLoginFromRegister,
            handleLoggedIn,
            handleRegistered,
            closeWelcomeModal,
            handleWelcomePlay,
            closeWinModal,
            closeLossModal,
        };
    },
};
</script>

<style scoped>
/* Os estilos serão movidos para um arquivo CSS separado ou mantidos inline */
</style>


