<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();

const currentYear = new Date().getFullYear();

const isAuthenticated = !!page.props.auth?.user;

const footerLinks = [
    { label: 'Dashboard', route: 'dashboard', requiresAuth: true },
    { label: 'Products', route: 'products.index', requiresAuth: true },
    { label: 'Orders', route: 'orders.index', requiresAuth: true },
    { label: 'Payments', route: 'payments.index', requiresAuth: true },
];

const hasRoute = (name: string): boolean => {
    try {
        return route().has(name);
    } catch {
        return false;
    }
};

const visibleLinks = footerLinks.filter(
    (link) => (!link.requiresAuth || isAuthenticated) && hasRoute(link.route),
);
</script>

<template>
    <footer class="relative mt-auto border-t border-white/10 bg-gradient-to-b from-[#0b1220] via-[#0f172a] to-[#111827]">
        <!-- Decorative glow -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -top-24 left-1/4 h-48 w-96 rounded-full bg-emerald-500/5 blur-3xl" />
            <div class="absolute -top-16 right-1/3 h-40 w-80 rounded-full bg-amber-500/5 blur-3xl" />
        </div>

        <div class="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-10">
            <div class="grid grid-cols-1 gap-10 md:grid-cols-3">
                <!-- Branding -->
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-lg shadow-emerald-500/25">
                            <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white tracking-tight">
                                Swift<span class="text-emerald-400">POS</span>
                            </h3>
                            <p class="text-[11px] font-medium uppercase tracking-[0.2em] text-slate-500">
                                Point of Sale
                            </p>
                        </div>
                    </div>
                    <p class="max-w-xs text-sm leading-relaxed text-slate-400">
                        A modern restaurant and retail command center. Streamline your sales,
                        inventory, and marketplace — all in one place.
                    </p>
                    <!-- Social icons -->
                    <div class="flex items-center gap-3 pt-1">
                        <a
                            v-for="(icon, idx) in ['github', 'twitter', 'linkedin']"
                            :key="idx"
                            href="#"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/10 bg-white/5 text-slate-400 transition hover:border-emerald-400/30 hover:bg-emerald-400/10 hover:text-emerald-300"
                            :aria-label="icon"
                        >
                            <!-- GitHub -->
                            <svg v-if="icon === 'github'" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0 1 12 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/>
                            </svg>
                            <!-- Twitter / X -->
                            <svg v-else-if="icon === 'twitter'" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                            <!-- LinkedIn -->
                            <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Navigation -->
                <div v-if="visibleLinks.length">
                    <h4 class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                        Quick Links
                    </h4>
                    <ul class="mt-4 space-y-3">
                        <li v-for="link in visibleLinks" :key="link.route">
                            <Link
                                :href="route(link.route)"
                                class="group flex items-center gap-2 text-sm text-slate-400 transition hover:text-emerald-300"
                            >
                                <span class="inline-block h-1 w-1 rounded-full bg-slate-600 transition group-hover:bg-emerald-400" />
                                {{ link.label }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- System Info -->
                <div>
                    <h4 class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                        Platform
                    </h4>
                    <ul class="mt-4 space-y-3 text-sm text-slate-500">
                        <li class="flex items-center gap-2">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-white/5">
                                <svg class="h-3.5 w-3.5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7" />
                                </svg>
                            </span>
                            <span>Laravel + Vue 3 + Inertia.js</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-white/5">
                                <svg class="h-3.5 w-3.5 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </span>
                            <span>Role-Based Access Control</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-white/5">
                                <svg class="h-3.5 w-3.5 text-sky-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </span>
                            <span>Multi-Payment Support</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom bar -->
            <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-8 sm:flex-row">
                <p class="text-xs text-slate-500">
                    &copy; {{ currentYear }} <span class="font-semibold text-slate-400">SwiftPOS</span>. All rights reserved.
                </p>
                <div class="flex items-center gap-1">
                    <span class="text-xs text-slate-600">Crafted with</span>
                    <svg class="h-3.5 w-3.5 text-rose-400" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/>
                    </svg>
                    <span class="text-xs text-slate-600">for modern business</span>
                </div>
            </div>
        </div>
    </footer>
</template>
