import { ref } from 'vue';

/**
 * Composable para lógica do jogo (animação, claw, items, etc)
 */
export function useGameLogic(
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
    openRegisterModal,
    modals,
    winAmount,
    winMultiplier
) {
    // Game state
    const items = ref([]);
    const clawRotation = ref(0);
    const isRopeStretching = ref(false);
    const isPlaying = ref(false);
    const playButtonText = ref('PEGAR');
    const hasHookLeft = ref(true);
    let itemsAnimationId = null;
    let swayAnimationId = null;
    
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
                        modals.showPresellWinModal.value = true;
                    } else {
                        // Primeiras rodadas sempre perdem (bomba)
                        if (lossSound.value) lossSound.value.play();
                        modals.showLossModal.value = true;
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
                        modals.showWinModal.value = true;
                    } else {
                        if (lossSound.value) lossSound.value.play();
                        modals.showLossModal.value = true;
                    }
                }
            } catch (error) {
                showErrorToast(error.message || 'Erro.');
                resetGame();
            }
        }, 1250);
    };

    const resetGame = () => {
        modals.showWinModal.value = false;
        modals.showLossModal.value = false;
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
    
    const cleanup = () => {
        cancelAnimationFrame(swayAnimationId);
        cancelAnimationFrame(itemsAnimationId);
    };
    
    return {
        items,
        clawRotation,
        isRopeStretching,
        isPlaying,
        playButtonText,
        hasHookLeft,
        createItems,
        animateItems,
        moveClawSway,
        triggerConfetti,
        playGame,
        resetGame,
        increaseBet,
        decreaseBet,
        handlePlayButtonClick,
        cleanup,
    };
}

