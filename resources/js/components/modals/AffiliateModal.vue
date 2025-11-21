<template>
    <div class="modal-overlay active" @click.self="$emit('close')">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Afiliados</h3>
                <button class="modal-close" @click="$emit('close')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-tabs">
                    <button
                        :class="['modal-tab', { active: activeTab === 'stats' }]"
                        @click="activeTab = 'stats'"
                    >
                        Estatísticas
                    </button>
                    <button
                        :class="['modal-tab', { active: activeTab === 'history' }]"
                        @click="activeTab = 'history'"
                    >
                        Histórico
                    </button>
                </div>

                <div v-if="activeTab === 'stats'" class="modal-tab-content active">
                    <div class="affiliate-stats">
                        <div class="stat-card">
                            <div class="label">Saldo de Afiliado</div>
                            <div class="value">R$ {{ formatAmount(balanceRef || 0) }}</div>
                        </div>
                        <div class="stat-card">
                            <div class="label">Ganho por Referido</div>
                            <div class="value">R$ {{ formatAmount(cpa || 0) }}</div>
                        </div>
                        <div class="stat-card stat-card-full">
                            <div class="label">Referidos</div>
                            <div class="value">{{ affiliateStats.referrals || 0 }}</div>
                        </div>
                    </div>
                    <div class="ref-link-section">
                        <div class="ref-link-label">Seu Link de Afiliado:</div>
                        <div class="ref-link-box">
                            {{ referralLink }}
                        </div>
                    </div>
                    <button class="modal-button" @click="copyReferralLink">📋 Copiar Link</button>
                </div>

                <div v-else class="modal-tab-content active">
                    <div v-if="commissionHistoryLoading">Carregando...</div>
                    <div v-else-if="commissionHistory.length === 0">
                        <p>Sem histórico de comissões.</p>
                    </div>
                    <table v-else class="commission-history-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Usuário</th>
                                <th style="text-align: right;">Comissão</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in commissionHistory" :key="index">
                                <td>{{ formatTime(item.data) }}</td>
                                <td>{{ item.usuario }}</td>
                                <td style="text-align:right">R$ {{ formatAmount(item.comissao) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, watch, computed } from 'vue';

export default {
    name: 'AffiliateModal',
    props: {
        balanceRef: {
            type: Number,
            default: 0,
        },
        referralCode: {
            type: String,
            default: '',
        },
        cpa: {
            type: Number,
            default: 0,
        },
    },
    setup(props) {
        const activeTab = ref('stats');
        const affiliateStats = ref({
            totalEarned: 0,
            referrals: 0,
        });
        const referralLink = computed(() => {
            if (props.referralCode) {
                return window.location.origin + window.location.pathname + '?ref=' + props.referralCode;
            }
            return '';
        });
        const commissionHistory = ref([]);
        const commissionHistoryLoading = ref(false);

        const internalApiRequest = async (action, data = {}) => {
            const params = new URLSearchParams();
            params.append('action', action);
            for (const key in data) {
                params.append(key, data[key]);
            }
            const response = await fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params,
            });
            if (!response.ok) {
                throw new Error('Erro de rede.');
            }
            return response.json();
        };

        const formatAmount = (value) => {
            return parseFloat(value).toFixed(2).replace('.', ',');
        };

        const formatTime = (dateString) => {
            return new Date(dateString).toLocaleTimeString('pt-BR');
        };

        const copyReferralLink = () => {
            navigator.clipboard.writeText(referralLink.value).then(() => {
                    if (window.showSuccessToast) {
                        window.showSuccessToast('Link copiado!');
                    } else if (window.Notiflix) {
                        window.Notiflix.Notify.success('🎄 Link copiado!');
                    }
            }).catch(() => {
                // Fallback para navegadores mais antigos
                const textArea = document.createElement('textarea');
                textArea.value = referralLink.value;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                if (window.showSuccessToast) {
                    window.showSuccessToast('Link copiado!');
                } else if (window.Notiflix) {
                    window.Notiflix.Notify.success('🎄 Link copiado!');
                }
            });
        };

        const loadAffiliateData = async () => {
            try {
                const res = await internalApiRequest('get_affiliate_data');
                if (res.success) {
                    affiliateStats.value = res.stats || affiliateStats.value;
                }
            } catch (e) {
                console.error('Erro ao carregar dados de afiliado:', e);
            }
        };

        const loadCommissionHistory = async () => {
            if (commissionHistory.value.length > 0) return;
            commissionHistoryLoading.value = true;
            try {
                const res = await internalApiRequest('get_commission_history');
                if (res.success && res.history) {
                    commissionHistory.value = res.history;
                }
            } catch (e) {
                console.error('Erro ao carregar histórico:', e);
            } finally {
                commissionHistoryLoading.value = false;
            }
        };

        watch(activeTab, (newTab) => {
            if (newTab === 'history') {
                loadCommissionHistory();
            }
        });

        onMounted(() => {
            loadAffiliateData();
        });

        return {
            activeTab,
            affiliateStats,
            referralLink,
            commissionHistory,
            commissionHistoryLoading,
            formatAmount,
            formatTime,
            copyReferralLink,
            loadCommissionHistory,
            balanceRef: computed(() => props.balanceRef),
            cpa: computed(() => props.cpa),
        };
    },
};
</script>

