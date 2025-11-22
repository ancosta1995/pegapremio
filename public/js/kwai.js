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

    // Função para salvar click_id no localStorage
    function saveClickId() {
        const clickId = getUrlParameter('click_id') || getUrlParameter('kwai_click_id');
        if (clickId) {
            localStorage.setItem('kwai_click_id', clickId);
            console.log('Kwai click_id saved:', clickId);

            // Envia para backend imediatamente
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            if (csrfToken) {
                fetch('/kwai/click', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ kwai_click_id: clickId })
                }).catch(err => {
                    console.error('Erro ao enviar click_id:', err);
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
        const clickId = getClickId();
        
        if (!clickId) {
            console.log('Kwai: click_id não encontrado, evento Content View não será enviado');
            return;
        }

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
        fetch('/api/kwai/track-content-view', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                click_id: clickId,
                page: page
            })
        }).catch(err => {
            console.error('Kwai: Erro ao enviar Content View:', err);
        });
    }

    // Inicialização
    function init() {
        // Sempre salvar click_id se presente na URL
        saveClickId();
        
        // Envia evento de visualização de conteúdo quando a página carregar
        // Aguarda um pouco para garantir que o click_id foi salvo
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
