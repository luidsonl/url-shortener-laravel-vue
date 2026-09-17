import { defineStore } from 'pinia';
import axios from 'axios';

const TOKEN_KEY = 'access_token';
const USER_KEY = 'user';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem(TOKEN_KEY) || null,
        user: JSON.parse(localStorage.getItem(USER_KEY) || 'null'),
    }),

    getters: {
        isAuthenticated: (state) => Boolean(state.token),
        isAdmin: (state) => state.user?.role === 'admin',
        emailVerified: (state) => Boolean(state.user?.email_verified),
        initials: (state) => {
            if (!state.user?.name) return '?';

            return state.user.name
                .split(' ')
                .filter(Boolean)
                .slice(0, 2)
                .map((part) => part[0].toUpperCase())
                .join('');
        },
    },

    actions: {
        setSession({ access_token: accessToken, user }) {
            this.token = accessToken;
            this.user = user;

            localStorage.setItem(TOKEN_KEY, accessToken);
            localStorage.setItem(USER_KEY, JSON.stringify(user));
        },

        clear() {
            this.token = null;
            this.user = null;

            localStorage.removeItem(TOKEN_KEY);
            localStorage.removeItem(USER_KEY);
        },

        async login(credentials) {
            const { data } = await axios.post('/auth/login', credentials);

            this.setSession(data);

            return data;
        },

        async register(payload) {
            const { data } = await axios.post('/auth/register', payload);

            this.setSession(data);

            return data;
        },

        async logout() {
            try {
                await axios.post('/auth/logout');
            } catch (error) {
                // Logout por expiração de token: ignora erros e limpa a sessão local.
            }

            this.clear();
        },

        async validate() {
            if (!this.token) return false;

            try {
                const { data } = await axios.post('/auth/validate-token', { token: this.token });

                this.user = data.user;
                localStorage.setItem(USER_KEY, JSON.stringify(data.user));

                return true;
            } catch (error) {
                this.clear();

                return false;
            }
        },

        async bootstrap() {
            if (this.token) {
                await this.validate();
            }
        },
    },
});