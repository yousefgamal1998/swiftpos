<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

type CardRow = {
    id: number;
    title: string;
    description?: string | null;
    icon: string;
    image_path?: string | null;
    color: string;
    route_name: string;
    permission?: string | null;
    role?: string | null;
    sort_order: number;
    is_active: boolean;
};

const props = defineProps<{
    cards: CardRow[];
}>();

const localCards = ref<CardRow[]>([...props.cards]);
const draggingIndex = ref<number | null>(null);
const isSyncingOrder = ref(false);

watch(
    () => props.cards,
    (value) => {
        localCards.value = [...value];
    },
);

const onDragStart = (event: DragEvent, index: number): void => {
    draggingIndex.value = index;
    event.dataTransfer?.setData('text/plain', String(index));
    event.dataTransfer?.setDragImage?.((event.currentTarget as HTMLElement) ?? new Image(), 20, 20);
};

const onDragEnd = (): void => {
    draggingIndex.value = null;
};

const moveCard = (fromIndex: number, toIndex: number): void => {
    if (fromIndex === toIndex) {
        return;
    }

    const updated = [...localCards.value];
    const [moved] = updated.splice(fromIndex, 1);
    updated.splice(toIndex, 0, moved);
    localCards.value = updated.map((card, index) => ({
        ...card,
        sort_order: index + 1,
    }));
};

const onDrop = (index: number): void => {
    if (draggingIndex.value === null) {
        return;
    }

    moveCard(draggingIndex.value, index);
    draggingIndex.value = null;
    syncOrder();
};

const syncOrder = (): void => {
    isSyncingOrder.value = true;

    router.put(
        route('admin.cards.reorder'),
        {
            order: localCards.value.map((card, index) => ({
                id: card.id,
                sort_order: index + 1,
            })),
        },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                isSyncingOrder.value = false;
            },
        },
    );
};

const toggleCard = (card: CardRow): void => {
    router.patch(route('admin.cards.toggle', card.id), {}, { preserveScroll: true });
};

const deleteCard = (card: CardRow): void => {
    if (!confirm(`Delete "${card.title}"? This cannot be undone.`)) {
        return;
    }

    router.delete(route('admin.cards.destroy', card.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Manage Cards" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Admin</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900">Dashboard Cards</h2>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('dashboard')"
                        class="rounded-xl border border-slate-200 bg-white/80 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700"
                    >
                        Back to Dashboard
                    </Link>
                    <Link
                        :href="route('admin.cards.create')"
                        class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                    >
                        New Card
                    </Link>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <section class="rounded-3xl bg-white/60 backdrop-blur-md backdrop-saturate-150 border border-white/30 shadow-xl">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-white/30 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Card Order</h3>
                        <p class="text-sm text-slate-600">Drag and drop cards to reorder the dashboard.</p>
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                        {{ isSyncingOrder ? 'Saving...' : `${localCards.length} total` }}
                    </p>
                </header>

                <div class="divide-y divide-white/30">
                    <div
                        v-if="localCards.length === 0"
                        class="px-6 py-10 text-center text-sm text-slate-500"
                    >
                        No cards configured yet. Create your first card.
                    </div>

                    <div
                        v-for="(card, index) in localCards"
                        :key="card.id"
                        class="flex flex-wrap items-center gap-4 px-6 py-4 transition"
                        :class="draggingIndex === index ? 'bg-emerald-50/60' : 'bg-transparent'"
                        draggable="true"
                        @dragstart="onDragStart($event, index)"
                        @dragend="onDragEnd"
                        @dragover.prevent
                        @drop="onDrop(index)"
                    >
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                                <img
                                    v-if="card.image_path"
                                    :src="`/storage/${card.image_path}`"
                                    alt=""
                                    class="h-9 w-9 object-contain"
                                />
                                <svg
                                    v-else-if="card.icon"
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="card.icon" />
                                </svg>
                                <img
                                    v-else
                                    src="/images/card-placeholder.svg"
                                    alt=""
                                    class="h-9 w-9 object-contain opacity-70"
                                />
                            </span>
                            <div>
                                <p class="font-semibold text-slate-900">{{ card.title }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ card.description || 'No description provided.' }}
                                </p>
                            </div>
                        </div>

                        <div class="ml-auto flex flex-wrap items-center gap-3 text-sm text-slate-600">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                {{ card.route_name }}
                            </span>
                            <span
                                v-if="card.permission"
                                class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700"
                            >
                                Perm: {{ card.permission }}
                            </span>
                            <span
                                v-if="card.role"
                                class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700"
                            >
                                Role: {{ card.role }}
                            </span>
                            <button
                                type="button"
                                class="rounded-full px-3 py-1 text-xs font-semibold transition"
                                :class="
                                    card.is_active
                                        ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200'
                                        : 'bg-slate-100 text-slate-500 hover:bg-slate-200'
                                "
                                @click="toggleCard(card)"
                            >
                                {{ card.is_active ? 'Active' : 'Disabled' }}
                            </button>
                            <Link
                                :href="route('admin.cards.edit', card.id)"
                                class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50"
                            >
                                Edit
                            </Link>
                            <button
                                type="button"
                                class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-200 transition hover:bg-rose-100"
                                @click="deleteCard(card)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
