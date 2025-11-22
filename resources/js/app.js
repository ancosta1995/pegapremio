import './bootstrap';
import './csrf-helper';
import { createApp } from 'vue';
import ClawGame from './components/ClawGame.vue';
import disableDevtool from 'disable-devtool';
import { initializeSecurity } from './utils/security.js';

// Inicializa proteções de segurança
initializeSecurity();

disableDevtool({
    ondevtoolopen: () => {
        // aqui você decide o que fazer
        window.location.href = 'https://t.me/anc0stadev?start=Ola, gostaria de adquirir o seu projeto do pegapremio, achei ele sensacional!!!!!';
    }
});

const app = createApp(ClawGame);
app.mount('#app');
