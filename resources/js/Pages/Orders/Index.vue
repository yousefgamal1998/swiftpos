<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

interface OrderRow {
    id: number;
    order_number: string;
    order_type: string;
    customer_name?: string | null;
    total_amount: string | number;
    payment_status: string;
    status: string;
    placed_at: string;
    user: {
        id: number;
        name: string;
    };
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    orders: {
        data: OrderRow[];
        links: PaginationLink[];
    };
    filters: {
        search?: string;
        status?: string;
        payment_status?: string;
        from?: string;
        to?: string;
    };
    statuses: string[];
    paymentStatuses: string[];
}>();

const filters = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    payment_status: props.filters.payment_status ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const applyFilters = (): void => {
    router.get(
        route('orders.index'),
        {
            search: filters.search || undefined,
            status: filters.status || undefined,
            payment_status: filters.payment_status || undefined,
            from: filters.from || undefined,
            to: filters.to || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const currency = (value: string | number): string =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value));
</script>

<template>
    <Head title="Orders" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-slate-800">Orders</h2>
                <Link :href="route('pos.index')" class="pos-btn pos-btn-primary">
                    New Sale
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8">
            <div class="pos-panel p-5">
                <div class="grid gap-3 md:grid-cols-6">
                    <input
                        v-model="filters.search"
                        type="text"
                        class="pos-input"
                        placeholder="Order number"
                    />
                    <select v-model="filters.status" class="pos-input">
                        <option value="">All status</option>
                        <option v-for="status in statuses" :key="status" :value="status">
                            {{ status }}
                        </option>
                    </select>
                    <select
                        v-model="filters.payment_status"
                        class="pos-input"
                    >
                        <option value="">All payments</option>
                        <option v-for="status in paymentStatuses" :key="status" :value="status">
                            {{ status }}
                        </option>
                    </select>
                    <input
                        v-model="filters.from"
                        type="date"
                        class="pos-input"
                    />
                    <input
                        v-model="filters.to"
                        type="date"
                        class="pos-input"
                    />
                    <button
                        type="button"
                        @click="applyFilters"
                        class="pos-btn pos-btn-secondary"
                    >
                        Filter
                    </button>
                </div>
            </div>

            <div class="pos-panel mt-4 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="pos-table">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3">Order</th>
                                <th class="px-4 py-3">Cashier</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Total</th>
                                <th class="px-4 py-3">Payment</th>
                                <th class="px-4 py-3">Placed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="orders.data.length === 0">
                                <td class="px-4 py-6 text-center text-slate-500" colspan="6">No orders found.</td>
                            </tr>
                            <tr v-for="order in orders.data" :key="order.id" class="border-t border-slate-100">
                                <td class="px-4 py-3 font-medium text-slate-800">
                                    <Link :href="route('orders.show', order.id)" class="hover:text-emerald-700">
                                        {{ order.order_number }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ order.user.name }}</td>
                                <td class="px-4 py-3 capitalize text-slate-600">{{ order.order_type.replace('_', ' ') }}</td>
                                <td class="px-4 py-3 text-slate-800">{{ currency(order.total_amount) }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="pos-badge capitalize"
                                        :class="order.payment_status === 'paid' ? 'pos-badge-success' : 'pos-badge-warning'"
                                    >
                                        {{ order.payment_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ new Date(order.placed_at).toLocaleString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 px-4 py-3">
                    <template v-for="link in orders.links" :key="link.label">
                        <span
                            v-if="!link.url"
                            class="pos-btn border border-slate-200 bg-white/70 px-3 py-1 text-xs text-slate-400"
                            v-html="link.label"
                        />
                        <Link
                            v-else
                            :href="link.url"
                            class="pos-btn px-3 py-1 text-xs"
                            :class="link.active ? 'pos-btn-primary' : 'pos-btn-outline'"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
