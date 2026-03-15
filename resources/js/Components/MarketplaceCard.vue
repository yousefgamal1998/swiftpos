<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    title: string;
    description?: string | null;
    meta?: string | null;
    href?: string | null;
}>();
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href || undefined"
        class="group flex h-full flex-col justify-between rounded-2xl border border-white/40 bg-white/60 backdrop-blur-lg shadow-xl transition duration-300 hover:-translate-y-0.5"
    >
        <div class="p-6">
            <p v-if="meta" class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-500">
                {{ meta }}
            </p>
            <h3 class="mt-2 text-lg font-semibold text-slate-900">
                {{ title }}
            </h3>
            <p v-if="description" class="mt-2 text-sm text-slate-600">
                {{ description }}
            </p>
        </div>
        <div v-if="$slots.footer" class="border-t border-white/30 px-6 py-4 text-sm text-slate-500">
            <slot name="footer" />
        </div>
    </component>
</template>
