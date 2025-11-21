
class FooterComponent {
    constructor(basePath = '') {
        this.basePath = basePath;
    }

    render() {
        return `
            <footer class="footer">
                <div class="container">
                    <div class="footer-content">
                        <div class="footer-section">
                            <h3>RASPOU PREMIOS</h3>
                            <p>A plataforma de raspadinhas virtuais mais emocionante do Brasil.</p>
                            <p>Raspe, ganhe e receba na hora via PIX!</p>
                        </div>

                        <div class="footer-section">
                            <h3>Links Rápidos</h3>
                            <ul>
                                <li><a href="${this.basePath}/">Início</a></li>
                                <li><a href="${this.basePath}/perfil/">Meu Perfil</a></li>
                                <li><a href="${this.basePath}/historico/">Histórico</a></li>
                                <li><a href="${this.basePath}/saque/">Sacar</a></li>
                            </ul>
                        </div>

                        <div class="footer-section">
                            <h3>Suporte</h3>
                            <ul>
                                <li><a href="${this.basePath}/como-jogar/">Como Jogar</a></li>
                                <li><a href="${this.basePath}/termos/">Termos de Uso</a></li>
                                <li><a href="${this.basePath}/privacidade/">Política de Privacidade</a></li>
                                <li><a href="${this.basePath}/contato/">Contato</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="footer-bottom">
                        <div>
                            <p>© ${new Date().getFullYear()} RaspouPremios. Todos os direitos reservados.</p>
                            <p style="font-size: 12px; margin-top: 10px;">Jogue com responsabilidade. Proibida a venda para menores de 18 anos.</p>
                        </div>
                        <div class="pix-logo">
                            <span>Pagamentos Digitais por</span>
                            <div class="pix-brand">PIX</div>
                        </div>
                    </div>
                </div>
            </footer>
        `;
    }

    init() {
        const footerContainer = document.getElementById('footer-container');
        if (footerContainer) {
            footerContainer.innerHTML = this.render();
        } else {
            document.body.insertAdjacentHTML('beforeend', this.render());
        }
    }
}

function initFooter(basePath = '') {
    const footer = new FooterComponent(basePath);
    footer.init();
    return footer;
}

window.initFooter = initFooter;