<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

type StoreRow = {
    id: number;
    name?: string | null;
    title?: string | null;
    created_at?: string | null;
};

const props = defineProps<{
    stores: StoreRow[];
}>();

const displayName = (store: StoreRow): string => store.name ?? store.title ?? `Store #${store.id}`;

const formatDate = (value?: string | null): string =>
    value ? new Date(value).toLocaleDateString() : '--';
</script>

<template>
    <Head title="Stores" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Directory</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900">Stores</h2>
                </div>
                <Link
                    :href="route('dashboard')"
                    class="rounded-xl border border-slate-200 bg-white/80 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700"
                >
                    Back to Dashboard
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <section class="rounded-3xl bg-white/60 backdrop-blur-md backdrop-saturate-150 border border-white/30 shadow-xl">
                <header class="flex items-center justify-between border-b border-white/30 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">All Stores</h3>
                        <p class="text-sm text-slate-600">Monitor retail performance and stocking needs.</p>
                    </div>
                    <p class="text-sm text-slate-500">{{ props.stores.length }} total</p>
                </header>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-white/50 text-xs uppercase tracking-[0.08em] text-slate-500">
                            <tr>
                                <th class="px-6 py-3 font-semibold">ID</th>
                                <th class="px-6 py-3 font-semibold">Name</th>
                                <th class="px-6 py-3 font-semibold">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="props.stores.length === 0">
                                <td class="px-6 py-8 text-center text-slate-500" colspan="3">
                                    No stores found.
                                </td>
                            </tr>
                            <tr v-for="store in props.stores" :key="store.id" class="border-t border-white/20">
                                <td class="px-6 py-3 text-slate-600">{{ store.id }}</td>
                                <td class="px-6 py-3 font-semibold text-slate-900">{{ displayName(store) }}</td>
                                <td class="px-6 py-3 text-slate-600">{{ formatDate(store.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
