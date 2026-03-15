<script setup lang="ts">
import { computed } from 'vue';

type CardItem = {
    id: number;
    category_id?: number | null;
    slug: string;
    title: string;
    description?: string | null;
    image_path?: string | null;
    route_name: string;
    permission?: string | null;
    role?: string | null;
    sort_order: number;
    is_active: boolean;
};

const props = withDefaults(
    defineProps<{
        card: CardItem;
        dragging?: boolean;
        draggable?: boolean;
    }>(),
    {
        dragging: false,
        draggable: true,
    },
);

const emit = defineEmits<{
    (e: 'dragstart', event: DragEvent): void;
    (e: 'dragend'): void;
    (e: 'drop'): void;
    (e: 'edit'): void;
    (e: 'delete'): void;
}>();

const imageSrc = computed(() => (props.card.image_path ? `/storage/${props.card.image_path}` : null));
</script>

<template>
    <div
        class="group relative flex h-full flex-col overflow-hidden rounded-3xl border border-white/40 bg-white/70 shadow-xl transition duration-300 hover:-translate-y-1"
        :class="dragging ? 'ring-2 ring-emerald-400/70 shadow-emerald-200/40' : ''"
        :draggable="draggable"
        @dragstart="emit('dragstart', $event)"
        @dragend="emit('dragend')"
        @dragover.prevent
        @drop="emit('drop')"
    >
        <div class="relative aspect-[4/3] w-full overflow-hidden bg-gradient-to-br from-slate-100 via-white to-emerald-50">
            <img
                v-if="imageSrc"
                :src="imageSrc"
                alt=""
                class="h-full w-full object-cover"
            />
            <div v-else class="flex h-full w-full items-center justify-center">
                <img src="/images/card-placeholder.svg" alt="" class="h-14 w-14 opacity-70" />
            </div>
            <span
                class="absolute left-3 top-3 rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]"
                :class="
                    card.is_active
                        ? 'bg-emerald-500/15 text-emerald-700'
                        : 'bg-slate-500/15 text-slate-600'
                "
            >
                {{ card.is_active ? 'Active' : 'Disabled' }}
            </span>
        </div>

        <div class="flex flex-1 flex-col gap-3 p-4">
            <div>
                <h4 class="text-base font-semibold text-slate-900">
                    {{ card.title }}
                </h4>
                <p class="mt-2 text-sm text-slate-600">
                    {{ card.description || 'No description provided.' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2 text-xs">
                <span
                    v-if="card.permission"
                    class="rounded-full bg-amber-100/80 px-2.5 py-1 font-semibold text-amber-700"
                >
                    Perm: {{ card.permission }}
                </span>
                <span
                    v-if="card.role"
                    class="rounded-full bg-sky-100/80 px-2.5 py-1 font-semibold text-sky-700"
                >
                    Role: {{ card.role }}
                </span>
            </div>

            <div class="mt-auto flex items-center justify-between gap-3 border-t border-white/40 pt-3 text-xs font-semibold">
                <button
                    type="button"
                    class="rounded-full bg-white px-3 py-1 text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50"
                    @click.stop="emit('edit')"
                >
                    Edit
                </button>
                <button
                    type="button"
                    class="rounded-full bg-rose-50 px-3 py-1 text-rose-700 ring-1 ring-rose-200 transition hover:bg-rose-100"
                    @click.stop="emit('delete')"
                >
                    Delete
                </button>
            </div>
        </div>
    </div>
</template>
