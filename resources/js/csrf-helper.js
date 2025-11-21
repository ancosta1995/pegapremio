// Helper global para gerenciar CSRF Token
window.csrfHelper = {
    // Obtém o token CSRF atual
    getToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    },

    // Atualiza o token na meta tag
    updateToken(newToken) {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            meta.setAttribute('content', newToken);
        }
    },

    // Obtém um novo token do servidor
    async refreshToken() {
        try {
            const response = await fetch('/api/csrf-token', {
                method: 'GET',
                credentials: 'same-origin',
            });

            if (response.ok) {
                const data = await response.json();
                if (data.token) {
                    this.updateToken(data.token);
                    return data.token;
                }
            }
        } catch (error) {
            console.error('Erro ao atualizar CSRF token:', error);
        }
        return null;
    },

    // Faz uma requisição com retry automático em caso de erro 419
    async fetchWithCsrf(url, options = {}) {
        const token = this.getToken();
        
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        };

        const finalOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...(options.headers || {}),
            },
        };

        let response = await fetch(url, finalOptions);

        // Se for erro 419 (CSRF token expirado), tenta atualizar e refazer
        if (response.status === 419) {
            console.log('CSRF token expirado, tentando atualizar...');
            const newToken = await this.refreshToken();
            
            if (newToken) {
                // Atualiza o token no header e refaz a requisição
                finalOptions.headers['X-CSRF-TOKEN'] = newToken;
                response = await fetch(url, finalOptions);
            } else {
                // Se não conseguir atualizar, recarrega a página
                console.error('Não foi possível atualizar o CSRF token, recarregando página...');
                window.location.reload();
                throw new Error('Sessão expirada');
            }
        }

        return response;
    }
};

