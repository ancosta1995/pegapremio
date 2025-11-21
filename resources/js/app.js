import './bootstrap';
import './csrf-helper';
import { createApp } from 'vue';
import ClawGame from './components/ClawGame.vue';

const app = createApp(ClawGame);
app.mount('#app');
