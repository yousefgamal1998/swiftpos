<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface DashboardStats {
    sales_today: number;
    sales_month: number;
    orders_today: number;
    open_sessions: number;
    low_stock_count: number;
}

interface RecentOrder {
    id: number;
    order_number: string;
    total_amount: string | number;
    payment_status: string;
    placed_at: string;
    user: {
        id: number;
        name: string;
    };
}

interface LowStockProduct {
    id: number;
    name: string;
    sku: string;
    stock_quantity: string | number;
    low_stock_threshold: string | number;
    unit: string;
}

interface DashboardCard {
    id: number;
    slug?: string | null;
    title: string;
    description?: string | null;
    icon: string;
    image_path?: string | null;
    color: string;
    route_name: string;
}

interface CategoryGroup {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    cards: DashboardCard[];
}

interface StatCard {
    key: string;
    label: string;
    value: string;
    helper: string;
    icon: string;
    tone: 'emerald' | 'amber' | 'blue' | 'rose' | 'violet';
}

const props = defineProps<{
    stats: DashboardStats;
    recentOrders: RecentOrder[];
    lowStockProducts: LowStockProduct[];
    categories: CategoryGroup[];
}>();

const page = usePage<PageProps>();

const userRoles = computed(() => page.props.auth.user?.roles ?? []);
const canUsePos = computed(() =>
    userRoles.value.some((role) => ['admin', 'manager', 'cashier'].includes(role)),
);

const currency = (value: string | number): string =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 2,
    }).format(Number(value));

const formatTime = (value: string): string => {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '--';
    }

    return new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit',
    }).format(date);
};

const statCards = computed<StatCard[]>(() => [
    {
        key: 'sales_today',
        label: "Today's Sales",
        value: currency(props.stats.sales_today),
        helper: 'Completed revenue in the last 24 hours',
        icon: 'M3.75 12h16.5m-12-4.5h7.5a2.25 2.25 0 0 1 0 4.5H9.75a2.25 2.25 0 0 0 0 4.5h8.25',
        tone: 'emerald',
    },
    {
        key: 'sales_month',
        label: 'Monthly Sales',
        value: currency(props.stats.sales_month),
        helper: 'Total confirmed sales this month',
        icon: 'M4.5 19.5h15M7.5 16.5V9m4.5 7.5V6m4.5 10.5v-4.5',
        tone: 'amber',
    },
    {
        key: 'orders_today',
        label: 'Orders Today',
        value: String(props.stats.orders_today),
        helper: 'Tickets completed by your team',
        icon: 'M7.5 3.75h9l3 3v13.5a1.5 1.5 0 0 1-1.5 1.5h-12a1.5 1.5 0 0 1-1.5-1.5V5.25a1.5 1.5 0 0 1 1.5-1.5Zm1.5 6h6m-6 3h6m-6 3h3',
        tone: 'blue',
    },
    {
        key: 'low_stock_count',
        label: 'Low Stock Alerts',
        value: String(props.stats.low_stock_count),
        helper: 'Items below minimum inventory threshold',
        icon: 'M12 9v4.5m0 3h.008v.008H12V16.5Zm8.25 2.25L13.5 4.5a1.73 1.73 0 0 0-3 0L3.75 18.75A1.73 1.73 0 0 0 5.25 21h13.5a1.73 1.73 0 0 0 1.5-2.25Z',
        tone: 'rose',
    },
    {
        key: 'open_sessions',
        label: 'Active Cashier Sessions',
        value: String(props.stats.open_sessions),
        helper: 'Registers currently open in POS',
        icon: 'M8.25 6.75h7.5m-7.5 4.5h7.5m-7.5 4.5h4.5M4.5 4.5h15v15h-15z',
        tone: 'violet',
    },
]);

const toneStyles: Record<StatCard['tone'], { top: string; chip: string; icon: string }> = {
    emerald: {
        top: 'bg-gradient-to-r from-emerald-500 via-emerald-400 to-teal-400',
        chip: 'bg-emerald-500/12',
        icon: 'text-emerald-600',
    },
    amber: {
        top: 'bg-gradient-to-r from-amber-500 via-orange-400 to-amber-300',
        chip: 'bg-amber-500/12',
        icon: 'text-amber-600',
    },
    blue: {
        top: 'bg-gradient-to-r from-sky-500 via-cyan-400 to-blue-400',
        chip: 'bg-sky-500/12',
        icon: 'text-sky-600',
    },
    rose: {
        top: 'bg-gradient-to-r from-rose-500 via-pink-400 to-orange-300',
        chip: 'bg-rose-500/12',
        icon: 'text-rose-600',
    },
    violet: {
        top: 'bg-gradient-to-r from-violet-500 via-indigo-400 to-violet-300',
        chip: 'bg-violet-500/12',
        icon: 'text-violet-600',
    },
};

const categoryIcons: Record<string, string> = {
    restaurants: 'R',
    stores: 'S',
    pharmacies: 'P',
    cafes: 'C',
    supermarkets: 'M',
    electronics: 'E',
    fashion: 'F',
    services: 'SV',
};

const categoryGradients: Record<string, string> = {
    restaurants: 'from-orange-500 via-red-500 to-rose-500',
    stores: 'from-blue-500 via-indigo-500 to-violet-500',
    pharmacies: 'from-emerald-500 via-teal-500 to-cyan-500',
    cafes: 'from-amber-500 via-orange-400 to-yellow-400',
    supermarkets: 'from-green-500 via-emerald-400 to-teal-400',
    electronics: 'from-slate-600 via-slate-500 to-blue-500',
    fashion: 'from-pink-500 via-rose-400 to-fuchsia-500',
    services: 'from-sky-500 via-blue-400 to-indigo-400',
};

const getCategoryIcon = (slug: string): string => {
    return categoryIcons[slug?.toLowerCase()] ?? 'BX';
};

const getCategoryGradient = (slug: string): string => {
    return categoryGradients[slug?.toLowerCase()] ?? 'from-slate-500 via-slate-400 to-slate-300';
};

const resolveCategoryImage = (category: CategoryGroup): string | null => {
    const cardWithImage = category.cards.find((card) => card.image_path);

    return cardWithImage?.image_path ? `/storage/${cardWithImage.image_path}` : null;
};

const resolveCategoryHref = (category: CategoryGroup): string => {
    const firstCard = category.cards[0];

    if (firstCard?.slug) {
        return route('cards.show', firstCard.slug);
    }

    return route('categories.show', category.slug);
};

const paymentStatusClass = (status: string): string => {
    const normalized = status.toLowerCase();

    if (normalized === 'paid') {
        return 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200';
    }

    if (normalized === 'partial') {
        return 'bg-amber-100 text-amber-700 ring-1 ring-amber-200';
    }

    return 'bg-slate-100 text-slate-600 ring-1 ring-slate-200';
};

const stockProgress = (product: LowStockProduct): number => {
    const threshold = Math.max(Number(product.low_stock_threshold), 1);
    const quantity = Math.max(Number(product.stock_quantity), 0);

    return Math.min(100, Math.round((quantity / threshold) * 100));
};

const stockAlertLabel = (product: LowStockProduct): string => {
    const threshold = Math.max(Number(product.low_stock_threshold), 1);
    const quantity = Math.max(Number(product.stock_quantity), 0);

    if (quantity === 0) {
        return 'Out of stock';
    }

    if (quantity <= threshold * 0.4) {
        return 'Critical';
    }

    return 'Low';
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <section
                class="relative overflow-hidden rounded-[28px] bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-900 p-6 text-white shadow-[0_24px_70px_-34px_rgba(15,23,42,0.95)] sm:p-8"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_16%_22%,rgba(251,146,60,0.35),transparent_46%),radial-gradient(circle_at_82%_16%,rgba(16,185,129,0.28),transparent_44%),radial-gradient(circle_at_60%_110%,rgba(249,115,22,0.22),transparent_45%)]"
                />

                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-200/90">
                            SwiftPOS Command Center
                        </p>
                        <h1 class="mt-3 text-3xl font-semibold leading-tight sm:text-4xl">
                            Restaurant and Retail Performance
                        </h1>
                        <p class="mt-3 text-sm text-slate-200 sm:text-base">
                            Monitor sales, cashier activity, and inventory risk from one professional workspace.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Link
                            v-if="canUsePos"
                            :href="route('pos.index')"
                            class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:-translate-y-0.5 hover:bg-slate-100"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5.25v13.5M5.25 12h13.5" />
                            </svg>
                            Open Cashier
                        </Link>

                        <Link
                            v-if="canUsePos"
                            :href="route('orders.index')"
                            class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:border-white/35 hover:bg-white/15"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75h9l3 3v13.5a1.5 1.5 0 0 1-1.5 1.5h-12a1.5 1.5 0 0 1-1.5-1.5V5.25a1.5 1.5 0 0 1 1.5-1.5Zm1.5 6h6m-6 3h6m-6 3h3" />
                            </svg>
                            Review Orders
                        </Link>
                    </div>
                </div>
            </section>
        </template>

        <div class="space-y-6">
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <article
                    v-for="(card, index) in statCards"
                    :key="card.key"
                    class="animate-pos-fade-in group relative overflow-hidden rounded-2xl bg-white/60 backdrop-blur-md backdrop-saturate-150 border border-white/30 p-5 shadow-xl transition duration-300 hover:-translate-y-0.5"
                    :style="{ animationDelay: `${index * 80}ms` }"
                >
                    <div class="absolute inset-x-0 top-0 h-1.5" :class="toneStyles[card.tone].top" />

                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ card.label }}</p>
                            <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ card.value }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ card.helper }}</p>
                        </div>
                        <span
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl"
                            :class="[toneStyles[card.tone].chip, toneStyles[card.tone].icon]"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="card.icon" />
                            </svg>
                        </span>
                    </div>
                </article>
            </section>

            <!-- Category Cards Section -->
            <section>
                <div class="mb-4 flex items-center justify-between">
                    <div class="w-full rounded-xl border border-slate-200 bg-white/70 px-6 py-4 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Marketplace</p>
                            <h2 class="mt-1 text-xl font-semibold text-slate-900">Browse Categories</h2>
                        </div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                            {{ props.categories.length }} categories
                        </p>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <Link
                        v-for="(category, index) in props.categories"
                        :key="category.id"
                        :href="resolveCategoryHref(category)"
                        class="animate-pos-fade-in group relative flex flex-col overflow-hidden rounded-3xl border border-white/40 bg-white/60 backdrop-blur-lg shadow-xl transition duration-300 hover:-translate-y-1 hover:shadow-2xl"
                        :style="{ animationDelay: `${index * 100}ms` }"
                    >
                        <!-- Category gradient header -->
                        <div
                            v-if="resolveCategoryImage(category)"
                            class="relative flex h-[204px] items-center justify-center overflow-hidden bg-slate-900/5"
                        >
                            <img
                                :src="resolveCategoryImage(category) ?? ''"
                                :alt="category.name"
                                class="absolute inset-0 h-full w-full object-cover object-center"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-slate-900/10 to-transparent" />
                        </div>
                        <div
                            v-else
                            class="relative flex h-[204px] items-center justify-center bg-gradient-to-br"
                            :class="getCategoryGradient(category.slug)"
                        >
                            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(255,255,255,0.25),transparent_50%)]" />
                            <span class="relative text-4xl font-semibold tracking-[0.18em] drop-shadow-lg transition-transform duration-300 group-hover:scale-110">
                                {{ getCategoryIcon(category.slug) }}
                            </span>
                            <div class="absolute bottom-0 left-0 right-0 h-16 bg-gradient-to-t from-black/20 to-transparent" />
                        </div>

                        <!-- Category info -->
                        <div class="flex flex-1 flex-col bg-white/90 px-5 py-4">
                            <div class="flex flex-1 flex-col items-center gap-2 text-center">
                                <h3 class="text-lg font-bold text-slate-900">
                                    {{ category.name }}
                                </h3>
                                <p class="text-sm text-slate-500 line-clamp-2">
                                    {{ category.description ?? 'Explore businesses and their products.' }}
                                </p>
                            </div>
                            <div class="mt-3 flex items-center justify-between">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                    {{ category.cards.length }} {{ category.cards.length === 1 ? 'business' : 'businesses' }}
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-emerald-700 shadow-sm transition group-hover:border-emerald-200 group-hover:bg-emerald-50"
                                >
                                    Explore
                                    <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </Link>

                    <div
                        v-if="props.categories.length === 0"
                        class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-white/70 p-8 text-center text-sm text-slate-500"
                    >
                        No categories are configured yet.
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.5fr,1fr]">
                <article class="rounded-3xl bg-white/60 backdrop-blur-md backdrop-saturate-150 border border-white/30 shadow-xl">
                    <header class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/12 text-amber-600">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75h9l3 3v13.5a1.5 1.5 0 0 1-1.5 1.5h-12a1.5 1.5 0 0 1-1.5-1.5V5.25a1.5 1.5 0 0 1 1.5-1.5Zm1.5 6h6m-6 3h6m-6 3h3" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">Recent Orders</h3>
                                <p class="text-xs text-slate-500">Latest completed sales activity</p>
                            </div>
                        </div>

                        <Link
                            v-if="canUsePos"
                            :href="route('orders.index')"
                            class="text-sm font-semibold text-emerald-700 transition hover:text-emerald-800"
                        >
                            View all
                        </Link>
                    </header>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-slate-50/90 text-xs uppercase tracking-[0.08em] text-slate-500">
                                <tr>
                                    <th class="px-5 py-3 font-semibold">Order</th>
                                    <th class="px-5 py-3 font-semibold">Cashier</th>
                                    <th class="px-5 py-3 font-semibold">Total</th>
                                    <th class="px-5 py-3 font-semibold">Payment</th>
                                    <th class="px-5 py-3 font-semibold">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="recentOrders.length === 0">
                                    <td class="px-5 py-8 text-center text-slate-500" colspan="5">
                                        No orders have been completed yet.
                                    </td>
                                </tr>
                                <tr
                                    v-for="order in recentOrders"
                                    :key="order.id"
                                    class="border-t border-slate-100 transition hover:bg-slate-50/70"
                                >
                                    <td class="px-5 py-3 font-semibold text-slate-800">
                                        <Link :href="route('orders.show', order.id)" class="transition hover:text-emerald-700">
                                            {{ order.order_number }}
                                        </Link>
                                    </td>
                                    <td class="px-5 py-3 text-slate-600">{{ order.user.name }}</td>
                                    <td class="px-5 py-3 font-medium text-slate-900">{{ currency(order.total_amount) }}</td>
                                    <td class="px-5 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize"
                                            :class="paymentStatusClass(order.payment_status)"
                                        >
                                            {{ order.payment_status.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-slate-500">{{ formatTime(order.placed_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="rounded-3xl bg-white/60 backdrop-blur-md backdrop-saturate-150 border border-white/30 shadow-xl">
                    <header class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-rose-500/12 text-rose-600">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4.5m0 3h.008v.008H12V16.5Zm8.25 2.25L13.5 4.5a1.73 1.73 0 0 0-3 0L3.75 18.75A1.73 1.73 0 0 0 5.25 21h13.5a1.73 1.73 0 0 0 1.5-2.25Z" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">Low Stock Alerts</h3>
                                <p class="text-xs text-slate-500">Products that need replenishment</p>
                            </div>
                        </div>

                        <Link
                            :href="route('inventory.index')"
                            class="text-sm font-semibold text-emerald-700 transition hover:text-emerald-800"
                        >
                            Manage stock
                        </Link>
                    </header>

                    <div class="divide-y divide-slate-100">
                        <div v-if="lowStockProducts.length === 0" class="px-5 py-8 text-sm text-slate-500">
                            Inventory levels are healthy.
                        </div>

                        <div
                            v-for="product in lowStockProducts"
                            :key="product.id"
                            class="space-y-3 px-5 py-4"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ product.name }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ product.sku }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-rose-600">
                                        {{ Number(product.stock_quantity).toFixed(2) }} {{ product.unit }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        Min {{ Number(product.low_stock_threshold).toFixed(2) }}
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        class="h-full rounded-full bg-gradient-to-r from-rose-500 via-orange-400 to-amber-300"
                                        :style="{ width: `${stockProgress(product)}%` }"
                                    />
                                </div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-rose-600">
                                    {{ stockAlertLabel(product) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
