<template>
    <div class="modal-overlay active" @click.self="$emit('close')">
        <div class="result-modal-content shake" ref="modalContent">
            <button class="modal-close" @click="$emit('close')">&times;</button>
            <img :src="asset('assets/bomb1.png')" alt="Bomba" ref="bombImage" class="item-exploding">
            <h3>Talvez na próxima</h3>
            <button class="result-modal-button" @click="$emit('close')">Fechar</button>
        </div>
    </div>
</template>

<script>
import { onMounted, onUnmounted } from 'vue';

export default {
    name: 'LossModal',
    setup() {
        let shakeTimeout = null;

        const asset = (path) => {
            const baseUrl = window.ASSETS_BASE_URL || '';
            return baseUrl + (path.startsWith('/') ? path.substring(1) : path);
        };

        onMounted(() => {
            // Remove shake animation after 500ms
            shakeTimeout = setTimeout(() => {
                const modalContent = document.querySelector('.result-modal-content.shake');
                const bombImage = document.querySelector('.item-exploding');
                if (modalContent) modalContent.classList.remove('shake');
                if (bombImage) bombImage.classList.remove('item-exploding');
            }, 500);
        });

        onUnmounted(() => {
            if (shakeTimeout) clearTimeout(shakeTimeout);
        });

        return {
            asset,
        };
    },
};
</script>


