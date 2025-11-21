<template>
    <div class="modal-overlay active" @click.self="handleClose">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">🎉 Bem-vindo ao Pega Prêmio!</h3>
                <button class="modal-close" @click="handleClose">&times;</button>
            </div>
            <div class="modal-body">
                <div class="welcome-icon">🎁</div>
                <h2 class="welcome-title">
                    Você ganhou {{ freeRounds }} rodadas grátis!
                </h2>
                <p class="welcome-text">
                    Experimente nosso jogo sem gastar nada! Use suas {{ freeRounds }} rodadas grátis e veja como é fácil ganhar prêmios incríveis.
                </p>
                <div class="welcome-badge">
                    <p>🎮 {{ freeRounds }} Rodadas Grátis Disponíveis</p>
                </div>
                <button 
                    @click="handlePlay"
                    class="welcome-button"
                >
                    🎯 Jogar Agora
                </button>
                <p class="welcome-footer">
                    Você pode fechar este modal e jogar depois, mas não perca suas rodadas grátis!
                </p>
            </div>
        </div>
    </div>
</template>

<script>
import { ref } from 'vue';

export default {
    name: 'WelcomeModal',
    props: {
        freeRounds: {
            type: Number,
            default: 3,
        },
    },
    emits: ['close', 'play'],
    setup(props, { emit }) {
        const handleClose = () => {
            // Marca que o usuário já viu o modal
            localStorage.setItem('welcome_modal_seen', 'true');
            emit('close');
        };

        const handlePlay = () => {
            // Marca que o usuário já viu o modal
            localStorage.setItem('welcome_modal_seen', 'true');
            emit('play');
        };

        return {
            freeRounds: props.freeRounds,
            handleClose,
            handlePlay,
        };
    },
};
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    animation: fadeIn 0.3s ease;
}

.modal-overlay.active {
    display: flex;
}

.modal-content {
    background: #1a1a2e;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    max-width: 380px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideUp 0.3s ease;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid rgba(34, 197, 94, 0.2);
    background: linear-gradient(135deg, #16213e 0%, #1a1a2e 100%);
    border-radius: 12px 12px 0 0;
}

.modal-title {
    font-size: 18px;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #94a3b8;
    cursor: pointer;
    padding: 0;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
}

.modal-close:hover {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
}

.modal-body {
    text-align: center;
    padding: 20px;
}

.welcome-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.welcome-title {
    color: #22c55e;
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 10px;
    margin-top: 0;
}

.welcome-text {
    color: #cbd5e1;
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 15px;
}

.welcome-badge {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(22, 163, 74, 0.15) 100%);
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.welcome-badge p {
    color: #22c55e;
    font-size: 14px;
    font-weight: 600;
    margin: 0;
}

.welcome-button {
    width: 100%;
    padding: 12px;
    font-size: 16px;
    font-weight: 700;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border: none;
    border-radius: 8px;
    color: white;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
}

.welcome-button:hover {
    transform: scale(1.02);
    box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
}

.welcome-button:active {
    transform: scale(0.98);
}

.welcome-footer {
    color: #64748b;
    font-size: 12px;
    margin-top: 12px;
    margin-bottom: 0;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        transform: translateY(30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>

