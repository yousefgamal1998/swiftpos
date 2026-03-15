<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

interface PaymentRow {
    id: number;
    method: string;
    amount: string | number;
    status: string;
    reference?: string | null;
    paid_at: string;
    order?: {
        id: number;
        order_number: string;
    } | null;
    user?: {
        id: number;
        name: string;
    } | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    payments: {
        data: PaymentRow[];
        links: PaginationLink[];
    };
    filters: {
        search?: string;
        status?: string;
        method?: string;
        from?: string;
        to?: string;
    };
    methods: string[];
    statuses: string[];
}>();

const filters = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    method: props.filters.method ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const applyFilters = (): void => {
    router.get(
        route('payments.index'),
        {
            search: filters.search || undefined,
            status: filters.status || undefined,
            method: filters.method || undefined,
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

const formatDate = (value?: string | null): string =>
    value ? new Date(value).toLocaleString() : '--';

const statusClass = (status: string): string => {
    const normalized = status.toLowerCase();

    if (normalized === 'completed' || normalized === 'paid') {
        return 'pos-badge-success';
    }

    if (normalized === 'pending' || normalized === 'processing') {
        return 'pos-badge-warning';
    }

    if (normalized === 'failed' || normalized === 'voided') {
        return 'pos-badge-danger';
    }

    return 'pos-badge-neutral';
};
</script>

<template>
    <Head title="Payments" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-slate-800">Payments</h2>
                <Link :href="route('orders.index')" class="pos-btn pos-btn-outline">
                    View Orders
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
                        placeholder="Order, reference, cashier"
                    />
                    <select v-model="filters.method" class="pos-input">
                        <option value="">All methods</option>
                        <option v-for="method in methods" :key="method" :value="method">
                            {{ method.replace('_', ' ') }}
                        </option>
                    </select>
                    <select v-model="filters.status" class="pos-input">
                        <option value="">All status</option>
                        <option v-for="status in statuses" :key="status" :value="status">
                            {{ status.replace('_', ' ') }}
                        </option>
                    </select>
                    <input v-model="filters.from" type="date" class="pos-input" />
                    <input v-model="filters.to" type="date" class="pos-input" />
                    <button type="button" class="pos-btn pos-btn-secondary" @click="applyFilters">
                        Filter
                    </button>
                </div>
            </div>

            <div class="pos-panel mt-4 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="pos-table">
                        <thead>
                            <tr>
                                <th class="px-4 py-3">Payment</th>
                                <th class="px-4 py-3">Order</th>
                                <th class="px-4 py-3">Method</th>
                                <th class="px-4 py-3">Amount</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Paid At</th>
                                <th class="px-4 py-3">Cashier</th>
                                <th class="px-4 py-3">Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="payments.data.length === 0">
                                <td class="px-4 py-6 text-center text-slate-500" colspan="8">
                                    No payments found.
                                </td>
                            </tr>
                            <tr v-for="payment in payments.data" :key="payment.id">
                                <td class="px-4 py-3 font-medium text-slate-800">#{{ payment.id }}</td>
                                <td class="px-4 py-3 text-slate-700">
                                    <Link
                                        v-if="payment.order"
                                        :href="route('orders.show', payment.order.id)"
                                        class="font-medium text-emerald-700 hover:text-emerald-800"
                                    >
                                        {{ payment.order.order_number }}
                                    </Link>
                                    <span v-else class="text-slate-400">--</span>
                                </td>
                                <td class="px-4 py-3 capitalize text-slate-600">
                                    {{ payment.method.replace('_', ' ') }}
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ currency(payment.amount) }}</td>
                                <td class="px-4 py-3">
                                    <span class="pos-badge capitalize" :class="statusClass(payment.status)">
                                        {{ payment.status.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ formatDate(payment.paid_at) }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ payment.user?.name || 'System' }}
                                </td>
                                <td class="px-4 py-3 text-slate-500">
                                    {{ payment.reference || '--' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 px-4 py-3">
                    <template v-for="link in payments.links" :key="link.label">
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
