import { ref } from 'vue';

/**
 * Composable para navegação entre páginas
 */
export function useNavigation(isUserLoggedIn) {
    const currentPage = ref('game'); // 'game', 'profile', 'wallet', 'affiliate'
    
    // Mapeamento de rotas
    const routeMap = {
        '/': 'game',
        '/perfil': 'profile',
        '/carteira': 'wallet',
        '/afiliados': 'affiliate',
    };
    
    // Mapeamento reverso (página -> rota)
    const pageToRoute = {
        'game': '/',
        'profile': '/perfil',
        'wallet': '/carteira',
        'affiliate': '/afiliados',
    };
    
    // Função para ler a rota atual da URL
    const getPageFromRoute = () => {
        const path = window.location.pathname;
        return routeMap[path] || 'game';
    };
    
    // Inicializa a página baseada na URL atual
    const initializeRoute = (openRegisterModal) => {
        const page = getPageFromRoute();
        
        // Verifica se a página requer autenticação
        if (!isUserLoggedIn.value && page !== 'game') {
            // Se não estiver logado e tentar acessar página protegida, redireciona para o jogo
            currentPage.value = 'game';
            const route = pageToRoute['game'] || '/';
            window.history.replaceState({ page: 'game' }, '', route);
            return;
        }
        
        currentPage.value = page;
        
        // Define o estado inicial do histórico se não existir
        if (!window.history.state || !window.history.state.page) {
            window.history.replaceState({ page }, '', pageToRoute[page] || '/');
        }
    };
    
    // Listener para mudanças no histórico do navegador (botão voltar/avançar)
    const handlePopState = (event, openRegisterModal) => {
        const page = event.state?.page || getPageFromRoute();
        
        // Verifica se a página requer autenticação
        if (!isUserLoggedIn.value && page !== 'game') {
            // Se não estiver logado e tentar acessar página protegida, redireciona para o jogo
            currentPage.value = 'game';
            const route = pageToRoute['game'] || '/';
            window.history.replaceState({ page: 'game' }, '', route);
            if (openRegisterModal) openRegisterModal();
            return;
        }
        
        currentPage.value = page;
    };
    
    const navigateTo = (page, openRegisterModal) => {
        // Apenas a página 'game' é acessível sem login
        if (!isUserLoggedIn.value && page !== 'game') {
            if (openRegisterModal) openRegisterModal();
            // Se estiver tentando acessar uma página protegida, volta para o jogo
            if (currentPage.value !== 'game') {
                currentPage.value = 'game';
                const route = pageToRoute['game'] || '/';
                window.history.pushState({ page: 'game' }, '', route);
            }
            return;
        }
        currentPage.value = page;
        
        // Atualiza a URL sem recarregar a página
        const route = pageToRoute[page] || '/';
        window.history.pushState({ page }, '', route);
    };
    
    return {
        currentPage,
        navigateTo,
        initializeRoute,
        handlePopState,
        pageToRoute,
    };
}

