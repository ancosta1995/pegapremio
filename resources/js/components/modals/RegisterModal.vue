<template>
    <div class="modal-overlay active" @click.self="$emit('close')">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Criar Conta</h3>
                <button class="modal-close" @click="$emit('close')">&times;</button>
            </div>
            <div class="modal-body">
                <form @submit.prevent="handleRegister">
                    <div class="form-group">
                        <label class="label">Nome Completo</label>
                        <input
                            type="text"
                            class="form-input"
                            v-model="form.name"
                            required
                            placeholder="Digite seu nome completo"
                        />
                    </div>
                    <div class="form-group">
                        <label class="label">Número de Telefone</label>
                        <div class="phone-input-wrapper">
                            <span class="country-code">+55</span>
                            <input
                                type="tel"
                                class="form-input phone-input"
                                v-model="form.phone"
                                required
                                placeholder="(00) 00000-0000"
                                @input="formatPhone"
                            />
                        </div>
                    </div>
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
                            placeholder="Mínimo 8 caracteres"
                            minlength="8"
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
                        {{ loading ? 'Criando conta...' : 'Criar Conta' }}
                    </button>
                    <div class="form-footer">
                        <p>Já tem uma conta? <a href="#" @click.prevent="$emit('showLogin')">Fazer login</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';

export default {
    name: 'RegisterModal',
    emits: ['close', 'showLogin', 'registered'],
    setup(props, { emit }) {
        const form = ref({
            name: '',
            phone: '',
            email: '',
            password: '',
            referral_code: '',
            // Tracking fields
            click_id: '',
            kwai_click_id: '',
            pixel_id: '',
            campaign_id: '',
            adset_id: '',
            creative_id: '',
            utm_source: '',
            utm_campaign: '',
            utm_medium: '',
            utm_content: '',
            utm_term: '',
            utm_id: '',
            fbclid: '',
        });
        const loading = ref(false);
        const errorMessage = ref('');

        // Captura parâmetros da URL ao abrir o modal
        onMounted(() => {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Código de referência
            const refCode = urlParams.get('ref');
            if (refCode) {
                form.value.referral_code = refCode;
            }
            
            // Parâmetros de tracking (captura tanto do URL quanto do localStorage)
            // Prioridade: URL > localStorage
            const clickIdFromUrl = urlParams.get('click_id') || urlParams.get('clickid');
            const kwaiClickIdFromUrl = urlParams.get('kwai_click_id');
            const clickIdFromStorage = localStorage.getItem('click_id') || localStorage.getItem('kwai_click_id');
            
            // O click_id do link do Kwai deve ser salvo como kwai_click_id
            form.value.kwai_click_id = kwaiClickIdFromUrl || clickIdFromUrl || clickIdFromStorage || '';
            form.value.click_id = clickIdFromUrl || clickIdFromStorage || '';
            form.value.pixel_id = urlParams.get('pixel_id') || urlParams.get('pixelid') || urlParams.get('kwaiId') || urlParams.get('kwai_id') || localStorage.getItem('pixel_id') || '';
            form.value.campaign_id = urlParams.get('CampaignID') || urlParams.get('campaign_id') || urlParams.get('CampaignId') || localStorage.getItem('campaign_id') || '';
            form.value.adset_id = urlParams.get('adSETID') || urlParams.get('adset_id') || urlParams.get('AdsetId') || localStorage.getItem('adset_id') || '';
            form.value.creative_id = urlParams.get('CreativeID') || urlParams.get('creative_id') || urlParams.get('CreativeId') || localStorage.getItem('creative_id') || '';
            form.value.utm_source = urlParams.get('utm_source') || '';
            form.value.utm_campaign = urlParams.get('utm_campaign') || '';
            form.value.utm_medium = urlParams.get('utm_medium') || '';
            form.value.utm_content = urlParams.get('utm_content') || '';
            form.value.utm_term = urlParams.get('utm_term') || '';
            form.value.utm_id = urlParams.get('utm_id') || '';
            form.value.fbclid = urlParams.get('fbclid') || '';
            
            // Também tenta pegar do localStorage (caso tenha sido salvo anteriormente)
            const savedClickId = localStorage.getItem('click_id');
            const savedPixelId = localStorage.getItem('pixel_id');
            if (savedClickId && !form.value.click_id) {
                form.value.click_id = savedClickId;
            }
            if (savedPixelId && !form.value.pixel_id) {
                form.value.pixel_id = savedPixelId;
            }
            
            // Salva no localStorage para uso futuro
            if (form.value.click_id) {
                localStorage.setItem('click_id', form.value.click_id);
            }
            if (form.value.pixel_id) {
                localStorage.setItem('pixel_id', form.value.pixel_id);
            }
        });

        const formatPhone = (event) => {
            let value = event.target.value.replace(/\D/g, '');
            // Remove o código do país se o usuário digitou
            if (value.startsWith('55')) {
                value = value.substring(2);
            }
            // Limita a 11 dígitos (DDD + número)
            if (value.length <= 11) {
                if (value.length <= 10) {
                    // Formato: (00) 0000-0000
                    value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
                } else {
                    // Formato: (00) 00000-0000
                    value = value.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
                }
                form.value.phone = value;
            }
        };

        const handleRegister = async () => {
            errorMessage.value = '';

            // Validações
            if (form.value.password.length < 8) {
                errorMessage.value = 'A senha deve ter no mínimo 8 caracteres!';
                return;
            }

            // Adiciona o código do país ao telefone antes de enviar
            const phoneDigits = form.value.phone.replace(/\D/g, '');
            const phoneWithCountryCode = phoneDigits ? `+55${phoneDigits}` : '';

            loading.value = true;

            try {
                const response = await window.csrfHelper.fetchWithCsrf('/register', {
                    method: 'POST',
                    body: JSON.stringify({
                        ...form.value,
                        phone: phoneWithCountryCode,
                    }),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    if (window.showSuccessToast) {
                        window.showSuccessToast('Conta criada com sucesso!');
                    } else if (window.Notiflix) {
                        window.Notiflix.Notify.success('🎄 Conta criada com sucesso!');
                    }
                    emit('registered', data);
                    emit('close');
                } else {
                    errorMessage.value = data.message || 'Erro ao criar conta. Tente novamente.';
                }
            } catch (error) {
                errorMessage.value = 'Erro ao conectar com o servidor. Tente novamente.';
                console.error('Erro no registro:', error);
            } finally {
                loading.value = false;
            }
        };

        return {
            form,
            loading,
            errorMessage,
            formatPhone,
            handleRegister,
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

.phone-input-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
}

.country-code {
    background-color: var(--cor-fundo-input);
    border: 1px solid #444;
    border-radius: 6px;
    padding: 0.75rem;
    color: var(--cor-texto);
    font-weight: 500;
    white-space: nowrap;
}

.phone-input {
    flex: 1;
}
</style>

