<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    title: string;
    description?: string | null;
    meta?: string | null;
    href?: string | null;
    imagePath?: string | null;
}>();

const imageSrc = computed(() => (props.imagePath ? `/storage/${props.imagePath}` : '/images/stores.jpg'));
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href || undefined"
        class="group relative flex h-full min-h-[260px] flex-col overflow-hidden rounded-[28px] border border-white/40 bg-white/80 shadow-xl transition duration-300 hover:-translate-y-0.5"
    >
        <div class="relative h-40 w-full">
            <img :src="imageSrc" alt="" class="h-full w-full object-cover" />
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/50 via-slate-900/15 to-transparent" />
            <h3 class="absolute top-4 end-4 text-lg font-semibold text-white drop-shadow">
                {{ title }}
            </h3>
        </div>

        <div class="flex flex-1 flex-col bg-white/90 px-5 py-4">
            <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center">
                <p
                    v-if="meta"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600"
                >
                    {{ meta }}
                </p>
                <p v-if="description" class="promo-line-clamp text-sm text-slate-600">
                    {{ description }}
                </p>
            </div>

            <div v-if="$slots.footer" class="mt-3 flex justify-end text-xs text-slate-500">
                <slot name="footer" />
            </div>
        </div>
    </component>
</template>

<style scoped>
.promo-line-clamp {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
