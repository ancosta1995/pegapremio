import { ref, computed } from 'vue';

/**
 * Composable para modo presell (demo grátis)
 */
export function usePresell(internalApiRequest, asset) {
    const isPresellMode = ref(window.PRESELL_MODE === true);
    const presellBetAmount = ref(null); // null até carregar do backend
    const presellFreeRounds = ref(3); // Quantidade de rodadas grátis
    const presellRoundsPlayed = ref(0); // Contador de rodadas jogadas
    const presellMultipliers = ref([50.00, 100.00]); // 2 maiores multiplicadores
    const presellLoading = ref(true); // Estado de loading
    
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
    const startPresellTour = (asset) => {
        // Verifica se Shepherd.js está disponível
        if (typeof window.Shepherd === 'undefined') {
            console.log('Shepherd.js não carregado');
            return null;
        }

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

        // Step 3: Explicar caixas de presentes (jogo)
        const prizeImageUrl = asset('assets/prize1.png');
        tour.addStep({
            id: 'game-items',
            text: `
                <div style="padding: 8px; text-align: center;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 8px;">
                        <div style="text-align: center;">
                            <img src="${prizeImageUrl}" alt="Prêmio" style="width: 50px; height: 50px; display: block; margin: 0 auto 5px;">
                            <strong style="color: #22c55e; font-size: 12px;">Prêmio</strong>
                        </div>
                    </div>
                    <p style="font-size: 13px; color: #ffffff; line-height: 1.5; margin: 0;">
                        <strong style="color: #22c55e;">Caixas de Presentes</strong> = Você ganha!
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
                        Você tem <strong style="color: #22c55e;">${presellFreeRounds.value} rodadas grátis</strong>!
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
        
        return tour;
    };
    
    const loadPresellConfig = async () => {
        presellLoading.value = true;
        
        // Carrega valor da aposta configurável e quantidade de rodadas
        try {
            const config = await internalApiRequest('get_presell_config');
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
        } catch (error) {
            console.log('Erro ao carregar configuração presell:', error);
            // Fallback em caso de erro
            presellBetAmount.value = 0.50;
            presellFreeRounds.value = 3;
        } finally {
            presellLoading.value = false;
        }
        
        // Carrega os 2 maiores multiplicadores
        try {
            const result = await internalApiRequest('get_presell_multipliers');
            if (result.success && result.multipliers && result.multipliers.length >= 2) {
                presellMultipliers.value = result.multipliers;
            }
        } catch (error) {
            console.log('Erro ao carregar multiplicadores presell:', error);
        }
    };
    
    return {
        isPresellMode,
        presellBetAmount,
        presellFreeRounds,
        presellRoundsPlayed,
        presellMultipliers,
        presellLoading,
        presellFakeBalance,
        startPresellTour,
        loadPresellConfig,
    };
}

