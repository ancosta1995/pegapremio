<template>
    <div>
        <!-- Confetti Container -->
        <div id="confetti-container" ref="confettiContainer"></div>

        <!-- Audio Elements -->
        <audio ref="backgroundMusic" :src="asset('assets/sounds/panto-clowns-jingle-271283.mp3')" loop></audio>
        <audio ref="winSound" :src="asset('assets/sounds/win.wav')"></audio>
        <audio ref="lossSound" :src="asset('assets/sounds/loss.wav')"></audio>

        <!-- Header -->
        <div class="game-header">
            <div class="header-left">
                <img :src="asset('assets/logo-1.png')" alt="Logo" style="height: 35px;">
            </div>
            <div class="header-right">
                <template v-if="isPresellMode">
                    <a href="#" @click.prevent="openRegisterModal" class="header-profile" style="cursor: pointer;">
                        <span class="balance" v-if="!presellLoading && presellBetAmount !== null">
                            R$ {{ formatBalance(presellBetAmount) }}
                        </span>
                        <span class="balance" v-else style="opacity: 0.7; font-size: 14px;">
                            Carregando...
                        </span>
                    </a>
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
                        🎁 RODADA GRÁTIS 🎁
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
                @deposit="openDepositModal"
                @withdraw="openWithdrawModal"
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
            @close="closeWithdrawalFeeModal"
            @pay-fee="openFeePaymentModal"
        />
        <WithdrawalFeePaymentModal
            v-if="showWithdrawalFeePaymentModal"
            :withdrawal-id="currentWithdrawalId"
            :fee-amount="currentFeeAmount"
            @close="closeWithdrawalFeePaymentModal"
            @fee-paid="handleFeePaid"
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
    </div>
</template>

<script>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import WinModal from './modals/WinModal.vue';
import PresellWinModal from './modals/PresellWinModal.vue';
import LossModal from './modals/LossModal.vue';
import DepositModal from './modals/DepositModal.vue';
import WithdrawModal from './modals/WithdrawModal.vue';
import WithdrawalFeeModal from './modals/WithdrawalFeeModal.vue';
import WithdrawalFeePaymentModal from './modals/WithdrawalFeePaymentModal.vue';
import LoginModal from './modals/LoginModal.vue';
import RegisterModal from './modals/RegisterModal.vue';
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
        LoginModal,
        RegisterModal,
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
        const currentWithdrawalId = ref(null);
        const currentFeeAmount = ref(0);
        const showLoginModal = ref(false);
        const showRegisterModal = ref(false);
        const winAmount = ref(0);
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
                    // Em modo presell, sempre ganha com um dos 2 maiores multiplicadores
                    if (isPresellMode.value) {
                        // Verifica se os dados estão carregados
                        if (presellBetAmount.value === null || presellLoading.value) {
                            showErrorToast('Aguarde, carregando configuração...');
                            resetGame();
                            return;
                        }
                        
                        // Sorteia aleatoriamente entre os 2 maiores multiplicadores
                        const randomIndex = Math.floor(Math.random() * presellMultipliers.value.length);
                        const selectedMultiplier = presellMultipliers.value[randomIndex];
                        const calculatedWinAmount = presellBetAmount.value * selectedMultiplier;
                        
                        if (winSound.value) winSound.value.play();
                        triggerConfetti();
                        winAmount.value = calculatedWinAmount;
                        winMultiplier.value = selectedMultiplier;
                        showPresellWinModal.value = true;
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
                
                // Carrega valor da aposta configurável
                internalApiRequest('get_presell_config')
                    .then(config => {
                        if (config.success && config.bet_amount) {
                            presellBetAmount.value = parseFloat(config.bet_amount);
                        } else {
                            // Fallback se não conseguir carregar
                            presellBetAmount.value = 0.50;
                        }
                    })
                    .catch(error => {
                        console.log('Erro ao carregar configuração presell:', error);
                        // Fallback em caso de erro
                        presellBetAmount.value = 0.50;
                    })
                    .finally(() => {
                        presellLoading.value = false;
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
        };

        const handleFeeRequired = (data) => {
            closeWithdrawModal();
            currentWithdrawalId.value = data.withdrawal_id;
            currentFeeAmount.value = data.fee_amount;
            // Abre modal imediatamente (o loading já foi feito no WithdrawModal)
            showWithdrawalFeeModal.value = true;
        };

        const closeWithdrawalFeeModal = () => {
            showWithdrawalFeeModal.value = false;
            currentWithdrawalId.value = null;
            currentFeeAmount.value = 0;
        };

        const openFeePaymentModal = () => {
            showWithdrawalFeeModal.value = false;
            showWithdrawalFeePaymentModal.value = true;
        };

        const closeWithdrawalFeePaymentModal = () => {
            showWithdrawalFeePaymentModal.value = false;
        };

        const handleFeePaid = () => {
            closeWithdrawalFeePaymentModal();
            currentWithdrawalId.value = null;
            currentFeeAmount.value = 0;
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
                initializeGame();
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

        const closeLossModal = () => {
            resetGame();
        };

        // Music initialization
        const startMusicOnFirstInteraction = () => {
            if (backgroundMusic.value && backgroundMusic.value.paused) {
                backgroundMusic.value.play().catch(e => console.error('Autoplay bloqueado:', e));
            }
            document.body.removeEventListener('click', startMusicOnFirstInteraction);
            document.body.removeEventListener('touchend', startMusicOnFirstInteraction);
        };

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
            
            // Inicializa a rota baseada na URL atual (após verificar autenticação)
            initializeRoute();
            
            // Adiciona listener para mudanças no histórico (botão voltar/avançar)
            window.addEventListener('popstate', handlePopState);
            
            document.body.addEventListener('click', startMusicOnFirstInteraction);
            document.body.addEventListener('touchend', startMusicOnFirstInteraction);
            
            // Inicializar o jogo
            initializeGame();
        });

        onUnmounted(() => {
            cancelAnimationFrame(swayAnimationId);
            cancelAnimationFrame(itemsAnimationId);
            document.body.removeEventListener('click', startMusicOnFirstInteraction);
            document.body.removeEventListener('touchend', startMusicOnFirstInteraction);
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
            currentWithdrawalId,
            currentFeeAmount,
            showLoginModal,
            showRegisterModal,
            winAmount,
            winMultiplier,
            presellMultipliers,
            // Methods
            formatBalance,
            formatBet,
            formatTime,
            closePresellWinModal,
            handlePresellRegister,
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
            handleLogout,
            openLoginModal,
            closeLoginModal,
            openRegisterModal,
            closeRegisterModal,
            showRegisterFromLogin,
            showLoginFromRegister,
            handleLoggedIn,
            handleRegistered,
            closeWinModal,
            closeLossModal,
        };
    },
};
</script>

<style scoped>
/* Os estilos serão movidos para um arquivo CSS separado ou mantidos inline */
</style>


