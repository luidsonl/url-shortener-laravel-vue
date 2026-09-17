<script setup>
import { reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';

const props = defineProps({
    mode: {
        type: String,
        default: 'login',
    },
});

const auth = useAuthStore();
const route = useRoute();

const submitting = ref(false);
const errors = ref({});
const message = ref(null);

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const isLogin = () => props.mode === 'login';

async function submit() {
    errors.value = {};
    message.value = null;
    submitting.value = true;

    try {
        if (isLogin()) {
            await auth.login({ email: form.email, password: form.password });
        } else {
            await auth.register({
                name: form.name,
                email: form.email,
                password: form.password,
                password_confirmation: form.password_confirmation,
            });
        }

        window.location.href = '/dashboard';
    } catch (err) {
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        } else if (err.response?.status === 401) {
            errors.value = { email: ['Credenciais inválidas.'] };
        } else {
            message.value = err.response?.data?.message ?? 'Não foi possível concluir a solicitação.';
        }
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="min-h-[calc(100vh-4rem)]">
        <div class="mx-auto flex max-w-md flex-col justify-center px-6 py-12 sm:max-w-6xl sm:py-16 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div class="hidden lg:block">
                    <h1 class="text-4xl font-bold leading-tight tracking-tight text-slate-900">
                        Links curtos, <span class="text-brand-600">conexões rápidas</span>.
                    </h1>
                    <p class="mt-4 max-w-md text-lg leading-relaxed text-slate-500">
                        Transforme URLs longas em links curtos e rastreáveis. Compartilhe com facilidade, acompanhe cliques e controle a expiração dos seus links.
                    </p>

                    <div class="mt-10 grid max-w-md grid-cols-3 gap-4">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-2xl font-bold text-brand-600">+1k</p>
                            <p class="mt-1 text-sm text-slate-500">links criados</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-2xl font-bold text-brand-600">&lt;1ms</p>
                            <p class="mt-1 text-sm text-slate-500">de redirecionamento</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-2xl font-bold text-brand-600">100%</p>
                            <p class="mt-1 text-sm text-slate-500">rastreável</p>
                        </div>
                    </div>
                </div>

                <div class="mx-auto w-full max-w-md">
                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/50">
                        <div class="flex border-b border-slate-100">
                            <router-link
                                :to="{ name: 'login' }"
                                class="flex-1 px-4 py-4 text-center text-sm font-semibold transition"
                                :class="isLogin() ? 'border-b-2 border-brand-600 text-brand-700' : 'text-slate-500 hover:text-slate-900'"
                            >
                                Entrar
                            </router-link>
                            <router-link
                                :to="{ name: 'register' }"
                                class="flex-1 px-4 py-4 text-center text-sm font-semibold transition"
                                :class="!isLogin() ? 'border-b-2 border-brand-600 text-brand-700' : 'text-slate-500 hover:text-slate-900'"
                            >
                                Criar conta
                            </router-link>
                        </div>

                        <div class="p-8">
                            <h2 class="text-2xl font-bold text-slate-900">
                                {{ isLogin() ? 'Bem-vindo de volta' : 'Crie sua conta' }}
                            </h2>
                            <p class="mt-1.5 text-sm text-slate-500">
                                {{ isLogin() ? 'Acesse sua conta para gerenciar seus links.' : 'Cadastre-se grátis e comece a encurtar URLs.' }}
                            </p>

                            <form class="mt-8 space-y-5" @submit.prevent="submit">
                                <div v-if="!isLogin()">
                                    <label for="name" class="mb-1.5 block text-sm font-semibold text-slate-700">Nome</label>
                                    <input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        autocomplete="name"
                                        placeholder="Seu nome"
                                        class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500"
                                    />
                                    <p v-for="error in errors.name" :key="error" class="mt-1.5 text-xs text-red-600">{{ error }}</p>
                                </div>

                                <div>
                                    <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">E-mail</label>
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        autocomplete="email"
                                        placeholder="voce@exemplo.com"
                                        class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500"
                                    />
                                    <p v-for="error in errors.email" :key="error" class="mt-1.5 text-xs text-red-600">{{ error }}</p>
                                </div>

                                <div>
                                    <div class="mb-1.5 flex items-center justify-between">
                                        <label for="password" class="block text-sm font-semibold text-slate-700">Senha</label>
                                        <router-link
                                            v-if="isLogin()"
                                            :to="{ name: 'forgot-password' }"
                                            class="text-xs font-semibold text-brand-600 hover:text-brand-700"
                                        >
                                            Esqueceu a senha?
                                        </router-link>
                                    </div>
                                    <input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        autocomplete="current-password"
                                        placeholder="Mínimo 8 caracteres"
                                        class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500"
                                    />
                                    <p v-for="error in errors.password" :key="error" class="mt-1.5 text-xs text-red-600">{{ error }}</p>
                                </div>

                                <div v-if="!isLogin()">
                                    <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-slate-700">
                                        Confirmar senha
                                    </label>
                                    <input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        autocomplete="new-password"
                                        placeholder="Confirme sua senha"
                                        class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500"
                                    />
                                </div>

                                <p v-if="message" class="rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-600">{{ message }}</p>

                                <button
                                    type="submit"
                                    :disabled="submitting"
                                    class="w-full rounded-xl bg-brand-600 py-3.5 text-sm font-bold text-white shadow-lg shadow-brand-200 transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {{ submitting ? 'Aguarde...' : isLogin() ? 'Entrar' : 'Criar conta' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>