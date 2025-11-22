import { ref } from 'vue';

/**
 * Composable para requisições de API
 */
export function useApi() {
    const internalApiRequest = async (action, data = {}) => {
        // Se for uma URL completa (começa com /), faz fetch direto
        if (typeof action === 'string' && action.startsWith('/')) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            
            const options = {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
            };
            
            // Se tiver data, muda para POST
            if (Object.keys(data).length > 0) {
                options.method = 'POST';
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(data);
            }
            
            const response = await fetch(action, options);
            
            if (!response.ok) {
                if (response.status === 419) {
                    window.location.reload();
                    throw new Error('Sessão expirada. Recarregando...');
                }
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Erro de rede.');
            }
            return response.json();
        }
        
        // Sistema antigo (POST com action)
        const params = new URLSearchParams();
        params.append('action', action);
        for (const key in data) {
            params.append(key, data[key]);
        }
        
        // Obtém o token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        const response = await fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: params,
        });
        
        if (!response.ok) {
            // Se for erro 419, tenta obter um novo token CSRF
            if (response.status === 419) {
                // Recarrega a página para obter um novo token
                window.location.reload();
                throw new Error('Sessão expirada. Recarregando...');
            }
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Erro de rede.');
        }
        return response.json();
    };

    const formatBalance = (value) => {
        return parseFloat(value).toFixed(2).replace('.', ',');
    };

    const formatBet = (value) => {
        return parseFloat(value).toFixed(2).replace('.', ',');
    };

    const formatTime = (dateString) => {
        return new Date(dateString).toLocaleTimeString('pt-BR');
    };

    const asset = (path) => {
        const baseUrl = window.ASSETS_BASE_URL || '';
        return baseUrl + (path.startsWith('/') ? path.substring(1) : path);
    };

    return {
        internalApiRequest,
        formatBalance,
        formatBet,
        formatTime,
        asset,
    };
}

