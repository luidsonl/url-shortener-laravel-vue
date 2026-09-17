<script setup>
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
</script>

<template>
    <div class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-6">
        <div class="text-center">
            <p class="text-6xl font-black tracking-tight text-brand-600">404</p>
            <h1 class="mt-4 text-2xl font-bold text-slate-900">Página não encontrada</h1>
            <p class="mt-2 text-sm text-slate-500">O endereço que você acessou não existe ou foi movido.</p>
            <router-link
                :to="{ name: auth.isAuthenticated ? 'dashboard' : 'login' }"
                class="mt-8 inline-block rounded-xl bg-brand-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-brand-200 transition hover:bg-brand-700"
            >
                Voltar ao início
            </router-link>
        </div>
    </div>
</template>