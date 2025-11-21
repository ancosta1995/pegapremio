<template>
    <div class="modal-overlay active" @click.self="$emit('close')">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Entrar</h3>
                <button class="modal-close" @click="$emit('close')">&times;</button>
            </div>
            <div class="modal-body">
                <form @submit.prevent="handleLogin">
                    <div class="form-group">
                        <label class="label">Email</label>
                        <input
                            type="email"
                            class="form-input"
                            v-model="form.email"
                            required
                            placeholder="seu@email.com"
                        />
                    </div>
                    <div class="form-group">
                        <label class="label">Senha</label>
                        <input
                            type="password"
                            class="form-input"
                            v-model="form.password"
                            required
                            placeholder="Digite sua senha"
                        />
                    </div>
                    <div v-if="errorMessage" class="error-message">
                        {{ errorMessage }}
                    </div>
                    <button
                        type="submit"
                        class="modal-button"
                        :disabled="loading"
                    >
                        {{ loading ? 'Entrando...' : 'Entrar' }}
                    </button>
                    <div class="form-footer">
                        <p>Não tem uma conta? <a href="#" @click.prevent="$emit('showRegister')">Criar conta</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { ref } from 'vue';

export default {
    name: 'LoginModal',
    emits: ['close', 'showRegister', 'loggedIn'],
    setup(props, { emit }) {
        const form = ref({
            email: '',
            password: '',
        });
        const loading = ref(false);
        const errorMessage = ref('');

        const handleLogin = async () => {
            errorMessage.value = '';
            loading.value = true;

            try {
                const response = await fetch('/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify(form.value),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    if (window.showSuccessToast) {
                        window.showSuccessToast('Login realizado com sucesso!');
                    } else if (window.Notiflix) {
                        window.Notiflix.Notify.success('🎄 Login realizado com sucesso!');
                    }
                    emit('loggedIn', data);
                    emit('close');
                } else {
                    errorMessage.value = data.message || 'Email ou senha incorretos.';
                }
            } catch (error) {
                errorMessage.value = 'Erro ao conectar com o servidor. Tente novamente.';
                console.error('Erro no login:', error);
            } finally {
                loading.value = false;
            }
        };

        return {
            form,
            loading,
            errorMessage,
            handleLogin,
        };
    },
};
</script>

<style scoped>
.error-message {
    background-color: rgba(239, 68, 68, 0.1);
    border: 1px solid #ef4444;
    color: #fca5a5;
    padding: 0.75rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    text-align: center;
}

.form-footer {
    margin-top: 1rem;
    text-align: center;
    color: var(--cor-texto-secundaria);
    font-size: 0.9rem;
}

.form-footer a {
    color: var(--cor-principal);
    text-decoration: none;
    font-weight: bold;
}

.form-footer a:hover {
    text-decoration: underline;
}
</style>

