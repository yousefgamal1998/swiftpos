<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

type Card = {
    id: number;
    slug: string;
    title: string;
    description?: string | null;
    image_path?: string | null;
    color: string;
};

type ParentCard = {
    id: number;
    slug: string;
    title: string;
    description?: string | null;
    image_path?: string | null;
    color: string;
};

const props = defineProps<{
    card: ParentCard;
    children: Card[];
}>();
</script>

<template>
    <Head :title="props.card.title" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="rounded-3xl border border-white/60 bg-emerald-50/85 px-6 py-5 shadow-xl backdrop-blur-md backdrop-saturate-150"
            >
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="mb-2">
                            <Link
                                :href="route('dashboard')"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white/80 px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                                </svg>
                                Back to Dashboard
                            </Link>
                        </div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Category</p>
                        <h2 class="mt-1 text-2xl font-bold text-slate-900">
                            {{ props.card.title }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ props.card.description || 'Browse businesses in this category.' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                            {{ props.children.length }} {{ props.children.length === 1 ? 'business' : 'businesses' }}
                        </span>
                    </div>
                </div>
            </div>
        </template>

        <section class="mt-2 space-y-5">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <Link
                    v-for="(child, index) in props.children"
                    :key="child.id"
                    :href="route('cards.products', child.slug)"
                    class="animate-pos-fade-in group relative flex h-full min-h-[320px] flex-col overflow-hidden rounded-3xl border border-white/40 bg-white/60 backdrop-blur-lg shadow-xl transition duration-300 hover:-translate-y-1 hover:shadow-2xl"
                    :style="{ animationDelay: `${index * 80}ms` }"
                >
                    <!-- Business image -->
                    <div class="relative h-48 w-full overflow-hidden">
                        <img
                            :src="child.image_path ? `/storage/${child.image_path}` : '/images/card-placeholder.svg'"
                            :alt="child.title"
                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-slate-900/15 to-transparent" />
                        <div class="absolute bottom-3 left-4 right-4">
                            <h3 class="text-lg font-bold text-white drop-shadow-md">
                                {{ child.title }}
                            </h3>
                        </div>
                    </div>

                    <!-- Business info -->
                    <div class="flex flex-1 flex-col bg-white/90 px-5 py-4">
                        <p class="flex-1 text-sm text-slate-600 line-clamp-2">
                            {{ child.description ?? 'Explore products from this business.' }}
                        </p>
                        <div class="mt-3 flex justify-end">
                            <span
                                class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-emerald-700 shadow-sm transition group-hover:border-emerald-200 group-hover:bg-emerald-50"
                            >
                                View Products
                                <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </span>
                        </div>
                    </div>
                </Link>
            </div>

            <div
                v-if="props.children.length === 0"
                class="rounded-3xl border border-dashed border-slate-200 bg-white/70 p-10 text-center text-sm text-slate-500"
            >
                No businesses found in this category yet.
            </div>
        </section>
    </AuthenticatedLayout>
</template>
