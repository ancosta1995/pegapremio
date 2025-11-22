/**
 * Sistema de Proteção do Frontend
 * Implementa: Domain Locking, Request Signing, Environment Detection
 */

// 1. Domain Locking - Verifica se está rodando no domínio permitido
const ALLOWED_DOMAINS = [
    window.location.hostname, // Domínio atual
    // Adicione outros domínios permitidos aqui se necessário
    // 'seu-dominio.com',
    // 'www.seu-dominio.com',
];

const ALLOWED_ORIGINS = ALLOWED_DOMAINS.map(domain => {
    const protocol = window.location.protocol;
    return `${protocol}//${domain}`;
});

/**
 * Verifica se o domínio atual é permitido
 */
export function validateDomain() {
    const currentHost = window.location.hostname;
    const currentOrigin = window.location.origin;
    
    // Verifica se o domínio está na lista permitida
    const isAllowed = ALLOWED_DOMAINS.some(domain => {
        return currentHost === domain || currentHost.endsWith('.' + domain);
    });
    
    if (!isAllowed) {
        console.error('🚫 Acesso negado: Domínio não autorizado');
        // Bloqueia a execução
        document.body.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100vh;font-family:Arial;color:#ef4444;"><h1>Acesso Negado</h1></div>';
        throw new Error('Domínio não autorizado');
    }
    
    return true;
}

/**
 * Gera uma assinatura para requisições
 */
export function generateRequestSignature(data, timestamp) {
    // Usa uma chave secreta (em produção, isso deve vir do servidor)
    const secretKey = btoa(window.location.hostname + ':' + timestamp).substring(0, 16);
    
    // Ordena os dados para garantir consistência com o backend
    const sortedData = {};
    Object.keys(data).sort().forEach(key => {
        sortedData[key] = data[key];
    });
    
    // Cria um hash simples (em produção, use uma função de hash mais segura)
    const payload = JSON.stringify(sortedData) + timestamp + secretKey;
    let hash = 0;
    for (let i = 0; i < payload.length; i++) {
        const char = payload.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash; // Convert to 32bit integer
    }
    
    return Math.abs(hash).toString(16);
}

/**
 * Verifica o ambiente e detecta DevTools, headless browsers, etc.
 */
export function detectSuspiciousEnvironment() {
    const warnings = [];
    
    // Detecta DevTools aberto
    let devtools = { open: false };
    const element = new Image();
    Object.defineProperty(element, 'id', {
        get: function() {
            devtools.open = true;
            warnings.push('DevTools detectado');
        }
    });
    
    // Tenta detectar DevTools
    setInterval(() => {
        devtools.open = false;
        console.log(element);
        if (devtools.open) {
            warnings.push('DevTools detectado');
        }
    }, 1000);
    
    // Detecta headless browsers
    const isHeadless = 
        !window.chrome ||
        navigator.webdriver ||
        window.outerHeight === 0 ||
        window.outerWidth === 0 ||
        navigator.plugins.length === 0 ||
        navigator.languages.length === 0;
    
    if (isHeadless) {
        warnings.push('Possível navegador headless detectado');
    }
    
    // Detecta se está rodando em iframe (possível clonagem)
    if (window.self !== window.top) {
        warnings.push('Executando em iframe');
    }
    
    // Detecta extensões de desenvolvedor
    const hasDevExtensions = 
        window.__REACT_DEVTOOLS_GLOBAL_HOOK__ ||
        window.__VUE_DEVTOOLS_GLOBAL_HOOK__ ||
        window.__REDUX_DEVTOOLS_EXTENSION__;
    
    if (hasDevExtensions) {
        warnings.push('Extensões de desenvolvedor detectadas');
    }
    
    // Detecta se o console foi sobrescrito
    const originalConsole = window.console;
    if (originalConsole.toString().indexOf('native code') === -1) {
        warnings.push('Console foi modificado');
    }
    
    // Se houver muitas suspeitas, bloqueia
    if (warnings.length >= 2) {
        console.error('🚫 Ambiente suspeito detectado:', warnings);
        // Pode bloquear ou apenas logar
        return false;
    }
    
    return true;
}

/**
 * Inicializa todas as proteções
 */
export function initializeSecurity() {
    try {
        // 1. Valida domínio
        validateDomain();
        
        // 2. Detecta ambiente suspeito
        detectSuspiciousEnvironment();
        
        // 3. Protege contra debug
        let devtools = false;
        const element = new Image();
        Object.defineProperty(element, 'id', {
            get: function() {
                devtools = true;
            }
        });
        
        setInterval(() => {
            devtools = false;
            console.log(element);
            if (devtools) {
                // Bloqueia ou redireciona
                window.location.href = 'about:blank';
            }
        }, 1000);
        
        // 4. Protege contra cópia
        document.addEventListener('copy', (e) => {
            e.clipboardData.setData('text/plain', '');
            e.preventDefault();
        });
        
        // 5. Protege contra seleção
        document.addEventListener('selectstart', (e) => {
            e.preventDefault();
        });
        
        // 6. Desabilita botão direito
        document.addEventListener('contextmenu', (e) => {
            e.preventDefault();
        });
        
        // 7. Desabilita F12, Ctrl+Shift+I, etc.
        document.addEventListener('keydown', (e) => {
            // F12
            if (e.keyCode === 123) {
                e.preventDefault();
                return false;
            }
            // Ctrl+Shift+I
            if (e.ctrlKey && e.shiftKey && e.keyCode === 73) {
                e.preventDefault();
                return false;
            }
            // Ctrl+Shift+J
            if (e.ctrlKey && e.shiftKey && e.keyCode === 74) {
                e.preventDefault();
                return false;
            }
            // Ctrl+U
            if (e.ctrlKey && e.keyCode === 85) {
                e.preventDefault();
                return false;
            }
            // Ctrl+S
            if (e.ctrlKey && e.keyCode === 83) {
                e.preventDefault();
                return false;
            }
        });
        
        return true;
    } catch (error) {
        console.error('Erro ao inicializar proteções:', error);
        return false;
    }
}

