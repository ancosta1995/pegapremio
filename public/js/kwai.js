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

    // Inicialização
    function init() {
        // Sempre salvar click_id se presente na URL
        saveClickId();
    }

    // Executar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expor função globalmente para uso manual se necessário
    window.KwaiEventAPI = {
        getClickId: getClickId
    };

})();
