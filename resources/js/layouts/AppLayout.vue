<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();

const mobileOpen = ref(false);

async function logout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>

<template>
    <div class="flex min-h-full flex-col bg-slate-50">
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/80 backdrop-blur">
            <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-8">
                    <router-link :to="{ name: 'home' }" class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white shadow-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                            </svg>
                        </span>
                        <span class="text-lg font-bold tracking-tight text-slate-900">shrt.<span class="text-brand-600">ly</span></span>
                    </router-link>

                    <nav v-if="auth.isAuthenticated" class="hidden items-center gap-1 md:flex">
                        <router-link
                            :to="{ name: 'dashboard' }"
                            class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                            active-class="bg-brand-50 !text-brand-700"
                        >
                            Meus Links
                        </router-link>
                    </nav>
                </div>

                <div class="flex items-center gap-3">
                    <template v-if="auth.isAuthenticated">
                        <router-link :to="{ name: 'profile' }" class="flex items-center gap-2.5 rounded-full py-1.5 pl-1.5 pr-3 transition hover:bg-slate-100">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">
                                {{ auth.initials }}
                            </span>
                            <span class="hidden text-sm font-medium text-slate-700 sm:block">{{ auth.user?.name }}</span>
                        </router-link>
                        <button
                            @click="logout"
                            class="rounded-lg border border-slate-200 px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                        >
                            Sair
                        </button>
                    </template>
                    <template v-else>
                        <router-link :to="{ name: 'login' }" class="rounded-lg px-3.5 py-2 text-sm font-semibold text-slate-700 hover:text-slate-900">
                            Entrar
                        </router-link>
                        <router-link
                            :to="{ name: 'register' }"
                            class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700"
                        >
                            Criar conta
                        </router-link>
                    </template>
                </div>
            </nav>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-slate-500 sm:flex-row sm:px-6 lg:px-8">
                <p>&copy; {{ new Date().getFullYear() }} shrt.ly — Encurtador de URLs.</p>
                <div class="flex items-center gap-6">
                    <router-link :to="{ name: 'home' }" class="transition hover:text-slate-900">Home</router-link>
                    <router-link v-if="!auth.isAuthenticated" :to="{ name: 'login' }" class="transition hover:text-slate-900">
                        Entrar
                    </router-link>
                    <router-link v-else :to="{ name: 'dashboard' }" class="transition hover:text-slate-900">Meus Links</router-link>
                </div>
            </div>
        </footer>
    </div>
</template>