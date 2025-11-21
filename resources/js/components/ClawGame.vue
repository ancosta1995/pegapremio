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
                class="nav-btn"
                :class="{ active: currentPage === 'wallet' }"
                @click="navigateTo('wallet')"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M21 4H3C1.9 4 1 4.9 1 6v12c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-1 14H4c-.55 0-1-.45-1-1V7h18v10c0 .55-.45 1-1 1zm-1-8H5v2h14v-2zM4 6h16v1H4z"/>
                </svg>
                <span>Carteira</span>
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
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
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
        // Helper para caminhos de assets
        const asset = (path) => {
            const baseUrl = window.ASSETS_BASE_URL || '';
            return baseUrl + (path.startsWith('/') ? path.substring(1) : path);
        };

        // Refs
        const gameArea = ref(null);
        const clawPivot = ref(null);
        const clawRope = ref(null);
        const confettiContainer = ref(null);
        const backgroundMusic = ref(null);
        const winSound = ref(null);
        const lossSound = ref(null);
        const itemElements = ref([]);

        // State
        const currentPage = ref('game'); // 'game', 'profile', 'wallet', 'affiliate'
        
        // Modo presell (demo grátis)
        const isPresellMode = ref(window.PRESELL_MODE === true);
        
        // Mapeamento de rotas
        const routeMap = {
            '/': 'game',
            '/perfil': 'profile',
            '/carteira': 'wallet',
            '/afiliados': 'affiliate',
        };
        
        // Mapeamento reverso (página -> rota)
        const pageToRoute = {
            'game': '/',
            'profile': '/perfil',
            'wallet': '/carteira',
            'affiliate': '/afiliados',
        };
        const isUserLoggedIn = ref(false);
        const username = ref('');
        const userEmail = ref('');
        const userPhone = ref('');
        const balance = ref(0);
        const balanceBonus = ref(0);
        const userBalanceRef = ref(0);
        const userReferralCode = ref('');
        const userCpa = ref(0);
        const betLevels = ref([0.50, 1.00, 2.00, 5.00, 10.00]);
        // Em modo presell, valor configurável do backend
        const presellBetAmount = ref(null); // null até carregar do backend
        const presellFreeRounds = ref(3); // Quantidade de rodadas grátis
        const presellRoundsPlayed = ref(0); // Contador de rodadas jogadas
        const presellMultipliers = ref([50.00, 100.00]); // 2 maiores multiplicadores
        const presellLoading = ref(true); // Estado de loading
        const currentBetIndex = ref(0);
        const isPlaying = ref(false);
        const playButtonText = ref('PEGAR');
        const activeTab = ref('bet');
        const playHistory = ref([]);
        const historyLoading = ref(false);

        // Game state
        const items = ref([]);
        const clawRotation = ref(0);
        const isRopeStretching = ref(false);
        let itemsAnimationId = null;
        let swayAnimationId = null;

        // Modals
        const showWinModal = ref(false);
        const showPresellWinModal = ref(false);
        const showLossModal = ref(false);
        const showDepositModal = ref(false);
        const showWithdrawModal = ref(false);
        const showWithdrawalFeeModal = ref(false);
        const showWithdrawalFeePaymentModal = ref(false);
        const showWithdrawalQueueModal = ref(false);
        const currentWithdrawalId = ref(null);
        const currentFeeAmount = ref(0);
        const isPriorityFee = ref(false);
        const currentQueuePosition = ref(0);
        const canPayPriority = ref(false);
        const priorityFeeAmount = ref(0);
        const priorityFeePaid = ref(false);
        const showLoginModal = ref(false);
        const showRegisterModal = ref(false);
        const showWelcomeModal = ref(false);
        const justRegistered = ref(false); // Flag para detectar registro recente
        const winAmount = ref(0);
        let currentTour = null; // Referência ao tour ativo
        const winMultiplier = ref(0);
        const hasHookLeft = ref(true); // Pode ser verificado dinamicamente se necessário

        const prizeImages = ref([
            asset('assets/prize1.png'),
            asset('assets/prize2.png'),
            asset('assets/prize3.png'),
            asset('assets/prize4.png'),
        ]);

        // API Helper
        const internalApiRequest = async (action, data = {}) => {
            // Se for uma URL completa (começa com /), faz fetch direto
            if (typeof action === 'string' && action.startsWith('/')) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                
                const options = {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin',
                };
                
                // Se tiver data, muda para POST
                if (Object.keys(data).length > 0) {
                    options.method = 'POST';
                    options.headers['Content-Type'] = 'application/json';
                    options.body = JSON.stringify(data);
                }
                
                const response = await fetch(action, options);
                
                if (!response.ok) {
                    if (response.status === 419) {
                        window.location.reload();
                        throw new Error('Sessão expirada. Recarregando...');
                    }
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || 'Erro de rede.');
                }
                return response.json();
            }
            
            // Sistema antigo (POST com action)
            const params = new URLSearchParams();
            params.append('action', action);
            for (const key in data) {
                params.append(key, data[key]);
            }
            
            // Obtém o token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            
            const response = await fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: params,
            });
            
            if (!response.ok) {
                // Se for erro 419, tenta obter um novo token CSRF
                if (response.status === 419) {
                    // Recarrega a página para obter um novo token
                    window.location.reload();
                    throw new Error('Sessão expirada. Recarregando...');
                }
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Erro de rede.');
            }
            return response.json();
        };

        // Format helpers
        const formatBalance = (value) => {
            return parseFloat(value).toFixed(2).replace('.', ',');
        };

        const formatBet = (value) => {
            return parseFloat(value).toFixed(2).replace('.', ',');
        };

        const formatTime = (dateString) => {
            return new Date(dateString).toLocaleTimeString('pt-BR');
        };

        // Game functions
        const createItems = () => {
            if (!gameArea.value) {
                // Se o gameArea ainda não está disponível, tenta novamente depois
                setTimeout(createItems, 100);
                return;
            }
            
            // Clear existing items
            items.value = [];
            itemElements.value = [];

            const itemsCount = 7 + Math.floor(Math.random() * 4);
            const gameRect = gameArea.value.getBoundingClientRect();
            
            // Se o gameArea ainda não tem dimensões, tenta novamente
            if (gameRect.width === 0 || gameRect.height === 0) {
                setTimeout(createItems, 100);
                return;
            }

            const currentPrizeSrc = prizeImages.value[currentBetIndex.value % prizeImages.value.length];

            for (let i = 0; i < itemsCount; i++) {
                const isBomb = i % 3 === 0;
                items.value.push({
                    src: isBomb ? asset('assets/bomb1.png') : currentPrizeSrc,
                    x: Math.random() * (gameRect.width - 48),
                    y: (gameRect.height * 0.4) + (Math.random() * (gameRect.height * 0.5 - 48)),
                    vx: (Math.random() - 0.5) * 1.5,
                    vy: (Math.random() - 0.5) * 1.5,
                    exploding: false,
                });
            }
        };

        const animateItems = () => {
            if (itemsAnimationId) cancelAnimationFrame(itemsAnimationId);
            
            const gameRect = gameArea.value?.getBoundingClientRect();
            if (!gameRect || gameRect.width === 0) {
                itemsAnimationId = requestAnimationFrame(animateItems);
                return;
            }

            items.value.forEach((item) => {
                item.x += item.vx;
                item.y += item.vy;

                if (item.x <= 0 || item.x >= gameRect.width - 48) {
                    item.vx *= -1;
                    item.x = Math.max(0, Math.min(item.x, gameRect.width - 48));
                }
                if (item.y <= gameRect.height * 0.25 || item.y >= gameRect.height - 48) {
                    item.vy *= -1;
                    item.y = Math.max(gameRect.height * 0.25, Math.min(item.y, gameRect.height - 48));
                }
            });

            itemsAnimationId = requestAnimationFrame(animateItems);
        };

        const moveClawSway = () => {
            if (!clawPivot.value) return;
            const time = Date.now() / 800;
            clawRotation.value = 18 * Math.sin(time);
            swayAnimationId = requestAnimationFrame(moveClawSway);
        };

        const triggerConfetti = () => {
            if (!confettiContainer.value) return;
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti-piece';
                confetti.style.left = `${Math.random() * 100}vw`;
                confetti.style.animationDelay = `${Math.random() * 2}s`;
                confetti.style.backgroundColor = `hsl(${Math.random() * 360}, 100%, 50%)`;
                confettiContainer.value.appendChild(confetti);
                setTimeout(() => confetti.remove(), 4000);
            }
        };

        const playGame = async () => {
            if (isPlaying.value) return;
            
            // Em modo presell, não precisa estar logado nem verificar saldo
            if (!isPresellMode.value) {
                if (!isUserLoggedIn.value) {
                    openRegisterModal();
                    return;
                }
                if (balance.value < betLevels.value[currentBetIndex.value]) {
                    showErrorToast('Saldo insuficiente!');
                    return;
                }
            }

            isPlaying.value = true;
            playButtonText.value = 'PEGANDO...';
            cancelAnimationFrame(swayAnimationId);
            cancelAnimationFrame(itemsAnimationId);
            isRopeStretching.value = true;

            setTimeout(async () => {
                isRopeStretching.value = false;
                
                const collisionType = (() => {
                    if (!clawPivot.value || !gameArea.value) return 'none';
                    const clawRect = clawPivot.value.getBoundingClientRect();
                    const gameAreaRect = gameArea.value.getBoundingClientRect();
                    const clawCenterX = clawRect.left + (clawRect.width / 2) - gameAreaRect.left;
                    
                    for (const item of items.value) {
                        if (Math.abs(clawCenterX - (item.x + 24)) < 24) {
                            return item.src.includes('bomb') ? 'bomb' : 'prize';
                        }
                    }
                    return 'none';
                })();

                try {
                    // Em modo presell, controla rodadas grátis
                    if (isPresellMode.value) {
                        // Verifica se os dados estão carregados
                        if (presellBetAmount.value === null || presellLoading.value) {
                            showErrorToast('Aguarde, carregando configuração...');
                            resetGame();
                            return;
                        }
                        
                        // Verifica se ainda tem rodadas disponíveis
                        if (presellRoundsPlayed.value >= presellFreeRounds.value) {
                            showErrorToast('Você já usou todas as rodadas grátis! Crie uma conta para continuar jogando.');
                            resetGame();
                            return;
                        }
                        
                        // Incrementa contador de rodadas
                        presellRoundsPlayed.value++;
                        
                        // Primeiras rodadas sempre perdem (exceto a última)
                        const isLastRound = presellRoundsPlayed.value === presellFreeRounds.value;
                        
                        if (isLastRound) {
                            // Última rodada sempre ganha
                            const randomIndex = Math.floor(Math.random() * presellMultipliers.value.length);
                            const selectedMultiplier = presellMultipliers.value[randomIndex];
                            const calculatedWinAmount = presellBetAmount.value * selectedMultiplier;
                            
                            if (winSound.value) winSound.value.play();
                            triggerConfetti();
                            winAmount.value = calculatedWinAmount;
                            winMultiplier.value = selectedMultiplier;
                            showPresellWinModal.value = true;
                        } else {
                            // Primeiras rodadas sempre perdem (bomba)
                            if (lossSound.value) lossSound.value.play();
                            showLossModal.value = true;
                        }
                    } else {
                        // Modo normal (com autenticação)
                        const action = 'play_claw_game';
                        const betAmount = betLevels.value[currentBetIndex.value];
                        
                        const result = await internalApiRequest(action, {
                            bet_amount: betAmount,
                            collision_type: collisionType,
                        });
                        
                        if (result.new_balance !== undefined) {
                            balance.value = parseFloat(result.new_balance);
                        }

                        if (result.is_win) {
                            if (winSound.value) winSound.value.play();
                            triggerConfetti();
                            winAmount.value = result.win_amount;
                            winMultiplier.value = result.multiplier || 0;
                            showWinModal.value = true;
                        } else {
                            if (lossSound.value) lossSound.value.play();
                            showLossModal.value = true;
                        }
                    }
                } catch (error) {
                    showErrorToast(error.message || 'Erro.');
                    resetGame();
                }
            }, 1250);
        };

        const resetGame = () => {
            showWinModal.value = false;
            showLossModal.value = false;
            isRopeStretching.value = false;
            createItems();
            animateItems();
            playButtonText.value = 'PEGAR';
            swayAnimationId = requestAnimationFrame(moveClawSway);
            isPlaying.value = false;
        };

        const increaseBet = () => {
            if (!isPlaying.value && currentBetIndex.value < betLevels.value.length - 1) {
                currentBetIndex.value++;
            }
        };

        const decreaseBet = () => {
            if (!isPlaying.value && currentBetIndex.value > 0) {
                currentBetIndex.value--;
            }
        };

        const handlePlayButtonClick = () => {
            // Em modo presell, não precisa estar logado
            if (isPresellMode.value) {
                playGame();
            } else if (!isUserLoggedIn.value) {
                openRegisterModal();
            } else {
                playGame();
            }
        };

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
                presellLoading.value = true;
                
                // Carrega valor da aposta configurável e quantidade de rodadas
                internalApiRequest('get_presell_config')
                    .then(config => {
                        if (config.success) {
                            if (config.bet_amount) {
                                presellBetAmount.value = parseFloat(config.bet_amount);
                            } else {
                                presellBetAmount.value = 0.50;
                            }
                            if (config.free_rounds) {
                                presellFreeRounds.value = parseInt(config.free_rounds);
                            }
                        } else {
                            // Fallback se não conseguir carregar
                            presellBetAmount.value = 0.50;
                            presellFreeRounds.value = 3;
                        }
                    })
                    .catch(error => {
                        console.log('Erro ao carregar configuração presell:', error);
                        // Fallback em caso de erro
                        presellBetAmount.value = 0.50;
                        presellFreeRounds.value = 3;
                    })
                    .finally(() => {
                        presellLoading.value = false;
                        // Inicia tour após carregar configuração
                        if (isPresellMode.value) {
                            setTimeout(() => {
                                startPresellTour();
                            }, 500);
                        }
                    });
                
                // Carrega os 2 maiores multiplicadores
                internalApiRequest('get_presell_multipliers')
                    .then(result => {
                        if (result.success && result.multipliers && result.multipliers.length >= 2) {
                            presellMultipliers.value = result.multipliers;
                        }
                    })
                    .catch(error => {
                        console.log('Erro ao carregar multiplicadores presell:', error);
                    });
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

        // Navigation
        const navigateTo = (page) => {
            // Apenas a página 'game' é acessível sem login
            if (!isUserLoggedIn.value && page !== 'game') {
                openRegisterModal();
                // Se estiver tentando acessar uma página protegida, volta para o jogo
                if (currentPage.value !== 'game') {
                    currentPage.value = 'game';
                    const route = pageToRoute['game'] || '/';
                    window.history.pushState({ page: 'game' }, '', route);
                }
                return;
            }
            currentPage.value = page;
            
            // Atualiza a URL sem recarregar a página
            const route = pageToRoute[page] || '/';
            window.history.pushState({ page }, '', route);
        };
        
        // Função para ler a rota atual da URL
        const getPageFromRoute = () => {
            const path = window.location.pathname;
            return routeMap[path] || 'game';
        };
        
        // Inicializa a página baseada na URL atual
        const initializeRoute = () => {
            const page = getPageFromRoute();
            
            // Verifica se a página requer autenticação
            if (!isUserLoggedIn.value && page !== 'game') {
                // Se não estiver logado e tentar acessar página protegida, redireciona para o jogo
                currentPage.value = 'game';
                const route = pageToRoute['game'] || '/';
                window.history.replaceState({ page: 'game' }, '', route);
                return;
            }
            
            currentPage.value = page;
            
            // Define o estado inicial do histórico se não existir
            if (!window.history.state || !window.history.state.page) {
                window.history.replaceState({ page }, '', pageToRoute[page] || '/');
            }
        };
        
        // Listener para mudanças no histórico do navegador (botão voltar/avançar)
        const handlePopState = (event) => {
            const page = event.state?.page || getPageFromRoute();
            
            // Verifica se a página requer autenticação
            if (!isUserLoggedIn.value && page !== 'game') {
                // Se não estiver logado e tentar acessar página protegida, redireciona para o jogo
                currentPage.value = 'game';
                const route = pageToRoute['game'] || '/';
                window.history.replaceState({ page: 'game' }, '', route);
                openRegisterModal();
                return;
            }
            
            currentPage.value = page;
        };

        // Modal functions
        const openDepositModal = () => {
            if (!isUserLoggedIn.value) {
                openRegisterModal();
                return;
            }
            showDepositModal.value = true;
        };

        const closeDepositModal = () => {
            showDepositModal.value = false;
        };

        const openWithdrawModal = () => {
            if (!isUserLoggedIn.value) {
                openRegisterModal();
                return;
            }
            showWithdrawModal.value = true;
        };

        const closeWithdrawModal = () => {
            showWithdrawModal.value = false;
            closeWithdrawalQueueModal();
        };

        const handleFeeRequired = (data) => {
            closeWithdrawModal();
            currentWithdrawalId.value = data.withdrawal_id;
            currentFeeAmount.value = data.fee_amount;
            isPriorityFee.value = false; // Primeira taxa (validação)
            // Abre modal imediatamente (o loading já foi feito no WithdrawModal)
            showWithdrawalFeeModal.value = true;
        };

        const closeWithdrawalFeeModal = () => {
            showWithdrawalFeeModal.value = false;
            // Não limpa os valores aqui, pois podem ser necessários para o modal de pagamento
            // Os valores serão limpos quando o modal de pagamento for fechado
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
            showWithdrawalFeeModal.value = false;
            
            // Usa nextTick para garantir que o DOM foi atualizado antes de abrir o modal
            nextTick(() => {
                // Abre o modal de pagamento
                showWithdrawalFeePaymentModal.value = true;
                
                console.log('Modal de pagamento aberto com valores:', {
                    withdrawalId: currentWithdrawalId.value,
                    feeAmount: currentFeeAmount.value,
                    isPriorityFee: isPriorityFee.value,
                });
            });
        };

        const closeWithdrawalFeePaymentModal = () => {
            showWithdrawalFeePaymentModal.value = false;
            // NÃO limpa o currentWithdrawalId aqui, pois pode ser necessário para o modal de fila
            // Os valores só serão limpos quando o modal de fila for fechado
        };

        const handleFeePaid = async (queuePosition) => {
            closeWithdrawalFeePaymentModal();
            closeWithdrawalFeeModal();
            
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
                    showWithdrawalQueueModal.value = true;
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
                showWithdrawalQueueModal.value = true;
            }
        };

        const handlePriorityFeePaid = async () => {
            closeWithdrawalFeePaymentModal();
            closeWithdrawalFeeModal();
            
            // Atualiza o status da prioridade no modal de fila
            priorityFeePaid.value = true;
            
            // Se o modal de fila estiver aberto, atualiza as informações
            if (showWithdrawalQueueModal.value) {
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
                        showWithdrawalQueueModal.value = true;
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
            
            closeWithdrawalQueueModal();
            
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
            
            showWithdrawalFeeModal.value = true;
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
            showWithdrawalFeeModal.value = true;
        };

        const handleReopenFeePayment = async (withdrawal) => {
            console.log('handleReopenFeePayment chamado:', withdrawal);
            // Reabre o modal de pagamento da primeira taxa
            currentWithdrawalId.value = withdrawal.id;
            isPriorityFee.value = false;
            
            // Busca o valor da taxa de validação
            try {
                const response = await internalApiRequest(`/api/withdrawals/${withdrawal.id}/info`);
                console.log('Resposta do saque:', response);
                if (response.success) {
                    // Se já existe transação, vai direto para o QR code
                    if (withdrawal.fee_transaction_id || response.withdrawal.fee_transaction_id) {
                        // Busca a transação existente
                        currentFeeAmount.value = response.withdrawal.fee_amount || 50.00;
                        console.log('Abrindo modal de pagamento direto (QR code existente)');
                        // Abre direto o modal de pagamento (QR code)
                        showWithdrawalFeePaymentModal.value = true;
                    } else {
                        // Se não existe, abre modal de explicação primeiro
                        currentFeeAmount.value = response.withdrawal.fee_amount || 50.00;
                        console.log('Abrindo modal de explicação primeiro');
                        showWithdrawalFeeModal.value = true;
                    }
                }
            } catch (error) {
                console.error('Erro ao buscar informações do saque:', error);
                // Usa valor padrão
                currentFeeAmount.value = 50.00;
                showWithdrawalFeeModal.value = true;
            }
        };

        const closeWithdrawalQueueModal = () => {
            showWithdrawalQueueModal.value = false;
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
                    currentPage.value = 'game';
                    const route = pageToRoute['game'] || '/';
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

        const openLoginModal = () => {
            showLoginModal.value = true;
        };

        const closeLoginModal = () => {
            showLoginModal.value = false;
        };

        const openRegisterModal = () => {
            // Para o tour se estiver ativo
            if (currentTour) {
                try {
                    // Verifica se o tour está ativo e o completa
                    if (typeof currentTour.isActive === 'function' && currentTour.isActive()) {
                        currentTour.complete();
                    } else if (currentTour.currentStep) {
                        // Se não tem isActive, tenta completar diretamente
                        currentTour.complete();
                    }
                    currentTour = null;
                } catch (e) {
                    console.log('Erro ao parar tour:', e);
                }
            }
            showRegisterModal.value = true;
        };

        const closeRegisterModal = () => {
            showRegisterModal.value = false;
        };

        const showRegisterFromLogin = () => {
            showLoginModal.value = false;
            showRegisterModal.value = true;
        };

        const showLoginFromRegister = () => {
            showRegisterModal.value = false;
            showLoginModal.value = true;
        };

        const handleLoggedIn = (data) => {
            isUserLoggedIn.value = true;
            if (data.user) {
                username.value = data.user.name || data.user.email;
                userEmail.value = data.user.email || '';
                userPhone.value = data.user.phone || '';
                balance.value = parseFloat(data.user.balance || 0);
                balanceBonus.value = parseFloat(data.user.balance_bonus || 0);
                userBalanceRef.value = parseFloat(data.user.balance_ref || 0);
                userReferralCode.value = data.user.referral_code || '';
                userCpa.value = parseFloat(data.user.cpa || 0);
            }
            // Recarrega a configuração do jogo
            initializeGame();
        };

        const handleRegistered = (data) => {
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
                priorityFeeAmount.value = parseFloat(data.user.priority_fee_amount || 0);
                initializeGame();
                
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
                closeRegisterModal();
                
                // Adiciona parâmetro na URL e redireciona para a página principal
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('registered', 'true');
                window.location.href = currentUrl.toString();
            }
        };

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

        // Computed para saldo fake (desconta a cada rodada)
        const presellFakeBalance = computed(() => {
            if (!presellBetAmount.value || presellLoading.value) {
                return 0;
            }
            const totalBalance = presellBetAmount.value * presellFreeRounds.value;
            const spent = presellBetAmount.value * presellRoundsPlayed.value;
            return Math.max(0, totalBalance - spent);
        });

        // Função para iniciar o tour de onboarding na presell
        const startPresellTour = () => {
            // Verifica se Shepherd.js está disponível
            if (typeof window.Shepherd === 'undefined') {
                console.log('Shepherd.js não carregado');
                return;
            }

            // Tour sempre ativo (removida verificação do localStorage)

            const tour = new window.Shepherd.Tour({
                defaultStepOptions: {
                    cancelIcon: {
                        enabled: false // Remove opção de fechar
                    },
                    classes: 'shepherd-theme-custom',
                    scrollTo: { behavior: 'smooth', block: 'center' },
                    canClickTarget: false
                },
                useModalOverlay: true
            });
            
            // Salva referência do tour
            currentTour = tour;

            // Step 1: Botão de depositar (começa pelo botão)
            tour.addStep({
                id: 'deposit-button',
                text: `
                    <div style="padding: 8px;">
                        <p style="font-size: 13px; color: #ffffff; line-height: 1.5; margin: 0;">
                            Aqui você pode depositar dinheiro para jogar com valores reais!
                        </p>
                    </div>
                `,
                attachTo: {
                    element: '#presell-deposit-btn',
                    on: 'bottom'
                },
                buttons: [
                    {
                        text: 'Próximo',
                        action: tour.next
                    }
                ]
            });

            // Step 2: Saldo
            tour.addStep({
                id: 'balance',
                text: `
                    <div style="padding: 8px;">
                        <p style="font-size: 13px; color: #ffffff; line-height: 1.5; margin: 0;">
                            Este é o seu saldo disponível para jogar. Ele diminui a cada rodada!
                        </p>
                    </div>
                `,
                attachTo: {
                    element: '#presell-balance',
                    on: 'bottom'
                },
                buttons: [
                    {
                        text: 'Anterior',
                        action: tour.back
                    },
                    {
                        text: 'Próximo',
                        action: tour.next
                    }
                ]
            });

            // Step 3: Explicar caixas de presentes e bombas (jogo)
            const prizeImageUrl = asset('assets/prize1.png');
            const bombImageUrl = asset('assets/bomb1.png');
            tour.addStep({
                id: 'game-items',
                text: `
                    <div style="padding: 8px; text-align: center;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 8px;">
                            <div style="text-align: center;">
                                <img src="${prizeImageUrl}" alt="Prêmio" style="width: 50px; height: 50px; display: block; margin: 0 auto 5px;">
                                <strong style="color: #22c55e; font-size: 12px;">Prêmio</strong>
                            </div>
                            <div style="text-align: center;">
                                <img src="${bombImageUrl}" alt="Bomba" style="width: 50px; height: 50px; display: block; margin: 0 auto 5px;">
                                <strong style="color: #ef4444; font-size: 12px;">Bomba</strong>
                            </div>
                        </div>
                        <p style="font-size: 13px; color: #ffffff; line-height: 1.5; margin: 0;">
                            <strong style="color: #22c55e;">Caixas de Presentes</strong> = Você ganha!<br>
                            <strong style="color: #ef4444;">Bombas</strong> = Você perde
                        </p>
                    </div>
                `,
                attachTo: {
                    element: '#game-area',
                    on: 'top'
                },
                buttons: [
                    {
                        text: 'Anterior',
                        action: tour.back
                    },
                    {
                        text: 'Próximo',
                        action: tour.next
                    }
                ]
            });

            // Step 4: Explicar valor da aposta (input)
            tour.addStep({
                id: 'bet-amount',
                text: `
                    <div style="padding: 8px;">
                        <p style="font-size: 13px; color: #ffffff; line-height: 1.5; margin: 0;">
                            Aqui você define o valor de cada rodada baseada no seu saldo
                        </p>
                    </div>
                `,
                attachTo: {
                    element: '#bet-amount-display',
                    on: 'top'
                },
                buttons: [
                    {
                        text: 'Anterior',
                        action: tour.back
                    },
                    {
                        text: 'Próximo',
                        action: tour.next
                    }
                ]
            });

            // Step 5: Botão de jogar
            tour.addStep({
                id: 'play-button',
                text: `
                    <div style="padding: 8px;">
                        <p style="font-size: 13px; color: #ffffff; line-height: 1.5; margin: 0;">
                            Clique em <strong style="color: #ef4444;">"PEGAR"</strong> quando a garra estiver alinhada com um prêmio!
                        </p>
                    </div>
                `,
                attachTo: {
                    element: '#play-button',
                    on: 'top'
                },
                buttons: [
                    {
                        text: 'Anterior',
                        action: tour.back
                    },
                    {
                        text: 'Próximo',
                        action: tour.next
                    }
                ]
            });

            // Step 6: Rodadas grátis
            tour.addStep({
                id: 'free-rounds',
                text: `
                    <div style="padding: 8px;">
                        <p style="font-size: 13px; color: #ffffff; line-height: 1.5; margin: 0;">
                            Você tem <strong style="color: #22c55e;">${presellFreeRounds.value} rodadas grátis</strong>!</strong>!
                        </p>
                    </div>
                `,
                attachTo: {
                    element: '.presell-badge',
                    on: 'bottom'
                },
                buttons: [
                    {
                        text: 'Anterior',
                        action: tour.back
                    },
                    {
                        text: 'Começar!',
                        action: tour.complete
                    }
                ]
            });

            // Inicia o tour
            tour.start();
        };

        const closeLossModal = () => {
            resetGame();
        };

        // Music initialization - DESABILITADO
        // const startMusicOnFirstInteraction = () => {
        //     if (backgroundMusic.value && backgroundMusic.value.paused) {
        //         backgroundMusic.value.play().catch(e => console.error('Autoplay bloqueado:', e));
        //     }
        //     document.body.removeEventListener('click', startMusicOnFirstInteraction);
        //     document.body.removeEventListener('touchend', startMusicOnFirstInteraction);
        // };

        // Helper functions para toasts natalinos
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

        onMounted(async () => {
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


