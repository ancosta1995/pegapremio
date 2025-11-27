/**
 * Kwai Event API Tracker
 * Implementação do tracking de eventos via Kwai Event API
 */

(function() {
    'use strict';

    // Função para obter parâmetro da URL
    function getUrlParameter(name) {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        const results = regex.exec(location.search);
        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
    }

    // Função para salvar todos os parâmetros do Kwai
    function saveKwaiParams() {
        // Captura click_id (pode vir como click_id ou kwai_click_id)
        const clickId = getUrlParameter('click_id') || getUrlParameter('kwai_click_id');
        
        // Captura outros parâmetros do Kwai (com diferentes nomes na URL)
        const kwaiId = getUrlParameter('kwaiId') || getUrlParameter('kwai_id');
        const pixelId = getUrlParameter('pixel_id') || kwaiId;
        const campaignId = getUrlParameter('CampaignID') || getUrlParameter('campaign_id') || getUrlParameter('CampaignId');
        const adsetId = getUrlParameter('adSETID') || getUrlParameter('adset_id') || getUrlParameter('AdsetId');
        const creativeId = getUrlParameter('CreativeID') || getUrlParameter('creative_id') || getUrlParameter('CreativeId');
        
        // Salva no localStorage
        if (clickId) {
            localStorage.setItem('kwai_click_id', clickId);
            localStorage.setItem('click_id', clickId); // Também salva como click_id para compatibilidade
            console.log('Kwai click_id saved:', clickId);
        }
        
        if (pixelId) {
            localStorage.setItem('pixel_id', pixelId);
            console.log('Kwai pixel_id saved:', pixelId);
        }
        
        if (campaignId) {
            localStorage.setItem('campaign_id', campaignId);
            console.log('Kwai campaign_id saved:', campaignId);
        }
        
        if (adsetId) {
            localStorage.setItem('adset_id', adsetId);
            console.log('Kwai adset_id saved:', adsetId);
        }
        
        if (creativeId) {
            localStorage.setItem('creative_id', creativeId);
            console.log('Kwai creative_id saved:', creativeId);
        }

        // Envia para backend imediatamente (sessão)
        if (clickId) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            if (csrfToken) {
                fetch('/kwai/click', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ 
                        kwai_click_id: clickId,
                        pixel_id: pixelId || null,
                        campaign_id: campaignId || null,
                        adset_id: adsetId || null,
                        creative_id: creativeId || null
                    })
                }).catch(err => {
                    console.error('Erro ao enviar parâmetros do Kwai:', err);
                });
            }
        }
    }

    // Função para obter click_id do localStorage
    function getClickId() {
        return localStorage.getItem('kwai_click_id') || '';
    }

    // Função para enviar evento EVENT_CONTENT_VIEW para o backend
    function trackContentView(page = null) {
        // Verifica se o evento já foi enviado (localStorage como cache)
        const contentViewSent = localStorage.getItem('kwai_content_view_sent');
        if (contentViewSent === 'true') {
            console.log('Kwai: Content View já foi enviado anteriormente');
            return;
        }

        const clickId = getClickId();
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        if (!csrfToken) {
            console.warn('Kwai: CSRF token não encontrado');
            return;
        }

        // Detecta a página atual se não for fornecida
        if (!page) {
            const path = window.location.pathname;
            if (path === '/carteira' || path.includes('wallet')) {
                page = 'wallet';
            } else if (path === '/afiliados' || path.includes('affiliate')) {
                page = 'affiliate';
            } else if (path === '/perfil' || path.includes('profile')) {
                page = 'profile';
            } else if (path === '/presell' || path.includes('presell')) {
                page = 'presell';
            } else {
                page = 'home';
            }
        }

        // Envia para o backend processar
        // O backend vai usar testToken como fallback se click_id estiver vazio e estiver em modo teste
        fetch('/api/kwai/track-content-view', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                click_id: clickId || null, // Envia null se não tiver, backend vai usar testToken em modo teste
                page: page
            })
        })
        .then(response => response.json())
        .then(data => {
            // Se foi enviado com sucesso, marca no localStorage para evitar chamadas futuras
            if (data.status === 'ok' && !data.already_sent) {
                localStorage.setItem('kwai_content_view_sent', 'true');
                console.log('Kwai: Content View enviado com sucesso');
            } else if (data.already_sent) {
                // Se já foi enviado, marca no localStorage também
                localStorage.setItem('kwai_content_view_sent', 'true');
            }
        })
        .catch(err => {
            console.error('Kwai: Erro ao enviar Content View:', err);
        });
    }

    // Inicialização
    function init() {
        // Sempre salvar todos os parâmetros do Kwai se presentes na URL
        saveKwaiParams();
        
        // Envia evento de visualização de conteúdo quando a página carregar
        // Aguarda um pouco para garantir que os parâmetros foram salvos
        setTimeout(() => {
            trackContentView();
        }, 500);
    }

    // Executar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expor função globalmente para uso manual se necessário
    window.KwaiEventAPI = {
        getClickId: getClickId,
        trackContentView: trackContentView
    };

})();
