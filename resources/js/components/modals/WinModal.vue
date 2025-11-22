<template>
    <div class="modal-overlay active" @click.self="$emit('close')">
        <div class="result-modal-content">
            <button class="modal-close" @click="$emit('close')">&times;</button>
            <div class="prize-multiplier-row">
                <img :src="asset('assets/prize-open.png')" alt="Prêmio" class="prize-image">
                <p class="multiplier-text" v-if="multiplier > 0">{{ formatMultiplier(multiplier) }}x</p>
            </div>
            <h3>Você Ganhou!</h3>
            <p class="win-amount">R$ {{ formatAmount(winAmount) }}</p>
            <button class="result-modal-button" @click="$emit('close')">Continuar</button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'WinModal',
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
            // Remove zeros desnecessários (ex: 1.00 vira 1, 2.00 vira 2)
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


