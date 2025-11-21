 document.addEventListener('DOMContentLoaded', function() {
            const names = ['Fernanda', 'Rodrigo', 'Patrícia', 'Jose', 'Vanessa', 'André', 'Tatiane', 'Leandro', 'Maria', 'Felipe', 'Aline', 'Thiago'];
            const prizes = [
                { value: 'R$ 50', type: 'PIX' },
                { value: 'R$ 100', type: 'PIX' },
                { value: 'R$ 250', type: 'PIX' },
                { value: 'R$ 500', type: 'PIX' },
                { value: 'R$ 2.000', type: 'PIX' },
                { value: 'iPhone 15', type: 'PRODUTO' },
                { value: 'AirPods Pro', type: 'PRODUTO' },
                { value: 'Apple Watch', type: 'PRODUTO' },
            ];

            const colors = [
                'linear-gradient(135deg, #4338ca, #3730a3)',
                'linear-gradient(135deg, #7c3aed, #6d28d9)',
                'linear-gradient(135deg, #f59e0b, #d97706)',
                'linear-gradient(135deg, #06b6d4, #0891b2)',
                'linear-gradient(135deg, #ec4899, #db2777)',
                'linear-gradient(135deg, #10b981, #059669)',
                'linear-gradient(135deg, #ef4444, #dc2626)',
                'linear-gradient(135deg, #8b5cf6, #7c3aed)',
            ];

            function createWinnerItem(name, prize, time, color) {
                return `
                    <div class="winner-item">
                        <div class="winner-avatar">
                            <div class="avatar-circle" style="background: ${color};">
                                <span class="avatar-text">${name.charAt(3).toUpperCase()}</span>
                            </div>
                        </div>
                        <div class="winner-info">
                            <div class="winner-name">${name}***</div>
                            <div class="winner-time">há ${time} min</div>
                        </div>
                        <div class="winner-prize">
                            <div class="prize-value">${prize.value}</div>
                            <div class="prize-type">${prize.type}</div>
                        </div>
                    </div>
                `;
            }

            function updateTotal() {
                const totalElement = document.querySelector('.distributed-value');
                if (totalElement) {
                    let currentValue = parseInt(totalElement.textContent.replace(/[^\d]/g, ''));
                    currentValue += Math.floor(Math.random() * 1000) + 100;
                    totalElement.textContent = `R$ ${currentValue.toLocaleString('pt-BR')}`;
                }
            }

            setInterval(updateTotal, 30000);

            function addNewWinner() {
                const track = document.getElementById('winnersTrack');
                const randomName = names[Math.floor(Math.random() * names.length)];
                const randomPrize = prizes[Math.floor(Math.random() * prizes.length)];
                const randomTime = Math.floor(Math.random() * 5) + 1;
                const randomColor = colors[Math.floor(Math.random() * colors.length)];

                const newWinner = createWinnerItem(randomName, randomPrize, randomTime, randomColor);
                track.insertAdjacentHTML('beforeend', newWinner);

                const items = track.children;
                if (items.length > 20) {
                    track.removeChild(items[0]);
                }
            }

            setInterval(addNewWinner, 15000);

            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.winner-item').forEach(item => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                item.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(item);
            });

            const carousel = document.querySelector('.winners-carousel');
            const trackElement = document.querySelector('.winners-track');

            const carouselObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        trackElement.style.animationPlayState = 'running';
                    } else {
                        trackElement.style.animationPlayState = 'paused';
                    }
                });
            }, { threshold: 0.1 });

            carouselObserver.observe(carousel);
        });