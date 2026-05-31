import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import './style.css'; // Load the premium, responsive global design tokens and styling system

const app = createApp(App);

// Mount the configured SPA vue-router
app.use(router);

app.mount('#app');
