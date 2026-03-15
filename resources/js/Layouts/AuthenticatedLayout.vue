<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Footer from '@/Components/Footer.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';
import { useCartStore } from '@/stores/cart';

interface SidebarItem {
    key: string;
    label: string;
    icon: string;
    href: string | null;
    active: boolean;
    visible: boolean;
    comingSoon?: boolean;
}

interface QuickAction {
    key: string;
    label: string;
    href: string;
    icon: string;
}

const page = usePage<PageProps>();
const mobileSidebarOpen = ref(false);

const currentUser = computed(() => page.props.auth.user);
const userRoles = computed(() => currentUser.value?.roles ?? []);

const {
    cartOpen,
    cartCount,
    cartItems,
    cartTotal,
    toggleCart,
    closeCart,
    removeItem,
    setUser,
    checkout,
} = useCartStore();

const canManageProducts = computed(() =>
    userRoles.value.some((role) => ['admin', 'manager'].includes(role)),
);

const canManageInventory = computed(() =>
    userRoles.value.some((role) => ['admin', 'manager'].includes(role)),
);

const canUsePos = computed(() =>
    userRoles.value.some((role) => ['admin', 'manager', 'cashier'].includes(role)),
);

const canManageMarketplace = computed(() => userRoles.value.includes('admin'));

const primaryRole = computed(() => {
    const role = userRoles.value[0] ?? 'staff';

    return role.charAt(0).toUpperCase() + role.slice(1);
});

const userAvatar = computed(() => currentUser.value?.avatar_url || '/images/default-avatar.png');

const hasRoute = (name: string): boolean => route().has(name);

const sidebarItems = computed<SidebarItem[]>(() =>
    [
        {
            key: 'dashboard',
            label: 'Dashboard',
            icon: 'dashboard',
            href: route('dashboard'),
            active: route().current('dashboard'),
            visible: true,
        },
        {
            key: 'pos',
            label: 'POS',
            icon: 'pos',
            href: canUsePos.value ? route('pos.index') : null,
            active: canUsePos.value ? route().current('pos.*') : false,
            visible: canUsePos.value,
        },
        {
            key: 'orders',
            label: 'Orders',
            icon: 'orders',
            href: canUsePos.value ? route('orders.index') : null,
            active: canUsePos.value ? route().current('orders.*') : false,
            visible: canUsePos.value,
        },
        {
            key: 'payments',
            label: 'Payments',
            icon: 'payments',
            href: canUsePos.value ? route('payments.index') : null,
            active: canUsePos.value ? route().current('payments.*') : false,
            visible: canUsePos.value,
        },
        {
            key: 'products',
            label: 'Products',
            icon: 'products',
            href: canManageProducts.value ? route('products.index') : null,
            active: canManageProducts.value ? route().current('products.*') : false,
            visible: canManageProducts.value,
        },
        {
            key: 'inventory',
            label: 'Inventory',
            icon: 'inventory',
            href: canManageInventory.value ? route('inventory.index') : null,
            active: canManageInventory.value ? route().current('inventory.*') : false,
            visible: canManageInventory.value,
        },
        {
            key: 'marketplace',
            label: 'Marketplace',
            icon: 'marketplace',
            href: canManageMarketplace.value ? route('admin.marketplace.index') : null,
            active: canManageMarketplace.value ? route().current('admin.marketplace.*') : false,
            visible: canManageMarketplace.value,
        },
        {
            key: 'instapay',
            label: 'InstaPay',
            icon: 'payments',
            href: canManageMarketplace.value && hasRoute('admin.instapay.index') ? route('admin.instapay.index') : null,
            active: canManageMarketplace.value && hasRoute('admin.instapay.index') ? route().current('admin.instapay.*') : false,
            visible: canManageMarketplace.value && hasRoute('admin.instapay.index'),
        },
        {
            key: 'reports',
            label: 'Reports',
            icon: 'reports',
            href: hasRoute('reports.index') ? route('reports.index') : null,
            active: hasRoute('reports.index') ? route().current('reports.*') : false,
            visible: true,
            comingSoon: !hasRoute('reports.index'),
        },
        {
            key: 'settings',
            label: 'Settings',
            icon: 'settings',
            href: route('profile.edit'),
            active: route().current('profile.*'),
            visible: true,
        },
    ].filter((item) => item.visible),
);

const quickActions = computed<QuickAction[]>(() => {
    const actions: QuickAction[] = [];

    if (canUsePos.value) {
        actions.push({
            key: 'new-sale',
            label: 'New Sale',
            href: route('pos.index'),
            icon: 'newSale',
        });
        actions.push({
            key: 'orders',
            label: 'Orders',
            href: route('orders.index'),
            icon: 'orders',
        });
    }

    if (canManageProducts.value) {
        actions.push({
            key: 'add-product',
            label: 'Add Product',
            href: route('products.create'),
            icon: 'addProduct',
        });
    }

    return actions;
});

const navIcons: Record<string, string> = {
    dashboard: 'M3.75 4.5h6.75v6.75H3.75V4.5Zm0 8.25h6.75v6.75H3.75v-6.75Zm9.75 0h6.75v6.75H13.5v-6.75Zm0-8.25h6.75v6.75H13.5V4.5Z',
    pos: 'M3.75 5.25h16.5a1.5 1.5 0 0 1 1.5 1.5v10.5a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5Zm0 4.5h18',
    orders:
        'M7.5 3.75h9l3 3v13.5a1.5 1.5 0 0 1-1.5 1.5h-12a1.5 1.5 0 0 1-1.5-1.5V5.25a1.5 1.5 0 0 1 1.5-1.5Zm1.5 6h6m-6 3h6m-6 3h3',
    payments:
        'M3.75 5.25h16.5a1.5 1.5 0 0 1 1.5 1.5v10.5a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5Zm0 4.5h18m-8.25 4.5h4.5',
    products:
        'M12 3.75 4.5 7.5 12 11.25 19.5 7.5 12 3.75Zm-7.5 7.5L12 15l7.5-3.75M4.5 11.25V18.75L12 22.5l7.5-3.75v-7.5',
    inventory:
        'M4.5 6.75a2.25 2.25 0 0 1 2.25-2.25h10.5A2.25 2.25 0 0 1 19.5 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 17.25V6.75Zm3.75 4.5h7.5m-7.5 3h4.5',
    marketplace: 'M3.75 6.75h16.5M4.5 9.75h15v8.25h-15zM7.5 13.5h3',
    reports: 'M4.5 19.5h15M7.5 16.5v-6m4.5 6v-9m4.5 9V9',
    settings:
        'M10.325 4.317a1 1 0 0 1 1.35-.936l.766.327a1 1 0 0 0 .939-.063l.705-.423a1 1 0 0 1 1.36.366l.75 1.3a1 1 0 0 0 .75.495l.86.11a1 1 0 0 1 .87.993v1.5a1 1 0 0 0 .427.819l.645.466a1 1 0 0 1 .296 1.37l-.75 1.299a1 1 0 0 0 0 1l.75 1.299a1 1 0 0 1-.296 1.37l-.645.466a1 1 0 0 0-.427.819v1.5a1 1 0 0 1-.87.994l-.86.109a1 1 0 0 0-.75.495l-.75 1.3a1 1 0 0 1-1.36.365l-.705-.423a1 1 0 0 0-.939-.063l-.766.327a1 1 0 0 1-1.35-.936v-.77a1 1 0 0 0-.588-.911l-.79-.35a1 1 0 0 1-.542-1.304l.295-.785a1 1 0 0 0-.164-.981l-.5-.61a1 1 0 0 1 0-1.268l.5-.61a1 1 0 0 0 .164-.98l-.295-.786a1 1 0 0 1 .542-1.304l.79-.35a1 1 0 0 0 .588-.911v-.77Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
};

const quickActionIcons: Record<string, string> = {
    newSale: 'M12 5.25v13.5M5.25 12h13.5',
    orders:
        'M7.5 3.75h9l3 3v13.5a1.5 1.5 0 0 1-1.5 1.5h-12a1.5 1.5 0 0 1-1.5-1.5V5.25a1.5 1.5 0 0 1 1.5-1.5Zm1.5 6h6m-6 3h6m-6 3h3',
    addProduct:
        'M12 3.75 4.5 7.5 12 11.25 19.5 7.5 12 3.75Zm0 7.5V22.5m-7.5-11.25V18.75L12 22.5l7.5-3.75v-7.5m-3-1.5v6',
};

const closeMobileSidebar = (): void => {
    mobileSidebarOpen.value = false;
};

onMounted(() => {
    setUser(currentUser.value?.id ?? null);
});

watch(currentUser, (user) => {
    setUser(user?.id ?? null);
});
</script>


<template>
    <div
        class="relative min-h-screen bg-cover bg-center bg-fixed text-slate-900"
        :style="{
            backgroundImage:
                'url(https://images.unsplash.com/photo-1504674900247-0877df9cc836)',
        }"
    >
        <div class="pointer-events-none absolute inset-0 bg-slate-950/40" />

        <div class="relative min-h-screen">
            <div
                v-if="mobileSidebarOpen"
                class="fixed inset-0 z-40 bg-slate-950/55 backdrop-blur-sm lg:hidden"
                @click="closeMobileSidebar"
            />

            <div class="relative flex min-h-screen">
                <aside
                    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-white/10 bg-gradient-to-b from-[#0b1220] via-[#0f172a] to-[#111827] text-slate-100 shadow-2xl transition-transform duration-300 lg:static lg:translate-x-0"
                    :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                >
                    <div class="border-b border-white/10 px-6 py-6">
                        <Link :href="route('dashboard')" class="inline-flex" @click="closeMobileSidebar">
                            <ApplicationLogo class="h-10 w-auto" />
                        </Link>
                        <p class="mt-3 text-xs text-slate-400">
                            Restaurant and retail command center
                        </p>
                    </div>

                    <nav class="flex-1 space-y-2 overflow-y-auto px-4 py-5">
                        <template v-for="item in sidebarItems" :key="item.key">
                            <Link
                                v-if="item.href"
                                :href="item.href"
                                class="group flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium transition"
                                :class="
                                    item.active
                                        ? 'bg-emerald-500/15 text-emerald-300 ring-1 ring-inset ring-emerald-400/40 shadow-lg shadow-emerald-950/20'
                                        : 'text-slate-300 hover:bg-white/5 hover:text-white'
                                "
                                @click="closeMobileSidebar"
                            >
                                <span class="flex items-center gap-3">
                                    <span
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg transition"
                                        :class="
                                            item.active
                                                ? 'bg-emerald-400/15 text-emerald-300'
                                                : 'bg-white/5 text-slate-300 group-hover:bg-white/10 group-hover:text-white'
                                        "
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                :d="navIcons[item.icon]"
                                            />
                                        </svg>
                                    </span>
                                    {{ item.label }}
                                </span>
                                <span
                                    v-if="item.comingSoon"
                                    class="rounded-full bg-amber-400/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-amber-200"
                                >
                                    Soon
                                </span>
                            </Link>

                            <div
                                v-else
                                class="flex items-center justify-between rounded-xl border border-dashed border-white/10 px-3 py-2.5 text-sm font-medium text-slate-400"
                            >
                                <span class="flex items-center gap-3">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white/5">
                                        <svg
                                            class="h-4 w-4"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                :d="navIcons[item.icon]"
                                            />
                                        </svg>
                                    </span>
                                    {{ item.label }}
                                </span>
                                <span
                                    class="rounded-full bg-amber-400/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-amber-200"
                                >
                                    Soon
                                </span>
                            </div>
                        </template>
                    </nav>

                    <div class="border-t border-white/10 px-4 py-4">
                        <div class="rounded-2xl bg-white/5 p-3">
                            <p class="text-xs uppercase tracking-[0.15em] text-slate-400">
                                Current role
                            </p>
                            <p class="mt-1 text-sm font-semibold text-white">
                                {{ primaryRole }}
                            </p>
                            <p class="mt-2 text-xs text-slate-400">
                                Optimized for fast checkout and inventory visibility.
                            </p>
                        </div>
                    </div>
                </aside>

                <div class="flex min-h-screen min-w-0 flex-1 flex-col">
                    <header class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/85 backdrop-blur-xl">
                        <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-10">
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:border-emerald-200 hover:text-emerald-700 lg:hidden"
                                    @click="mobileSidebarOpen = !mobileSidebarOpen"
                                >
                                    <span class="sr-only">Toggle navigation</span>
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                                    </svg>
                                </button>

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                                        SwiftPOS
                                    </p>
                                    <p class="text-sm font-semibold text-slate-800">
                                        Operations Dashboard
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 sm:gap-3">
                                <div class="hidden items-center gap-2 md:flex">
                                    <Link
                                        v-for="action in quickActions"
                                        :key="action.key"
                                        :href="action.href"
                                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:text-emerald-700 hover:shadow"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                :d="quickActionIcons[action.icon]"
                                            />
                                        </svg>
                                        {{ action.label }}
                                    </Link>
                                </div>

                                <button
                                    type="button"
                                    class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-amber-200 hover:text-amber-600"
                                >
                                    <span class="sr-only">Notifications</span>
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15 17h5l-1.4-1.4a2 2 0 0 1-.6-1.4V11a6 6 0 0 0-5-5.9V4a1 1 0 1 0-2 0v1.1A6 6 0 0 0 6 11v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0m6 0H9"
                                        />
                                    </svg>
                                    <span class="absolute right-2 top-2 h-2.5 w-2.5 rounded-full bg-amber-500 ring-2 ring-white" />
                                </button>

                                <button
                                    type="button"
                                    class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-emerald-200 hover:text-emerald-700"
                                    @click="toggleCart"
                                >
                                    <span class="sr-only">Cart</span>
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.6 3m0 0L7 18h10l2-8H5.6Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm7 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" />
                                    </svg>
                                    <span
                                        v-if="cartCount"
                                        class="absolute -right-1 -top-1 rounded-full bg-emerald-500 px-1.5 py-0.5 text-[10px] font-semibold text-white"
                                    >
                                        {{ cartCount }}
                                    </span>
                                </button>

                                <Dropdown align="right" width="56">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white py-1.5 pl-1.5 pr-3 text-sm font-medium text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700"
                                        >
                                            <img
                                                :src="userAvatar"
                                                alt="User Avatar"
                                                class="h-10 w-10 rounded-full object-cover"
                                            />
                                            <span class="hidden text-left sm:block">
                                                <span class="block leading-tight">{{ currentUser?.name }}</span>
                                                <span class="block text-xs text-slate-500">{{ primaryRole }}</span>
                                            </span>
                                            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.12l3.71-3.9a.75.75 0 1 1 1.08 1.04l-4.25 4.47a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </template>

                                    <template #content>
                                        <div class="px-4 py-2 text-xs text-slate-500">
                                            Signed in as
                                            <div class="mt-1 font-semibold text-slate-700">
                                                {{ currentUser?.email }}
                                            </div>
                                        </div>
                                        <DropdownLink :href="route('profile.edit')">
                                            Profile
                                        </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </header>

                    <main class="relative flex-1 overflow-x-hidden">
                        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(234,179,8,0.08),_transparent_45%),radial-gradient(circle_at_10%_20%,_rgba(16,185,129,0.08),_transparent_40%)]" />

                        <div class="relative px-4 pb-8 pt-6 sm:px-6 lg:px-10">
                            <div v-if="$slots.header" class="mb-6 animate-pos-fade-in">
                                <slot name="header" />
                            </div>

                            <div
                                v-if="$page.props.flash.success"
                                class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-800 shadow-sm"
                            >
                                {{ $page.props.flash.success }}
                            </div>
                            <div
                                v-if="$page.props.flash.error"
                                class="mb-4 rounded-2xl border border-rose-200 bg-rose-50/80 px-4 py-3 text-sm text-rose-800 shadow-sm"
                            >
                                {{ $page.props.flash.error }}
                            </div>

                            <slot />
                        </div>
                    </main>

                    <Footer />
                </div>
            </div>
        </div>

        <div
            v-if="cartOpen"
            class="fixed inset-0 z-40 bg-slate-900/30 backdrop-blur-sm"
            @click="closeCart"
        />
        <aside
            v-if="cartOpen"
            class="fixed right-6 top-24 z-50 w-full max-w-sm rounded-3xl border border-white/40 bg-white/90 p-6 shadow-2xl"
        >
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Cart</p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900">Your order</h3>
                </div>
                <button
                    type="button"
                    class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 hover:border-slate-300"
                    @click="closeCart"
                >
                    Close
                </button>
            </div>

            <div v-if="cartItems.length" class="mt-4 space-y-4">
                <div
                    v-for="item in cartItems"
                    :key="item.cartItemId ?? item.product.id"
                    class="flex items-start justify-between gap-3 rounded-2xl border border-slate-100 bg-white px-4 py-3 text-sm"
                >
                    <div>
                        <p class="font-semibold text-slate-900">{{ item.product.name }}</p>
                        <p class="text-xs text-slate-500">{{ item.product.description || 'No description' }}</p>
                        <p class="mt-1 text-xs font-semibold text-emerald-700">
                            ${{ Number(item.product.price).toFixed(2) }} · Qty {{ item.quantity }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-semibold text-slate-900">${{ item.total.toFixed(2) }}</p>
                        <button
                            type="button"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-500 transition hover:bg-rose-100 hover:text-rose-700"
                            title="Remove item"
                            @click="removeItem(item.product.id)"
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="mt-6 text-center text-sm text-slate-500">
                Your cart is empty.
            </div>

            <div class="mt-6 flex items-center justify-between text-sm font-semibold text-slate-700">
                <span>Total</span>
                <span class="text-base text-slate-900">${{ cartTotal.toFixed(2) }}</span>
            </div>

            <button
                type="button"
                class="mt-4 w-full rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-300"
                :disabled="!cartCount"
                @click="checkout"
            >
                Checkout
            </button>
        </aside>
    </div>
</template>
