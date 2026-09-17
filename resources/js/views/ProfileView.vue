<script setup>
import { reactive, ref } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();

const profile = reactive({ name: auth.user?.name ?? '' });
const updatingProfile = ref(false);
const profileErrors = ref({});
const profileSuccess = ref(null);

const password = reactive({
    previous_password: '',
    password: '',
    password_confirmation: '',
});
const updatingPassword = ref(false);
const passwordErrors = ref({});
const passwordSuccess = ref(null);

async function updateProfile() {
    profileErrors.value = {};
    profileSuccess.value = null;
    updatingProfile.value = true;

    try {
        const { data } = await axios.put('/profile', profile);

        auth.user = data;
        localStorage.setItem('user', JSON.stringify(data));
        profile.name = data.name;
        profileSuccess.value = 'Perfil atualizado com sucesso.';
    } catch (err) {
        profileErrors.value = err.response?.data?.errors ?? {};
    } finally {
        updatingProfile.value = false;
    }
}

async function updatePassword() {
    passwordErrors.value = {};
    passwordSuccess.value = null;
    updatingPassword.value = true;

    try {
        await axios.put('/profile', password);

        password.previous_password = '';
        password.password = '';
        password.password_confirmation = '';
        passwordSuccess.value = 'Senha atualizada com sucesso.';
    } catch (err) {
        if (err.response?.data?.errors) {
            passwordErrors.value = err.response.data.errors;
        } else if (err.response?.data?.message) {
            passwordErrors.value = { previous_password: [err.response.data.message] };
        }
    } finally {
        updatingPassword.value = false;
    }
}

async function deleteAccount() {
    if (!window.confirm('Tem certeza que deseja excluir sua conta? Esta ação não pode ser desfeita.')) return;

    try {
        await axios.delete('/profile');
        auth.clear();
        router.push({ name: 'login' });
    } catch (err) {
        window.alert(err.response?.data?.message ?? 'Não foi possível excluir a conta.');
    }
}
</script>

<template>
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Meu Perfil</h1>
        <p class="mt-1 text-sm text-slate-500">Gerencie suas informações pessoais e a segurança da sua conta.</p>

        <div class="mt-8 flex items-center gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-600 text-lg font-bold text-white">
                {{ auth.initials }}
            </span>
            <div>
                <p class="text-lg font-bold text-slate-900">{{ auth.user?.name }}</p>
                <p class="text-sm text-slate-500">{{ auth.user?.email }}</p>
            </div>
            <span
                class="ml-auto rounded-full px-3 py-1 text-xs font-semibold"
                :class="auth.emailVerified ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700'"
            >
                {{ auth.emailVerified ? 'E-mail verificado' : 'E-mail não verificado' }}
            </span>
        </div>

        <div class="mt-6 grid gap-6 md:grid-cols-2">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Informações</h2>

                <form class="mt-5 space-y-4" @submit.prevent="updateProfile">
                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-semibold text-slate-700">Nome</label>
                        <input
                            id="name"
                            v-model="profile.name"
                            type="text"
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-900 transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500"
                        />
                        <p v-for="err in profileErrors.name" :key="err" class="mt-1.5 text-xs text-red-600">{{ err }}</p>
                    </div>

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">E-mail</label>
                        <input
                            id="email"
                            :value="auth.user?.email"
                            type="email"
                            disabled
                            class="w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-400"
                        />
                    </div>

                    <p v-if="profileSuccess" class="rounded-xl border border-green-100 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ profileSuccess }}
                    </p>

                    <button
                        type="submit"
                        :disabled="updatingProfile"
                        class="w-full rounded-xl bg-brand-600 py-3 text-sm font-bold text-white shadow-lg shadow-brand-200 transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{ updatingProfile ? 'Salvando...' : 'Salvar alterações' }}
                    </button>
                </form>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Alterar senha</h2>

                <form class="mt-5 space-y-4" @submit.prevent="updatePassword">
                    <div>
                        <label for="previous_password" class="mb-1.5 block text-sm font-semibold text-slate-700">Senha atual</label>
                        <input
                            id="previous_password"
                            v-model="password.previous_password"
                            type="password"
                            autocomplete="current-password"
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-900 transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500"
                        />
                        <p v-for="err in passwordErrors.previous_password" :key="err" class="mt-1.5 text-xs text-red-600">{{ err }}</p>
                    </div>

                    <div>
                        <label for="new_password" class="mb-1.5 block text-sm font-semibold text-slate-700">Nova senha</label>
                        <input
                            id="new_password"
                            v-model="password.password"
                            type="password"
                            autocomplete="new-password"
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-900 transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500"
                        />
                        <p v-for="err in passwordErrors.password" :key="err" class="mt-1.5 text-xs text-red-600">{{ err }}</p>
                    </div>

                    <div>
                        <label for="new_password_confirmation" class="mb-1.5 block text-sm font-semibold text-slate-700">Confirmar nova senha</label>
                        <input
                            id="new_password_confirmation"
                            v-model="password.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-900 transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500"
                        />
                    </div>

                    <p v-if="passwordSuccess" class="rounded-xl border border-green-100 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ passwordSuccess }}
                    </p>

                    <button
                        type="submit"
                        :disabled="updatingPassword"
                        class="w-full rounded-xl bg-brand-600 py-3 text-sm font-bold text-white shadow-lg shadow-brand-200 transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{ updatingPassword ? 'Atualizando...' : 'Atualizar senha' }}
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-6 rounded-3xl border border-red-100 bg-red-50 p-6">
            <h2 class="text-lg font-bold text-red-700">Zona de perigo</h2>
            <p class="mt-1 text-sm text-red-600">Ao excluir sua conta, todos os seus links serão removidos permanentemente.</p>
            <button
                @click="deleteAccount"
                class="mt-4 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-100"
            >
                Excluir minha conta
            </button>
        </div>
    </div>
</template>