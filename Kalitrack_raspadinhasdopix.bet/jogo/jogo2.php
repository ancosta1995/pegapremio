<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raspou, Achou? Ganhou!</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #1a2332 0%, #2a3441 100%);
            color: white;
            min-height: 100vh;
            line-height: 1.4;
        }
        
        svg.lucide {
          width: 18px;
          height: 18px;
          stroke-width: 2;
          margin-right: 5px;
          vertical-align: middle;
        }

        .header {
            background: rgba(26, 35, 50, 0.95);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            font-size: 26px;
            font-weight: 800;
            color: #4CAF50;
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .logo span {
            color: #FFC107;
        }

        .nav-menu {
            display: flex;
            gap: 30px;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: color 0.3s;
        }

        .nav-menu a:hover {
            color: #4CAF50;
        }

        .nav-buttons {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(90deg, #32cd32, #00c853);
            color: #070b2d;
            border: 2px solid #32cd32;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #2eb82e, #00b34d);
            border-color: #2eb82e;
            color: white;
        }

        /* Menu de Usuário */
        .user-menu {
            position: relative;
            display: inline-block;
        }

        .user-menu-btn {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            color: white;
            padding: 8px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 14px;
            transition: all 0.3s;
        }

        .user-menu-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .dropdown-arrow {
            font-size: 10px;
            transition: transform 0.3s;
        }

        .user-menu.active .dropdown-arrow {
            transform: rotate(180deg);
        }

        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(26, 35, 50, 0.98);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            min-width: 200px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s;
            margin-top: 5px;
        }

        .user-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-balance-inline {
            display: flex;
            flex-direction: column;
            align-items: flex-end; 
            font-size: 14px;
            line-height: 1.2;
            margin-right: 10px;
        }
        
        .user-balance-inline div:first-child {
            color: #888;
            font-weight: normal;
            text-align: right;
        }
        
        .user-balance-inline div:last-child {
            color: #00ff88;
            font-weight: bold;
            text-align: right;
            margin-right: 0;
        }

        .dropdown-item {
            display: block;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }

        .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #4CAF50;
        }

        .dropdown-item:last-child {
            border-radius: 0 0 8px 8px;
        }

        /* Container principal */
        .main-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 70px);
            padding: 20px;
        }

        /* Modal Overlay Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal {
            background: rgba(58, 74, 92, 0.95);
            border-radius: 12px;
            width: 360px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            animation: modalAppear 0.3s ease-out;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        @keyframes modalAppear {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            color: #888;
            font-size: 20px;
            cursor: pointer;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            transition: all 0.3s;
        }

        .close-btn:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }

        .modal-header {
            background: rgba(58, 74, 92, 0.95);
            padding: 30px 20px 15px 20px;
            text-align: center;
            color: white;
        }

        .modal-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 00px;
            color: #fff;
        }

        .status-found {
            background: linear-gradient(90deg, #00e676, #00c853);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .modal-body {
            padding: 0 20px 20px 20px;
        }

        .scratch-area {
            position: relative;
            background: rgba(74, 90, 108, 0.8);
            border-radius: 8px;
            height: 280px;
            margin-bottom: 15px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .scratch-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            cursor: crosshair;
            z-index: 2;
        }

        .game-grid {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2px;
            padding: 10px;
            z-index: 1;
        }

        .grid-cell {
            background: rgba(240, 240, 240, 0.95);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .grid-cell.winning {
            background: rgba(232, 245, 232, 0.95);
            border: 2px solid #4CAF50;
        }

        .simple-message {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: white;
            text-align: center;
        }

        .simple-message h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .simple-message .emoji {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .simple-message .prize {
            font-size: 32px;
            font-weight: 700;
            color: #4CAF50;
            margin-top: 10px;
        }

        .instruction {
            text-align: center;
            color: #bbb;
            font-size: 12px;
            margin-bottom: 10px;
        }

        .balance-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.05);
            padding: 10px 15px;
            border-radius: 6px;
        }

        .balance-label {
            color: #bbb;
            font-size: 14px;
        }

        .balance-amount {
            color: #4CAF50;
            font-size: 16px;
            font-weight: 700;
        }

        .play-btn {
            background: linear-gradient(90deg, #32cd32, #00c853);
            color: #070b2d;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin: 0 auto 20px auto;
            transition: all 0.3s ease;
            gap: 8px;
            width: 80%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .play-btn:hover {
            background: linear-gradient(90deg, #2eb82e, #00b34d);
            color: white;
            transform: translateY(-1px);
        }

        .play-btn:disabled {
            background: #666;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .play-btn::before {
            content: "▶";
        }

        .keyboard-shortcuts {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 15px;
        }

        .shortcuts-title {
            color: #bbb;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .notification {
            background: rgba(240, 240, 240, 0.95);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            color: #333;
            font-size: 13px;
            line-height: 1.4;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .notification strong {
            font-weight: 600;
        }

        .notification.celebration {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
        }

        .notification.celebration::before {
            content: "🎉 ";
        }

        .notification.info::before {
            content: "💰 ";
        }

        .progress-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            z-index: 3;
        }
        
        .symbol-img {
            width: 80%;
            height: auto;
            display: block;
            margin: 0 auto;
            pointer-events: none;
            user-select: none;
        }

        .confetti-piece {
          position: fixed;
          top: -10px;
          z-index: 9999;
          border-radius: 2px;
          box-shadow: 0 0 4px rgba(0,0,0,0.2);
          will-change: transform, top;
          pointer-events: none;
        }
        
        @keyframes fall {
          0% {
            transform: translateY(0) rotate(0deg);
            opacity: 1;
          }
          100% {
            transform: translateY(100vh) rotate(720deg);
            opacity: 0;
          }
        }


        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-container {
                padding: 0 15px;
            }

            .nav-menu {
                display: none;
            }

            .nav-buttons {
                gap: 8px;
            }

            .btn {
                padding: 8px 16px;
                font-size: 13px;
            }

            .user-dropdown {
                right: -10px;
                min-width: 180px;
            }

            .main-container {
                padding: 15px;
            }

            .modal {
                width: 90%;
                max-width: 350px;
            }
        }

        @media (max-width: 480px) {
            .logo {
                font-size: 20px;
            }

            .nav-buttons .btn {
                padding: 6px 12px;
                font-size: 12px;
            }

            .user-menu-btn {
                padding: 6px 10px;
                font-size: 12px;
            }

            .main-container {
                padding: 10px;
            }

            .modal {
                width: 95%;
            }
        }
    </style>
    
        <link rel="stylesheet" href="/assets/css/deposit-success-modal.css">

</head>

<body>
<header class="header">
        <div class="nav-container">
                <a href="/" class="logo">
                  <img src="/images/logo.png" alt="Raspe Aqui" style="height: 72px;">
                </a>           
            <nav class="nav-menu">
                <a href="/"> <i data-lucide="house"></i> Início</a>
                <a href="/#raspadinhas"><i data-lucide="layout-grid"></i> Raspadinhas</a>
            </nav>
            
              <div class="nav-buttons">
                <div class="user-balance-inline">
                    <div>Saldo</div>
                    <div id="userBalance"> </div>
                </div>
            
                <div class="user-menu">
                    <button id="userMenuBtn" class="user-menu-btn">
                        <span><i data-lucide="user" style="width: 24px; height: 24px;"></i></span>
                        <span class="dropdown-arrow"><i data-lucide="chevron-down"></i></span>
                    </button>
                    <div id="userDropdown" class="user-dropdown">
                        <div class="dropdown-divider"></div>
                        <a href="/perfil/" class="dropdown-item"><i data-lucide="user-cog"></i> Perfil</a>
                        <a href="/#raspadinhas" class="dropdown-item"><i data-lucide="layout-grid"></i> Jogar</a>
                        <a href="/saque/" class="dropdown-item"><i data-lucide="banknote-arrow-down"></i> Sacar</a>
                        <a href="/historico/" class="dropdown-item"><i data-lucide="gamepad-2"></i> Histórico</a>
                        <a href="/deposito/" class="dropdown-item"><i data-lucide="banknote-arrow-up"></i> Depositar</a>
                        <a href="/api/logout.php" class="dropdown-item"><i data-lucide="log-out"></i> Sair</a>
                    </div>
                </div>

            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="modal-overlay" id="modalOverlay">
            <div class="modal">
                <button class="close-btn" onclick="closeModal()">×</button>

                <div class="modal-header">
                    <h2 class="modal-title"><i data-lucide="rocket"></i>Ache 3 imagens iguais</h2>
                    <div class="status-line">
                        <span class="status-found">Ganhe prêmios de até 5 Mil Reais!</span>
                    </div>
                </div>

                <div class="instruction" id="instruction">
                   <p><b>Compre uma raspadinha para começar a jogar</b></p>
                   <p>Clique no botão abaixo para comprar <i data-lucide="corner-right-down"></i></p>
                </div>
                
                <button class="play-btn" id="playButton" onclick="startGame()">Comprar Raspadinha</button>

                <div class="modal-body">
                    <div class="scratch-area" id="scratchArea">
                        <div class="progress-indicator" id="progressIndicator" style="display: none;">0%</div>
                        <canvas class="scratch-canvas" id="scratchCanvas" style="display: none;"></canvas>
                        <div class="game-grid" id="gameGrid"></div>
                        <div class="simple-message" id="simpleMessage" style="display: none;">
                            <h2 id="messageTitle"></h2>
                            <div class="emoji" id="messageEmoji"></div>
                            <div id="messageContent"></div>
                            <div class="prize" id="messagePrize"></div>
                        </div>
                    </div>

                    <div class="balance-section">
                        <span class="balance-label">SEU SALDO</span>
                        <span class="balance-amount" id="playerBalance">R$ 0,00</span>
                    </div>


                    <div class="notification" id="notification" style="display: none;"></div>

                    <div class="keyboard-shortcuts">
                        <div class="shortcuts-title"><i data-lucide="badge-info"></i>Atalhos para teclado:</div>
                        <div style="font-size: 12px; color: #bbb;">
                            <div><b>Espaço:</b> Jogar/Comprar</div>
                            <div><b>Enter:</b> Confirmar</div>
                            <div><b>Esc:</b> Cancelar</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
let gameState = {
    balance: 0,
    isPlaying: false,
    currentGame: null,
    scratchProgress: 0,
    config: {},
    gameId: 2,
    preGameBalance: 0,
    serverBalance: undefined
};

const elements = {
    scratchArea: document.getElementById('scratchArea'),
    scratchCanvas: document.getElementById('scratchCanvas'),
    gameGrid: document.getElementById('gameGrid'),
    simpleMessage: document.getElementById('simpleMessage'),
    messageTitle: document.getElementById('messageTitle'),
    messageEmoji: document.getElementById('messageEmoji'),
    messageContent: document.getElementById('messageContent'),
    messagePrize: document.getElementById('messagePrize'),
    instruction: document.getElementById('instruction'),
    playerBalance: document.getElementById('playerBalance'),
    playButton: document.getElementById('playButton'),
    notification: document.getElementById('notification'),
    progressIndicator: document.getElementById('progressIndicator')
};

async function loadGameConfig() {
    try {
        console.log('🔄 Carregando configurações do jogo...');
        
        const response = await fetch('/api/multi_game_logic.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'get_config',
                game_id: gameState.gameId 
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        console.log('📦 Resposta do servidor:', result);
        
        if (result.success) {
            gameState.config = result.config;
            
            updateButtonText();
            
            console.log('✅ Configurações carregadas:', gameState.config);
        } else {
            throw new Error(result.erro || 'Erro ao carregar configurações');
        }
    } catch (error) {
        console.error('❌ Erro ao carregar configurações:', error);
        showNotification('Erro ao carregar configurações: ' + error.message, 'info');
        
        gameState.config = {
            cost: 1.00,
            winChance: 0.45,
            minScratchPercent: 51,
            gameActive: true,
            symbolImages: {}
        };
        updateButtonText();
    }
}

function updateButtonText() {
    const cost = gameState.config.cost || 1.00;
    elements.playButton.textContent = `Comprar Raspadinha (R$ ${cost.toFixed(2)})`;
}

async function fetchSaldo() {
    try {
        const res = await fetch('/api/saldo_historico.php', { credentials: 'include' });
        if (!res.ok) throw new Error('Erro ao buscar saldo');
        const data = await res.json();
        
        let saldoValue = 0;
        if (data.saldo) {
            const cleanSaldo = data.saldo.toString().replace('R$', '').replace(/\s/g, '').replace(',', '.');
            saldoValue = parseFloat(cleanSaldo) || 0;
        }
        
        gameState.balance = saldoValue;
        updateBalance();
        console.log('💰 Saldo carregado:', gameState.balance);
    } catch (e) {
        console.error('❌ Erro ao carregar saldo:', e);
        showNotification('Erro ao carregar saldo', 'info');
    }
}

function createGrid(gameData) {
    console.log('🎨 Criando grid visual:', gameData);
    
    if (!gameData || !gameData.grid) {
        console.error('❌ Dados do jogo inválidos:', gameData);
        showNotification('Erro: Dados do jogo inválidos', 'info');
        return;
    }
    
    if (!Array.isArray(gameData.grid)) {
        console.error('❌ Grid não é um array:', gameData.grid);
        showNotification('Erro: Grid inválido recebido do servidor', 'info');
        return;
    }
    
    if (gameData.grid.length !== 9) {
        console.error('❌ Grid não tem 9 elementos:', gameData.grid.length);
        showNotification('Erro: Grid com tamanho incorreto', 'info');
        return;
    }
    
    elements.gameGrid.innerHTML = '';

    gameData.grid.forEach((symbolValue, index) => {
        const cell = document.createElement('div');
        cell.className = 'grid-cell';
        cell.dataset.index = index;

        const img = document.createElement('img');
        img.className = 'symbol-img';
        img.alt = `Prêmio R$ ${symbolValue}`;
        
        img.onerror = function() {
            console.warn(`⚠️ Imagem não encontrada: ${this.src} para símbolo: ${symbolValue}`);
            const textSpan = document.createElement('span');
            textSpan.textContent = `R$ ${parseFloat(symbolValue).toFixed(2)}`;
            textSpan.style.fontSize = '16px';
            textSpan.style.fontWeight = 'bold';
            textSpan.style.color = '#4CAF50';
            textSpan.style.textAlign = 'center';
            textSpan.style.display = 'flex';
            textSpan.style.alignItems = 'center';
            textSpan.style.justifyContent = 'center';
            textSpan.style.height = '100%';
            cell.removeChild(this);
            cell.appendChild(textSpan);
        };
        
        const symbolImages = gameState.config.symbolImages || {};
        let imageSrc = symbolImages[symbolValue];
        
        if (!imageSrc) {
            const numValue = parseFloat(symbolValue);
            if (numValue === 0.5) {
                imageSrc = '/images/50c.webp';
            } else if (numValue < 1) {
                imageSrc = `/images/${Math.round(numValue * 100)}c.webp`;
            } else if (numValue === Math.floor(numValue)) {
                imageSrc = `/images/${Math.floor(numValue)}-reais.webp`;
            } else {
                imageSrc = `/images/${numValue.toFixed(2).replace('.', '-')}-reais.webp`;
            }
        }
        
        img.src = imageSrc;
        
        img.onload = function() {
            console.log(`✅ Imagem carregada: ${symbolValue} -> ${imageSrc}`);
        };
        
        cell.appendChild(img);
        elements.gameGrid.appendChild(cell);
    });
    
    console.log('✅ Grid criado com sucesso:', gameData.grid.length, 'células');
}

function initCanvas() {
    const canvas = elements.scratchCanvas;
    const ctx = canvas.getContext('2d');

    canvas.width = 310;
    canvas.height = 280;

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    const gradient = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
    gradient.addColorStop(0, '#c8c8c8');
    gradient.addColorStop(0.3, '#d4d4d4');
    gradient.addColorStop(0.7, '#c0c0c0');
    gradient.addColorStop(1, '#b8b8b8');

    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    ctx.fillStyle = '#a8a8a8';
    for (let i = 0; i < canvas.width; i += 4) {
        for (let j = 0; j < canvas.height; j += 4) {
            if (Math.random() > 0.7) {
                ctx.fillRect(i, j, 2, 2);
            }
        }
    }

    ctx.fillStyle = 'rgba(0, 0, 0, 0.3)';
    ctx.font = 'bold 36px Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('RASPE AQUI', canvas.width / 2, canvas.height / 2 - 30);

    ctx.fillStyle = 'rgba(255, 255, 255, 0.15)';
    ctx.fillText('RASPE AQUI', canvas.width / 2 + 1, canvas.height / 2 - 29);

    ctx.fillStyle = 'rgba(0, 0, 0, 0.25)';
    ctx.font = 'bold 14px Arial';
    ctx.fillText("Ache 3 Iguais e GANHE na hora!", canvas.width / 2, canvas.height / 2 + 40);

    gameState.scratchProgress = 0;
    elements.progressIndicator.textContent = '0%';
}

function setupScratchEvents() {
    const canvas = elements.scratchCanvas;
    let isDrawing = false;

    const newCanvas = canvas.cloneNode(true);
    canvas.parentNode.replaceChild(newCanvas, canvas);
    elements.scratchCanvas = newCanvas;

    const ctx = newCanvas.getContext('2d');
    initCanvas();

    let scratchedPixels = new Set();
    const minScratchPercent = gameState.config.minScratchPercent || 51;

    function scratch(x, y) {
        if (!gameState.isPlaying) return;

        if (Math.random() < 0.1) {
            AudioSystem.playScratchSound();
        }

        ctx.globalCompositeOperation = 'destination-out';
        ctx.beginPath();
        ctx.arc(x, y, 25, 0, 2 * Math.PI);
        ctx.fill();

        for (let i = 0; i < 3; i++) {
            ctx.beginPath();
            const offsetX = x + (Math.random() - 0.5) * 20;
            const offsetY = y + (Math.random() - 0.5) * 20;
            ctx.arc(offsetX, offsetY, 15 + Math.random() * 10, 0, 2 * Math.PI);
            ctx.fill();
        }

        const radius = 30;
        for (let dx = -radius; dx <= radius; dx += 3) {
            for (let dy = -radius; dy <= radius; dy += 3) {
                if (dx * dx + dy * dy <= radius * radius) {
                    const pixelX = Math.floor(x + dx);
                    const pixelY = Math.floor(y + dy);
                    if (pixelX >= 0 && pixelX < newCanvas.width && pixelY >= 0 && pixelY < newCanvas.height) {
                        scratchedPixels.add(pixelX + ',' + pixelY);
                    }
                }
            }
        }

        const totalPixels = newCanvas.width * newCanvas.height;
        const scratchedCount = scratchedPixels.size;
        gameState.scratchProgress = Math.min(100, (scratchedCount / totalPixels) * 100);

        elements.progressIndicator.textContent = Math.round(gameState.scratchProgress) + '%';

        if (gameState.scratchProgress >= minScratchPercent && gameState.isPlaying) {
            gameState.isPlaying = false;
            console.log(`🎯 Raspagem atingiu ${minScratchPercent}% - finalizando jogo...`);
            
            setTimeout(() => {
                finishGame();
            }, 1000);
        }
    }

    newCanvas.addEventListener('mousedown', function (e) {
        if (!gameState.isPlaying) return;
        isDrawing = true;
        const rect = newCanvas.getBoundingClientRect();
        scratch(e.clientX - rect.left, e.clientY - rect.top);
    });

    newCanvas.addEventListener('mousemove', function (e) {
        if (!isDrawing || !gameState.isPlaying) return;
        const rect = newCanvas.getBoundingClientRect();
        scratch(e.clientX - rect.left, e.clientY - rect.top);
    });

    newCanvas.addEventListener('mouseup', function () {
        isDrawing = false;
    });

    newCanvas.addEventListener('mouseleave', function () {
        isDrawing = false;
    });

    newCanvas.addEventListener('touchstart', function (e) {
        if (!gameState.isPlaying) return;
        e.preventDefault();
        isDrawing = true;
        const rect = newCanvas.getBoundingClientRect();
        const touch = e.touches[0];
        scratch(touch.clientX - rect.left, touch.clientY - rect.top);
    });

    newCanvas.addEventListener('touchmove', function (e) {
        if (!isDrawing || !gameState.isPlaying) return;
        e.preventDefault();
        const rect = newCanvas.getBoundingClientRect();
        const touch = e.touches[0];
        scratch(touch.clientX - rect.left, touch.clientY - rect.top);
    });

    newCanvas.addEventListener('touchend', function (e) {
        e.preventDefault();
        isDrawing = false;
    });
}

async function startGame() {
    const cost = gameState.config.cost || 1.00;
    
    console.log('🎮 Iniciando jogo... Saldo:', gameState.balance, 'Custo:', cost);
    
    if (gameState.balance < cost) {
        showNotification('Saldo insuficiente para jogar!', 'info');
        return;
    }

    try {
        console.log('📡 Enviando requisição para iniciar jogo...');
        
        const response = await fetch('/api/multi_game_logic.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'start_game',
                game_id: gameState.gameId 
            })
        });
        
        console.log('📡 Resposta do servidor - Status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        console.log('📦 Dados do jogo recebidos:', result);

        if (!result.success) {
            throw new Error(result.erro || 'Erro ao iniciar jogo');
        }

        gameState.preGameBalance = gameState.balance;
        
        gameState.serverBalance = result.newBalance;
        gameState.currentGame = result.gameResult;
        gameState.isPlaying = true;
        gameState.scratchProgress = 0;

        if (!gameState.currentGame || !gameState.currentGame.grid) {
            throw new Error('Dados do jogo inválidos recebidos do servidor');
        }

        elements.playButton.style.display = 'none';
        elements.instruction.style.display = 'none';

        createGrid(gameState.currentGame);
        elements.gameGrid.style.display = 'grid';
        elements.scratchCanvas.style.display = 'block';
        elements.progressIndicator.style.display = 'block';
        elements.progressIndicator.textContent = '0%';

        setupScratchEvents();

        console.log('✅ Jogo iniciado com sucesso!');

    } catch (error) {
        console.error('❌ Erro ao iniciar jogo:', error);
        showNotification('Erro ao iniciar jogo: ' + error.message, 'info');
    }
}

function finishGame() {
    if (!gameState.currentGame) return;

    console.log('🏁 Finalizando jogo:', gameState.currentGame);

    elements.scratchCanvas.style.display = 'none';
    elements.progressIndicator.style.display = 'none';
    
    updateBalanceAfterGame();

    elements.gameGrid.style.display = 'grid';
    
    if (gameState.currentGame.isWin) {
        showWinScreen();
    } else {
        setTimeout(() => {
            showLoseScreen();
        }, 800);
    }
}

function updateBalanceAfterGame() {
    if (!gameState.currentGame) return;
    
    if (gameState.serverBalance !== undefined) {
        gameState.balance = gameState.serverBalance;
        console.log(`💰 Saldo atualizado com valor do servidor: R$ ${gameState.balance.toFixed(2)}`);
    } else {
        const cost = gameState.config.cost || 1.00;
        
        if (gameState.currentGame.isWin) {
            gameState.balance = gameState.preGameBalance - cost + gameState.currentGame.prize;
            console.log(`💰 VITÓRIA (calculado): Saldo atualizado para R$ ${gameState.balance.toFixed(2)} (${gameState.preGameBalance.toFixed(2)} - ${cost.toFixed(2)} + ${gameState.currentGame.prize.toFixed(2)})`);
        } else {
            gameState.balance = gameState.preGameBalance - cost;
            console.log(`💸 DERROTA (calculado): Saldo atualizado para R$ ${gameState.balance.toFixed(2)} (${gameState.preGameBalance.toFixed(2)} - ${cost.toFixed(2)})`);
        }
    }
    
    updateBalance();
}

function showWinScreen() {
    elements.gameGrid.style.display = 'grid';
    elements.simpleMessage.style.display = 'none';
    
    console.log('🏆 Mostrando tela de vitória...');
    
    setTimeout(() => {
        const prize = gameState.currentGame.prize;
        
        if (prize > 100) {
            AudioSystem.playAudioFile('/sounds/celebration.mp3');
            launchConfettiHTML();
        } else {
            AudioSystem.playAudioFile('/sounds/victory.mp3');
        }
                        
        const cells = elements.gameGrid.querySelectorAll('.grid-cell');
        if (gameState.currentGame.winPositions && Array.isArray(gameState.currentGame.winPositions)) {
            gameState.currentGame.winPositions.forEach(function (pos) {
                if (cells[pos]) {
                    cells[pos].classList.add('winning');
                    console.log(`🎯 Célula ${pos} destacada como vencedora`);
                }
            });
        } else {
            console.warn('⚠️ Posições vencedoras não encontradas ou inválidas');
        }
        
        setTimeout(function () {
            showFinalWin();
        }, 2500);
        
    }, 300);
}

function showFinalWin() {
    elements.gameGrid.style.display = 'none';
    elements.simpleMessage.style.display = 'flex';
    elements.simpleMessage.classList.add('celebrating');

    const prize = gameState.currentGame.prize;

    elements.messageTitle.textContent = 'VOCÊ GANHOU!';
    elements.messageEmoji.textContent = '🏆';
    elements.messagePrize.textContent = 'R$ ' + prize.toFixed(2);

    showNotification('Parabéns! Esse valor foi adicionado ao seu saldo!', 'celebration');

    setTimeout(() => resetGame(), 3000);
}

function showLoseScreen() {
    elements.gameGrid.style.display = 'grid';
    elements.simpleMessage.style.display = 'none';
    
    setTimeout(() => {
        elements.gameGrid.style.display = 'none';
        elements.simpleMessage.style.display = 'flex';

        elements.messageTitle.textContent = 'TENTE';
        elements.messageEmoji.textContent = '😔';
        elements.messageContent.innerHTML = 'NOVAMENTE';
        elements.messagePrize.textContent = '';

        showNotification('Que pena! Desta vez não foi, mas continue tentando!', 'info');

        setTimeout(function () {
            resetGame();
        }, 3000);
    }, 1200);
}

function resetGame() {
    gameState.isPlaying = false;
    gameState.currentGame = null;
    gameState.scratchProgress = 0;
    gameState.preGameBalance = 0;
    gameState.serverBalance = undefined;

    const canvas = elements.scratchCanvas;
    if (canvas && canvas.getContext) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    elements.simpleMessage.style.display = 'none';
    elements.simpleMessage.classList.remove('celebrating');
    elements.gameGrid.style.display = 'none';
    elements.scratchCanvas.style.display = 'none';
    elements.progressIndicator.style.display = 'none';

    elements.playButton.style.display = 'block';
    elements.instruction.style.display = 'block';
    elements.instruction.innerHTML = '<p><b>Compre uma raspadinha para começar a jogar</b></p><p>Clique no botão abaixo para comprar <i data-lucide="corner-right-down"></i></p>';

    elements.scratchCanvas.style.display = 'block';
    initCanvas();

    const cost = gameState.config.cost || 1.00;
    
    if (gameState.balance >= cost) {
        elements.playButton.disabled = false;
        updateButtonText();
    } else {
        elements.playButton.disabled = true;
        elements.playButton.textContent = 'SALDO INSUFICIENTE';
    }
}

function updateBalance() {
    elements.playerBalance.textContent = 'R$ ' + gameState.balance.toFixed(2);
    
    const userBalance = document.getElementById('userBalance');
    if (userBalance) {
        userBalance.textContent = 'R$ ' + gameState.balance.toFixed(2);
    }
}

function showNotification(message, type) {
    type = type || 'info';
    elements.notification.textContent = message;
    elements.notification.className = 'notification ' + type;
    elements.notification.style.display = 'block';

    setTimeout(function () {
        elements.notification.style.display = 'none';
    }, 4000);
}

function closeModal() {
    window.location.href = '/';
}

const AudioSystem = {
    audioContext: null,
    
    init() {
        try {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            console.log('🔊 Sistema de áudio inicializado');
        } catch (e) {
            console.warn('⚠️ Áudio não suportado:', e);
        }
    },
    
    playScratchSound() {
        if (!this.audioContext) return;
        
        try {
            const duration = 0.1;
            this.playNoise(duration, 0.05);
        } catch (e) {
            console.warn('⚠️ Erro ao reproduzir som de raspagem:', e);
        }
    },
    
    playTone(frequency, duration, waveType = 'sine', volume = 0.2) {
        if (!this.audioContext) return;
        
        const oscillator = this.audioContext.createOscillator();
        const gainNode = this.audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(this.audioContext.destination);
        
        oscillator.frequency.setValueAtTime(frequency, this.audioContext.currentTime);
        oscillator.type = waveType;
        
        gainNode.gain.setValueAtTime(volume, this.audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + duration);
        
        oscillator.start(this.audioContext.currentTime);
        oscillator.stop(this.audioContext.currentTime + duration);
    },
    
    playNoise(duration, volume = 0.1) {
        if (!this.audioContext) return;
        
        const bufferSize = this.audioContext.sampleRate * duration;
        const buffer = this.audioContext.createBuffer(1, bufferSize, this.audioContext.sampleRate);
        const data = buffer.getChannelData(0);
        
        for (let i = 0; i < bufferSize; i++) {
            data[i] = Math.random() * 2 - 1;
        }
        
        const source = this.audioContext.createBufferSource();
        const gainNode = this.audioContext.createGain();
        
        source.buffer = buffer;
        source.connect(gainNode);
        gainNode.connect(this.audioContext.destination);
        
        gainNode.gain.setValueAtTime(volume, this.audioContext.currentTime);
        source.start(this.audioContext.currentTime);
    },
    
    async playAudioFile(url) {
        try {
            const audio = new Audio(url);
            audio.volume = 0.5;
            await audio.play();
            console.log(`🎵 Arquivo de áudio reproduzido: ${url}`);
        } catch (e) {
            console.warn('⚠️ Erro ao reproduzir arquivo:', e);
            this.playTone(800, 0.5);
        }
    }
};

document.addEventListener('keydown', function (e) {
    switch (e.key) {
        case 'Escape':
            closeModal();
            break;
        case ' ':
            e.preventDefault();
            if (!gameState.isPlaying) startGame();
            break;
        case 'Enter':
            if (!gameState.isPlaying) startGame();
            break;
    }
});

document.getElementById('modalOverlay').addEventListener('click', function (e) {
    if (e.target === e.currentTarget) {
        closeModal();
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');
    const userMenu = document.querySelector('.user-menu');

    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
            userMenu.classList.toggle('active');
        });

        document.addEventListener('click', function (e) {
            if (!userMenu.contains(e.target)) {
                userDropdown.classList.remove('show');
                userMenu.classList.remove('active');
            }
        });
    }
});

async function fetchUserInfo() {
    try {
        const res = await fetch('/api/user_info.php', { credentials: 'include' });
        if (!res.ok) throw new Error('Não autenticado');
        const data = await res.json();
        
        if (data.saldo) {
            const userBalance = document.getElementById('userBalance');
            if (userBalance) {
                userBalance.textContent = data.saldo;
            }
        }
    } catch {
        
    }
}

async function initialize() {
    console.log('🚀 Inicializando aplicação...');
    
    AudioSystem.init();
    
    const urlParams = new URLSearchParams(window.location.search);
    const gameIdFromUrl = urlParams.get('game_id');
    if (gameIdFromUrl && !isNaN(gameIdFromUrl)) {
        gameState.gameId = parseInt(gameIdFromUrl);
        console.log('🎲 Game ID da URL:', gameState.gameId);
    }
    
    await loadGameConfig();
    
    await fetchSaldo();
    
    await fetchUserInfo();
    
    if (!gameState.config.gameActive) {
        showNotification('Jogo temporariamente indisponível', 'info');
        elements.playButton.disabled = true;
        elements.playButton.textContent = 'JOGO INDISPONÍVEL';
        return;
    }
    
    updateBalance();
    updateButtonText();
    
    elements.scratchCanvas.style.display = 'block';
    initCanvas();
    
    console.log('✅ Aplicação inicializada com sucesso');
}

document.addEventListener('DOMContentLoaded', initialize);

function launchConfettiHTML(particles = 150) {
    const colors = ['#f44336', '#e91e63', '#9c27b0', '#2196f3', '#00bcd4', '#4caf50', '#ffeb3b', '#ff9800'];

    for (let i = 0; i < particles; i++) {
        const confetti = document.createElement('div');
        confetti.classList.add('confetti-piece');

        const color = colors[Math.floor(Math.random() * colors.length)];
        const left = Math.random() * 100;
        const size = Math.random() * 8 + 6;
        const rotation = Math.random() * 360;
        const duration = Math.random() * 1.5 + 2.5;
        const delay = Math.random() * 0.3;
        const opacity = Math.random() * 0.5 + 0.5;

        confetti.style.backgroundColor = color;
        confetti.style.left = `${left}vw`;
        confetti.style.width = `${size}px`;
        confetti.style.height = `${size * 0.6}px`;
        confetti.style.opacity = opacity;
        confetti.style.transform = `rotate(${rotation}deg)`;
        confetti.style.animation = `fall ${duration}s ease-out ${delay}s forwards`;

        document.body.appendChild(confetti);

        setTimeout(() => confetti.remove(), (duration + delay) * 1000);
    }
}
</script>

<script>
document.addEventListener('DOMContentLoaded', async function () {
    const transacaoId = localStorage.getItem('pendingTransactionId');
    if (!transacaoId) return;

    try {
        const res = await fetch(`/api/status_transaction.php?transaction_id=${transacaoId}`);
        const data = await res.json();

        if (data.success && data.status === 'paid') {
            localStorage.removeItem('pendingTransactionId');

            const saldo = await fetch('/api/user_info.php', { credentials: 'include' })
                .then(r => r.json())
                .then(d => parseFloat((d.saldo || '0').replace(',', '.')))
                .catch(() => 0.00);

            const waitForModal = () => {
                if (typeof window.showDepositSuccessModal === 'function') {
                    window.showDepositSuccessModal(
                        data.amount || 0.00,
                        'PIX',
                        transacaoId,
                        saldo
                    );
                } else {
                    setTimeout(waitForModal, 300);
                }
            };
            waitForModal();
        }
    } catch (err) {
        console.error('Erro ao verificar transação pendente:', err);
    }
});
</script>
<script src="/assets/js/deposit-success-modal.js"></script>

<script>
    lucide.createIcons();
</script>
</body>

</html>