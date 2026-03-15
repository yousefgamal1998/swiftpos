<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Footer from '@/Components/Footer.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col items-center bg-slate-950 px-4 py-10 sm:px-6"
    >
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,_#0ea5e9_0,_transparent_35%),radial-gradient(circle_at_bottom_right,_#22c55e_0,_transparent_30%)] opacity-30" />

        <div class="flex flex-1 flex-col items-center justify-center">
            <div>
                <Link href="/">
                    <ApplicationLogo variant="mark" class="h-16 w-16 text-slate-900" />
                </Link>
            </div>

            <div
                class="relative mt-6 w-full max-w-md overflow-hidden rounded-3xl border border-white/20 bg-white px-6 py-6 shadow-2xl"
            >
                <slot />
            </div>
        </div>

        <div class="relative w-full">
            <Footer />
        </div>
    </div>
</template>

