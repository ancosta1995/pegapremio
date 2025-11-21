<template>
    <div class="modal-overlay active" @click.self="$emit('close')">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Perfil</h3>
                <button class="modal-close" @click="$emit('close')">&times;</button>
            </div>
            <div class="modal-body">
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
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ProfileModal',
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
    methods: {
        formatBalance(value) {
            return parseFloat(value).toFixed(2).replace('.', ',');
        },
    },
};
</script>

<style scoped>
.profile-info {
    margin-bottom: 20px;
}

.profile-field {
    margin-bottom: 16px;
    padding-bottom: 16px;
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
    margin-bottom: 6px;
    font-weight: 500;
}

.profile-value {
    font-size: 1rem;
    color: var(--cor-texto);
    font-weight: 500;
}
</style>


