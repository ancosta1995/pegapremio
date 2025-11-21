class HeaderComponent {
    constructor(basePath = '') {
        this.basePath = basePath;
        this.apiPath = this.basePath ? `${this.basePath}/api` : '/api';
    }

    render() {
        return `
            <header class="header">
                <div class="nav-container">
                    <a href="${this.basePath}/" class="logo">RASPE<span>PIX</span></a>
                    <nav class="nav-menu">
                        <a href="${this.basePath}/">Início</a>
                        <a href="${this.basePath}/jogo/3.html">Raspadinhas</a>
                    </nav>
                    <div id="authButtons" class="nav-buttons">
                        <a href="#" class="btn btn-outline" onclick="toggleModalLogin(true); return false;">Entrar</a>
                        <a href="#" class="btn btn-primary" onclick="toggleModalCadastro(true); return false;">Registrar</a>
                    </div>

                    <div id="userInfo" style="display:none; align-items: center; gap: 10px;">
                        <button class="btn btn-primary" onclick="window.location.href='${this.basePath}/saque/'">💲 Sacar</button>
                        <div class="user-menu">
                            <button id="userMenuBtn" class="user-menu-btn">
                                <span class="user-icon">👤</span>
                                <span class="dropdown-arrow">▼</span>
                            </button>
                            <div id="userDropdown" class="user-dropdown">
                                <div class="user-balance">
                                    <span>Saldo: R$ <span id="userBalance">0,00</span></span>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a href="${this.basePath}/perfil/" class="dropdown-item">Perfil</a>
                                <a href="${this.basePath}/historico/" class="dropdown-item">Histórico</a>
                                <a href="${this.basePath}/deposito/" class="dropdown-item">💰 Depositar</a>
                                <a href="${this.apiPath}/logout.php" class="dropdown-item">Sair</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
        `;
    }

    renderModals() {
        return `
            <!-- Modal Cadastro -->
            <div id="modalCadastro" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
                background: rgba(0,0,0,0.7); justify-content:center; align-items:center; z-index:10000;">
                <div style="background:#222; padding:30px; border-radius:8px; max-width:350px; width:90%; color:#fff; position:relative;">
                    <button onclick="toggleModalCadastro(false)" style="position:absolute; top:10px; right:10px; font-size:20px; background:none; border:none; color:#fff; cursor:pointer;">×</button>
                    <h2 style="margin-bottom:15px;">Cadastro</h2>
                    <form id="formCadastro">
                        <input type="text" id="cadUsername" placeholder="Usuário" required style="width:100%; padding:8px; margin-bottom:10px; border-radius:4px; border:none;">
                        <input type="email" id="cadEmail" placeholder="E-mail" required style="width:100%; padding:8px; margin-bottom:10px; border-radius:4px; border:none;">
                        <input type="password" id="cadSenha" placeholder="Senha" required style="width:100%; padding:8px; margin-bottom:10px; border-radius:4px; border:none;">
                        <button type="submit" style="width:100%; background:#4CAF50; border:none; padding:10px; color:#fff; font-weight:700; border-radius:4px; cursor:pointer;">Registrar</button>
                    </form>
                    <div id="cadMsg" style="margin-top:10px; font-size:14px; color:#f44336;"></div>
                </div>
            </div>

            <!-- Modal Login -->
            <div id="modalLogin" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
                background: rgba(0,0,0,0.7); justify-content:center; align-items:center; z-index:10000;">
                <div style="background:#222; padding:30px; border-radius:8px; max-width:350px; width:90%; color:#fff; position:relative;">
                    <button onclick="toggleModalLogin(false)" style="position:absolute; top:10px; right:10px; font-size:20px; background:none; border:none; color:#fff; cursor:pointer;">×</button>
                    <h2 style="margin-bottom:15px;">Entrar</h2>
                    <form id="formLogin">
                        <input type="text" id="loginUsername" placeholder="Usuário" required style="width:100%; padding:8px; margin-bottom:10px; border-radius:4px; border:none;">
                        <input type="password" id="loginSenha" placeholder="Senha" required style="width:100%; padding:8px; margin-bottom:10px; border-radius:4px; border:none;">
                        <button type="submit" style="width:100%; background:#4CAF50; border:none; padding:10px; color:#fff; font-weight:700; border-radius:4px; cursor:pointer;">Entrar</button>
                    </form>
                    <div id="loginMsg" style="margin-top:10px; font-size:14px; color:#f44336;"></div>
                </div>
            </div>
        `;
    }

    init() {
        const headerContainer = document.getElementById('header-container');
        if (headerContainer) {
            headerContainer.innerHTML = this.render();
        } else {
            document.body.insertAdjacentHTML('afterbegin', this.render());
        }

        document.body.insertAdjacentHTML('beforeend', this.renderModals());

        this.setupEventListeners();
        this.setupAuth();
        this.setupUserMenu();

        this.fetchUserInfo();
    }

    setupEventListeners() {
        const formCadastro = document.getElementById('formCadastro');
        const formLogin = document.getElementById('formLogin');

        if (formCadastro) {
            formCadastro.addEventListener('submit', this.handleCadastro.bind(this));
        }

        if (formLogin) {
            formLogin.addEventListener('submit', this.handleLogin.bind(this));
        }
    }

    setupUserMenu() {
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userDropdown = document.getElementById('userDropdown');
        const userMenu = document.querySelector('.user-menu');

        if (userMenuBtn && userDropdown) {
            userMenuBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('show');
                userMenu.classList.toggle('active');
            });

            document.addEventListener('click', function(e) {
                if (userMenu && !userMenu.contains(e.target)) {
                    userDropdown.classList.remove('show');
                    userMenu.classList.remove('active');
                }
            });
        }
    }

    setupAuth() {
        window.toggleModalLogin = (show) => {
            const modal = document.getElementById('modalLogin');
            modal.style.display = show ? 'flex' : 'none';
        };

        window.toggleModalCadastro = (show) => {
            const modal = document.getElementById('modalCadastro');
            modal.style.display = show ? 'flex' : 'none';
        };

        window.getURLParameters = () => {
            const params = new URLSearchParams(window.location.search);
            return {
                click_id: params.get('click_id') || '',
                pixel_id: params.get('pixel_id') || '',
                campaign_id: params.get('CampaignID') || '',
                adset_id: params.get('adSETID') || '',
                creative_id: params.get('CreativeID') || '',
                utm_source: params.get('utm_source') || '',
                utm_campaign: params.get('utm_campaign') || '',
                utm_medium: params.get('utm_medium') || '',
                utm_content: params.get('utm_content') || '',
                utm_term: params.get('utm_term') || '',
                utm_id: params.get('utm_id') || '',
                fbclid: params.get('fbclid') || ''
            };
        };
    }

    async handleCadastro(e) {
        e.preventDefault();
        const username = document.getElementById('cadUsername').value.trim();
        const email = document.getElementById('cadEmail').value.trim();
        const senha = document.getElementById('cadSenha').value;

        const trafficParams = window.getURLParameters();

        const msgDiv = document.getElementById('cadMsg');
        msgDiv.textContent = '';

        try {
            const response = await fetch(`${this.apiPath}/cadastro.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username, email, senha,
                    ...trafficParams
                })
            });

            const result = await response.json();

            if (response.ok) {
                msgDiv.style.color = '#4CAF50';
                msgDiv.textContent = 'Cadastro realizado com sucesso! Efetuando login...';

                const loginResponse = await fetch(`${this.apiPath}/login.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ username, senha })
                });

                const loginResult = await loginResponse.json();

                if (loginResponse.ok) {
                    setTimeout(() => {
                        window.toggleModalCadastro(false);
                        this.fetchUserInfo();

                        setTimeout(() => {
                            if (typeof showWelcomeModal === 'function') {
                                showWelcomeModal(username);
                            }
                        }, 800);

                    }, 1000);
                } else {
                    msgDiv.style.color = '#f44336';
                    msgDiv.textContent = 'Cadastro OK, mas falha no login automático. Por favor, entre manualmente.';
                }

            } else {
                msgDiv.style.color = '#f44336';
                msgDiv.textContent = result.erro || 'Erro no cadastro.';
            }
        } catch (error) {
            msgDiv.style.color = '#f44336';
            msgDiv.textContent = 'Erro ao conectar ao servidor.';
            console.error(error);
        }
    }

    async handleLogin(e) {
        e.preventDefault();
        const username = document.getElementById('loginUsername').value.trim();
        const senha = document.getElementById('loginSenha').value;

        const msgDiv = document.getElementById('loginMsg');
        msgDiv.textContent = '';

        try {
            const response = await fetch(`${this.apiPath}/login.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ username, senha })
            });

            const result = await response.json();

            if (response.ok) {
                msgDiv.style.color = '#4CAF50';
                msgDiv.textContent = 'Login realizado com sucesso!';
                setTimeout(() => {
                    window.toggleModalLogin(false);
                    this.fetchUserInfo();
                }, 1500);
            } else {
                msgDiv.style.color = '#f44336';
                msgDiv.textContent = result.erro || 'Usuário ou senha inválidos.';
            }
        } catch (error) {
            msgDiv.style.color = '#f44336';
            msgDiv.textContent = 'Erro ao conectar ao servidor.';
            console.error(error);
        }
    }

    async fetchUserInfo() {
        try {
            const res = await fetch(`${this.apiPath}/user_info.php`, { credentials: 'include' });
            if (!res.ok) throw new Error('Não autenticado');
            const data = await res.json();
            document.getElementById('authButtons').style.display = 'none';
            document.getElementById('userInfo').style.display = 'flex';
            if (data.saldo) {
                document.getElementById('userBalance').textContent = data.saldo;
            }
        } catch {
            document.getElementById('authButtons').style.display = 'flex';
            document.getElementById('userInfo').style.display = 'none';
        }
    }

    async logout() {
        try {
            const res = await fetch(`${this.apiPath}/logout.php`, {
                method: 'POST',
                credentials: 'include',
            });
            if (!res.ok) throw new Error('Erro no logout');
            this.fetchUserInfo();
        } catch (e) {
            alert('Erro ao sair');
        }
    }
}

function initHeader(basePath = '') {
    const header = new HeaderComponent(basePath);
    header.init();
    return header;
}

window.initHeader = initHeader;