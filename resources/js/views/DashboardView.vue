<script setup>
import { onMounted, reactive, ref } from 'vue';
import axios from 'axios';

const links = ref([]);
const loading = ref(true);
const error = ref(null);

const pagination = reactive({
    current_page: 1,
    last_page: 1,
    total: 0,
});

const selected = ref([]);

const form = reactive({
    original_url: '',
    expires_at: '',
});

const creating = ref(false);
const formErrors = ref({});
const formMessage = ref(null);

const created = ref(null);
const copiedKey = ref(null);

function formatDate(value) {
    if (!value) return '—';

    return new Date(value).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

async function fetchLinks(page = 1) {
    loading.value = true;
    error.value = null;

    try {
        const { data } = await axios.get('/short-links', { params: { per_page: 50, page } });

        links.value = data.data ?? [];
        pagination.current_page = data.current_page ?? 1;
        pagination.last_page = data.last_page ?? 1;
        pagination.total = data.total ?? 0;
    } catch (err) {
        error.value = err.response?.data?.message ?? 'Não foi possível carregar os links.';
    } finally {
        loading.value = false;
    }
}

async function createLink() {
    formErrors.value = {};
    formMessage.value = null;
    created.value = null;
    creating.value = true;

    try {
        const payload = { original_url: form.original_url };

        if (form.expires_at) {
            payload.expires_at = new Date(form.expires_at).toISOString();
        }

        const { data } = await axios.post('/short-links', payload);

        created.value = data;
        form.original_url = '';
        form.expires_at = '';

        await fetchLinks(pagination.current_page);
    } catch (err) {
        if (err.response?.data?.errors) {
            formErrors.value = err.response.data.errors;
        } else {
            formMessage.value = err.response?.data?.message ?? 'Não foi possível criar o link.';
        }
    } finally {
        creating.value = false;
    }
}

async function deleteLink(id) {
    if (!window.confirm('Excluir este link? Esta ação não pode ser desfeita.')) return;

    try {
        await axios.delete(`/short-links/${id}`);
        await fetchLinks(pagination.current_page);
    } catch (err) {
        window.alert(err.response?.data?.message ?? 'Não foi possível excluir o link.');
    }
}

async function bulkDelete() {
    if (!selected.value.length) return;

    if (!window.confirm(`Excluir ${selected.value.length} link(s) selecionado(s)?`)) return;

    try {
        await axios.post('/short-links/bulk-delete', { ids: selected.value });
        selected.value = [];
        await fetchLinks(pagination.current_page);
    } catch (err) {
        window.alert(err.response?.data?.message ?? 'Não foi possível excluir os links.');
    }
}

async function copyText(key, text) {
    try {
        await navigator.clipboard.writeText(text);
        copiedKey.value = key;

        setTimeout(() => {
            copiedKey.value = null;
        }, 1500);
    } catch (err) {
        // Clipboard indisponível: ignora.
    }
}

onMounted(() => fetchLinks());
</script>

<template>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">Meus Links</h1>
                <p class="mt-1 text-sm text-slate-500">Crie, acompanhe e gerencie seus links encurtados.</p>
            </div>
            <div class="text-sm text-slate-500">{{ pagination.total }} link(s) criado(s)</div>
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-1">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900">Criar link</h2>

                    <form class="mt-5 space-y-4" @submit.prevent="createLink">
                        <div>
                            <label for="original_url" class="mb-1.5 block text-sm font-semibold text-slate-700">URL original</label>
                            <input
                                id="original_url"
                                v-model="form.original_url"
                                type="url"
                                required
                                placeholder="https://exemplo.com/pagina-muito-longa"
                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500"
                            />
                            <p v-for="err in formErrors.original_url" :key="err" class="mt-1.5 text-xs text-red-600">{{ err }}</p>
                        </div>

                        <div>
                            <label for="expires_at" class="mb-1.5 block text-sm font-semibold text-slate-700">Expira em (opcional)</label>
                            <input
                                id="expires_at"
                                v-model="form.expires_at"
                                type="datetime-local"
                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-900 transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500"
                            />
                            <p v-for="err in formErrors.expires_at" :key="err" class="mt-1.5 text-xs text-red-600">{{ err }}</p>
                        </div>

                        <p v-if="formMessage" class="rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-600">{{ formMessage }}</p>

                        <button
                            type="submit"
                            :disabled="creating"
                            class="w-full rounded-xl bg-brand-600 py-3 text-sm font-bold text-white shadow-lg shadow-brand-200 transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {{ creating ? 'Criando...' : 'Encurtar URL' }}
                        </button>
                    </form>

                    <div v-if="created" class="mt-6 rounded-2xl border border-green-100 bg-green-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-green-600">Link criado!</p>
                        <div class="mt-2 flex items-center justify-between gap-2">
                            <a
                                :href="created.short_url"
                                target="_blank"
                                rel="noopener"
                                class="truncate text-sm font-bold text-brand-700 hover:underline"
                            >
                                {{ created.short_url }}
                            </a>
                            <button
                                @click="copyText('created', created.short_url)"
                                class="shrink-0 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100"
                            >
                                {{ copiedKey === 'created' ? 'Copiado!' : 'Copiar' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div v-if="selected.length" class="mb-4 flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <span class="text-sm font-medium text-slate-600">{{ selected.length }} selecionado(s)</span>
                    <button @click="bulkDelete" class="rounded-lg border border-red-200 px-3.5 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50">
                        Excluir selecionados
                    </button>
                </div>

                <div v-if="loading" class="rounded-3xl border border-slate-200 bg-white p-12 text-center text-sm text-slate-500 shadow-sm">
                    Carregando links...
                </div>

                <p v-else-if="error" class="rounded-3xl border border-red-100 bg-red-50 p-6 text-center text-sm text-red-600 shadow-sm">
                    {{ error }}
                </p>

                <div v-else-if="!links.length" class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center shadow-sm">
                    <p class="text-sm font-medium text-slate-600">Nenhum link criado ainda.</p>
                    <p class="mt-1 text-sm text-slate-400">Use o formulário ao lado para criar seu primeiro link curto.</p>
                </div>

                <div v-else class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <ul class="divide-y divide-slate-100">
                        <li v-for="link in links" :key="link.id" class="px-5 py-4 transition hover:bg-slate-50">
                            <div class="flex items-start gap-3">
                                <input v-model="selected" type="checkbox" :value="link.id" class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" />

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a
                                            :href="link.short_url"
                                            target="_blank"
                                            rel="noopener"
                                            class="truncate text-sm font-bold text-brand-700 hover:underline"
                                        >
                                            {{ link.short_url }}
                                        </a>
                                        <span
                                            class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                            :class="link.is_valid ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600'"
                                        >
                                            {{ link.is_valid ? 'Ativo' : 'Expirado' }}
                                        </span>
                                    </div>

                                    <p class="mt-1 truncate text-sm text-slate-500" :title="link.original_url">{{ link.original_url }}</p>

                                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-400">
                                        <span class="font-medium text-slate-500">{{ link.visits_count }} visitas</span>
                                        <span>Expira: {{ formatDate(link.expires_at) }}</span>
                                        <span>Criado: {{ formatDate(link.created_at) }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5">
                                    <button
                                        @click="copyText(`short-${link.id}`, link.short_url)"
                                        class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100"
                                        title="Copiar link"
                                    >
                                        {{ copiedKey === `short-${link.id}` ? 'Copiado!' : 'Copiar' }}
                                    </button>
                                    <button
                                        @click="copyText(`code-${link.id}`, link.short_code)"
                                        class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100"
                                        title="Copiar código"
                                    >
                                        {{ copiedKey === `code-${link.id}` ? 'Código!' : 'Código' }}
                                    </button>
                                    <button
                                        @click="deleteLink(link.id)"
                                        class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50"
                                        title="Excluir"
                                    >
                                        Excluir
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>

                <div v-if="pagination.last_page > 1" class="mt-6 flex items-center justify-between">
                    <button
                        :disabled="pagination.current_page <= 1"
                        @click="fetchLinks(pagination.current_page - 1)"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Anterior
                    </button>
                    <span class="text-sm text-slate-500">
                        Página {{ pagination.current_page }} de {{ pagination.last_page }}
                    </span>
                    <button
                        :disabled="pagination.current_page >= pagination.last_page"
                        @click="fetchLinks(pagination.current_page + 1)"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Próxima
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>