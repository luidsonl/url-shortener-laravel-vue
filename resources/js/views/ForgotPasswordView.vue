<script setup>
import { ref } from 'vue';
import axios from 'axios';

const email = ref('');
const submitting = ref(false);
const sent = ref(false);
const error = ref(null);

async function submit() {
    error.value = null;
    sent.value = false;
    submitting.value = true;

    try {
        await axios.post('/forgot-password', { email: email.value });
        sent.value = true;
    } catch (err) {
        error.value = err.response?.data?.errors?.email?.[0] ?? err.response?.data?.message ?? 'Não foi possível enviar o link.';
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="min-h-[calc(100vh-4rem)]">
        <div class="mx-auto flex w-full max-w-md flex-col justify-center px-6 py-16">
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/50">
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-slate-900">Recuperar senha</h2>
                    <p class="mt-1.5 text-sm text-slate-500">
                        Informe seu e-mail e enviaremos um link para redefinir sua senha.
                    </p>

                    <p v-if="sent" class="mt-6 rounded-xl border border-green-100 bg-green-50 px-4 py-3 text-sm text-green-700">
                        Se o e-mail existir, um link de redefinição foi enviado. Verifique sua caixa de entrada.
                    </p>

                    <form v-else class="mt-8 space-y-5" @submit.prevent="submit">
                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">E-mail</label>
                            <input
                                id="email"
                                v-model="email"
                                type="email"
                                autocomplete="email"
                                required
                                placeholder="voce@exemplo.com"
                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500"
                            />
                            <p v-if="error" class="mt-1.5 text-xs text-red-600">{{ error }}</p>
                        </div>

                        <button
                            type="submit"
                            :disabled="submitting"
                            class="w-full rounded-xl bg-brand-600 py-3.5 text-sm font-bold text-white shadow-lg shadow-brand-200 transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {{ submitting ? 'Enviando...' : 'Enviar link' }}
                        </button>

                        <router-link :to="{ name: 'login' }" class="block text-center text-sm font-semibold text-slate-600 hover:text-slate-900">
                            Voltar para o login
                        </router-link>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>