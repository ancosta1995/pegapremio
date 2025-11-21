<template>
    <div class="page-container">
        <div class="page-header">
            <h2 class="page-title">Perfil</h2>
        </div>
        <div class="page-content">
            <div class="profile-info">
                <div class="profile-field">
                    <div class="profile-label">Nome</div>
                    <div class="profile-value">{{ name || 'Não informado' }}</div>
                </div>
                <div class="profile-field">
                    <div class="profile-label">Email</div>
                    <div class="profile-value">{{ email || 'Não informado' }}</div>
                </div>
                <div class="profile-field">
                    <div class="profile-label">Telefone</div>
                    <div class="profile-value">{{ phone || 'Não informado' }}</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="label">Saldo</div>
                <div class="value">R$ {{ formatBalance(balance) }}</div>
            </div>
            <button class="modal-button" @click="$emit('withdraw')">Sacar</button>
            <button class="modal-button logout-button" @click="handleLogout">Sair</button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ProfilePage',
    props: {
        balance: {
            type: Number,
            default: 0,
        },
        name: {
            type: String,
            default: '',
        },
        email: {
            type: String,
            default: '',
        },
        phone: {
            type: String,
            default: '',
        },
    },
    emits: ['withdraw', 'logout'],
    methods: {
        formatBalance(value) {
            return parseFloat(value).toFixed(2).replace('.', ',');
        },
        handleLogout() {
            // Emite o evento para o componente pai fazer o logout e redirecionamento
            this.$emit('logout');
        },
    },
};
</script>

<style scoped>
.page-container {
    width: 100%;
    max-width: 600px;
    margin: 0 auto;
    padding: 1rem;
}

.page-header {
    margin-bottom: 2rem;
}

.page-title {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--cor-texto);
    margin: 0;
}

.page-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.profile-info {
    background-color: var(--cor-fundo-input);
    border-radius: 12px;
    padding: 1.5rem;
}

.profile-field {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.profile-field:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.profile-label {
    font-size: 0.85rem;
    color: #BFBFBF;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.profile-value {
    font-size: 1rem;
    color: var(--cor-texto);
    font-weight: 500;
}

.stat-card {
    background-color: var(--cor-fundo-input);
    padding: 1.5rem;
    border-radius: 12px;
    text-align: center;
}

.stat-card .label {
    font-size: 0.9rem;
    color: var(--cor-texto-secundaria);
    margin-bottom: 0.5rem;
}

.stat-card .value {
    font-size: 1.8rem;
    font-weight: bold;
    color: #22c55e;
}

.modal-button {
    width: 100%;
    padding: 1rem;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    font-size: 1rem;
    color: white;
    background-image: linear-gradient(to bottom, var(--cor-principal), var(--cor-principal-dark));
}

.logout-button {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    margin-top: 0.5rem;
}

.logout-button:hover {
    background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
}
</style>

