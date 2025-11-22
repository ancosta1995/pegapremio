<template>
    <div class="page-container">
        <div class="page-header">
            <h2 class="page-title">Afiliados</h2>
        </div>
        <div class="page-content">
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
                <div v-if="commissionHistoryLoading" class="empty-state">Carregando...</div>
                <div v-else-if="commissionHistory.length === 0" class="empty-state">
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
</template>

<script>
import { ref, onMounted, watch, computed } from 'vue';
import { useApi } from '../../composables/useApi.js';

export default {
    name: 'AffiliatePage',
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
        const { internalApiRequest } = useApi();
        
        const activeTab = ref('stats');
        const affiliateStats = ref({
            totalEarned: 0,
            referrals: 0,
        });
        const referralLink = computed(() => {
            if (props.referralCode) {
                // Sempre usa a raiz do site para o link de referência
                return window.location.origin + '/?ref=' + props.referralCode;
            }
            return '';
        });
        const commissionHistory = ref([]);
        const commissionHistoryLoading = ref(false);

        const formatAmount = (value) => {
            return parseFloat(value).toFixed(2).replace('.', ',');
        };

        const formatTime = (dateString) => {
            if (!dateString) return '-';
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                });
            } catch (e) {
                return dateString;
            }
        };

        const copyReferralLink = () => {
            navigator.clipboard.writeText(referralLink.value).then(() => {
                    if (window.showSuccessToast) {
                        window.showSuccessToast('Link copiado!');
                    } else if (window.Notiflix) {
                        window.Notiflix.Notify.success('🎄 Link copiado!');
                    }
            }).catch(() => {
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

.modal-tabs {
    display: flex;
    background-color: var(--cor-fundo-input);
    border-radius: 12px;
    padding: 0.5rem;
    margin-bottom: 2rem;
    gap: 0.5rem;
}

.modal-tab {
    flex: 1;
    padding: 0.75rem 1rem;
    background: transparent;
    border: none;
    color: var(--cor-texto-secundaria);
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.2s;
}

.modal-tab:hover {
    background: rgba(255, 255, 255, 0.05);
}

.modal-tab.active {
    color: white;
    background: linear-gradient(to bottom, var(--cor-principal), var(--cor-principal-dark));
}

.modal-tab-content {
    display: none;
}

.modal-tab-content.active {
    display: block;
    padding-top: 1rem;
}

.affiliate-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
}

.affiliate-stats .stat-card-full {
    grid-column: 1 / -1;
    max-width: 250px;
    margin: 1rem auto 0;
}

.stat-card {
    background-color: var(--cor-fundo-input);
    padding: 1.5rem 1rem;
    border-radius: 12px;
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.stat-card .label {
    font-size: 0.85rem;
    color: var(--cor-texto-secundaria);
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.stat-card .value {
    font-size: 1.4rem;
    font-weight: bold;
    color: var(--cor-texto);
}

.stat-card.stat-card-full .value {
    font-size: 1.8rem;
    color: var(--cor-principal);
}

.ref-link-section {
    margin: 2rem 0 1.5rem;
    padding: 1.5rem;
    background-color: var(--cor-fundo-input);
    border-radius: 12px;
}

.ref-link-label {
    font-size: 0.95rem;
    color: var(--cor-texto-secundaria);
    margin-bottom: 1rem;
    font-weight: 500;
    text-align: center;
}

.ref-link-box {
    background: rgba(0, 0, 0, 0.3);
    border: 2px dashed rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    padding: 1rem;
    word-break: break-all;
    color: #fff;
    text-align: center;
    font-size: 0.9rem;
    font-family: monospace;
    line-height: 1.5;
    transition: border-color 0.2s;
}

.ref-link-box:hover {
    border-color: var(--cor-principal);
}

.modal-button {
    width: 100%;
    padding: 1rem;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    font-size: 1rem;
    color: white;
    background-image: linear-gradient(to bottom, var(--cor-principal), var(--cor-principal-dark));
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.modal-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(220, 38, 38, 0.4);
}

.modal-button:active {
    transform: translateY(0);
}

.commission-history-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
    background-color: var(--cor-fundo-input);
    border-radius: 12px;
    overflow: hidden;
}

.commission-history-table thead {
    background-color: rgba(0, 0, 0, 0.3);
}

.commission-history-table th {
    padding: 1rem;
    text-align: left;
    color: var(--cor-texto-secundaria);
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
}

.commission-history-table td {
    padding: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--cor-texto);
}

.commission-history-table tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.05);
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--cor-texto-secundaria);
}

.empty-state p {
    margin: 0;
    font-size: 1rem;
}
</style>

