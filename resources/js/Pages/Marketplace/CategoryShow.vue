<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

type Category = {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
};

type Card = {
    id: number;
    slug: string;
    title: string;
    description?: string | null;
    image_path?: string | null;
    color: string;
    route_name: string;
    permission?: string | null;
    role?: string | null;
};

const props = defineProps<{
    category: Category;
    cards: Card[];
}>();
</script>

<template>
    <Head :title="props.category.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Category</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900">
                        {{ props.category.name }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ props.category.description || 'No description provided.' }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.marketplace.index')"
                        class="rounded-xl border border-slate-200 bg-white/80 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700"
                    >
                        Back to Marketplace
                    </Link>
                </div>
            </div>
        </template>

        <section class="rounded-3xl border border-white/40 bg-white/70 p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Cards</p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900">{{ props.cards.length }} cards</h3>
                </div>
                <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                    {{ route('categories.show', props.category.slug) }}
                </span>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Link
                    v-for="card in props.cards"
                    :key="card.id"
                    :href="route('cards.show', card.slug)"
                    class="group rounded-2xl border border-white/40 bg-white/70 p-4 shadow-sm transition hover:-translate-y-0.5"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100">
                            <img
                                v-if="card.image_path"
                                :src="`/storage/${card.image_path}`"
                                alt=""
                                class="h-10 w-10 object-contain"
                            />
                            <img
                                v-else
                                src="/images/card-placeholder.svg"
                                alt=""
                                class="h-10 w-10 object-contain opacity-70"
                            />
                        </div>
                        <div>
                            <p class="text-base font-semibold text-slate-900">{{ card.title }}</p>
                            <p class="text-xs text-slate-500">{{ card.description || 'No description' }}</p>
                        </div>
                    </div>
                </Link>

                <div
                    v-if="props.cards.length === 0"
                    class="rounded-2xl border border-dashed border-slate-200 bg-white/70 p-8 text-center text-sm text-slate-500"
                >
                    No cards in this category yet.
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
