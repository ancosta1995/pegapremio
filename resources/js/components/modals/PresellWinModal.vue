<template>
    <div class="modal-overlay active" @click.self="$emit('close')">
        <div class="result-modal-content" style="max-width: 500px;">
            <button class="modal-close" @click="$emit('close')">&times;</button>
            <div class="prize-multiplier-row">
                <img :src="asset('assets/prize-open.png')" alt="Prêmio" class="prize-image">
                <p class="multiplier-text" v-if="multiplier > 0">{{ formatMultiplier(multiplier) }}x</p>
            </div>
            <h3 style="color: #22c55e; margin-bottom: 10px;">🎉 Parabéns! Você Ganhou! 🎉</h3>
            <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 20px; border-radius: 12px; margin: 20px 0; border: 2px solid #fbbf24;">
                <p style="font-size: 14px; color: #92400e; margin-bottom: 8px; font-weight: 600;">
                    Se você tivesse jogado com dinheiro real:
                </p>
                <p class="win-amount" style="font-size: 32px; color: #16a34a; font-weight: 800; margin: 0;">
                    R$ {{ formatAmount(winAmount) }}
                </p>
            </div>
            <p style="color: #666; font-size: 14px; margin-bottom: 20px;">
                Crie sua conta agora e comece a ganhar dinheiro de verdade!
            </p>
            <button 
                class="result-modal-button" 
                @click="$emit('close')"
                style="
                    background: transparent;
                    color: #666;
                    margin-top: 10px;
                    font-size: 14px;
                "
            >
                Fechar
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'PresellWinModal',
    props: {
        winAmount: {
            type: Number,
            default: 0,
        },
        multiplier: {
            type: Number,
            default: 0,
        },
    },
    methods: {
        formatAmount(value) {
            return parseFloat(value).toFixed(2).replace('.', ',');
        },
        formatMultiplier(value) {
            const num = parseFloat(value);
            return num % 1 === 0 ? num.toString() : num.toFixed(2);
        },
        asset(path) {
            const baseUrl = window.ASSETS_BASE_URL || '';
            return baseUrl + (path.startsWith('/') ? path.substring(1) : path);
        },
    },
};
</script>

