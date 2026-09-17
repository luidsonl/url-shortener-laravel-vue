import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import axios from 'axios';

import router from './router';
import App from './App.vue';
import { useAuthStore } from './stores/auth';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

axios.interceptors.request.use((config) => {
    const token = localStorage.getItem('access_token');

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 401) {
            const auth = useAuthStore(pinia);

            if (auth.isAuthenticated) {
                auth.clear();
            }

            if (router.currentRoute.value.name !== 'login') {
                router.push({ name: 'login' });
            }
        }

        return Promise.reject(error);
    }
);

useAuthStore(pinia).bootstrap().finally(() => {
    app.mount('#app');
});