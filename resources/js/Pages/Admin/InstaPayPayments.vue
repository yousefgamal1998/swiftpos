<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type OrderUser = {
    id: number;
    name: string;
    email: string;
};

type InstaPayOrder = {
    id: number;
    order_number: string;
    total_amount: string;
    payment_status: string;
    status: string;
    payment_method: string;
    instapay_screenshot: string | null;
    placed_at: string;
    created_at: string;
    user: OrderUser | null;
};

type PaginatedOrders = {
    data: InstaPayOrder[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    orders: PaginatedOrders;
    filter: string;
}>();

const rejectingOrderId = ref<number | null>(null);
const rejectReason = ref('');

const currency = (val: string | number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 2 }).format(Number(val));

const formatDate = (val: string) => {
    const d = new Date(val);
    return isNaN(d.getTime())
        ? '--'
        : new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }).format(d);
};

const statusBadge = (status: string) => {
    const map: Record<string, string> = {
        pending: 'bg-blue-100 text-blue-700 ring-blue-200',
        waiting_confirmation: 'bg-amber-100 text-amber-700 ring-amber-200',
        paid: 'bg-emerald-100 text-emerald-700 ring-emerald-200',
        rejected: 'bg-rose-100 text-rose-700 ring-rose-200',
    };
    return map[status] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
};

const statusLabel = (status: string) =>
    status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

const approve = (order: InstaPayOrder) => {
    if (!confirm(`Approve payment for Order #${order.order_number}?`)) return;
    router.post(route('admin.instapay.approve', order.id), {}, { preserveScroll: true });
};

const startReject = (orderId: number) => {
    rejectingOrderId.value = orderId;
    rejectReason.value = '';
};

const confirmReject = () => {
    if (rejectingOrderId.value === null) return;
    router.post(
        route('admin.instapay.reject', rejectingOrderId.value),
        { reason: rejectReason.value },
        {
            preserveScroll: true,
            onFinish: () => { rejectingOrderId.value = null; },
        },
    );
};

const cancelReject = () => {
    rejectingOrderId.value = null;
};

const isPendingFilter = computed(() => props.filter === 'pending');
</script>

<template>
    <Head title="InstaPay Payments" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Admin</p>
                    <h2 class="mt-1 text-2xl font-bold text-slate-900">InstaPay Payments</h2>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('admin.instapay.index', { filter: 'pending' })"
                        class="rounded-xl px-4 py-2 text-sm font-semibold transition"
                        :class="isPendingFilter ? 'bg-emerald-600 text-white shadow-md' : 'bg-white/80 text-slate-600 border border-slate-200 hover:bg-slate-50'"
                    >
                        Pending
                    </Link>
                    <Link
                        :href="route('admin.instapay.index', { filter: 'all' })"
                        class="rounded-xl px-4 py-2 text-sm font-semibold transition"
                        :class="!isPendingFilter ? 'bg-emerald-600 text-white shadow-md' : 'bg-white/80 text-slate-600 border border-slate-200 hover:bg-slate-50'"
                    >
                        All
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-4">
            <article class="overflow-hidden rounded-3xl border border-white/40 bg-white/70 shadow-xl backdrop-blur-md">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50/90 text-xs uppercase tracking-[0.08em] text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Order</th>
                                <th class="px-5 py-3 font-semibold">Customer</th>
                                <th class="px-5 py-3 font-semibold">Amount</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                                <th class="px-5 py-3 font-semibold">Screenshot</th>
                                <th class="px-5 py-3 font-semibold">Date</th>
                                <th class="px-5 py-3 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="props.orders.data.length === 0">
                                <td class="px-5 py-12 text-center text-slate-500" colspan="7">
                                    No InstaPay payments found.
                                </td>
                            </tr>
                            <tr
                                v-for="order in props.orders.data"
                                :key="order.id"
                                class="border-t border-slate-100 transition hover:bg-slate-50/70"
                            >
                                <td class="px-5 py-3">
                                    <Link :href="route('instapay.show', order.id)" class="font-semibold text-emerald-700 hover:underline">
                                        {{ order.order_number }}
                                    </Link>
                                </td>
                                <td class="px-5 py-3">
                                    <div v-if="order.user">
                                        <p class="font-medium text-slate-800">{{ order.user.name }}</p>
                                        <p class="text-xs text-slate-500">{{ order.user.email }}</p>
                                    </div>
                                    <span v-else class="text-slate-400">Guest</span>
                                </td>
                                <td class="px-5 py-3 font-semibold text-slate-900">
                                    {{ currency(order.total_amount) }}
                                </td>
                                <td class="px-5 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1"
                                        :class="statusBadge(order.payment_status)"
                                    >
                                        {{ statusLabel(order.payment_status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <a v-if="order.instapay_screenshot" :href="`/storage/${order.instapay_screenshot}`" target="_blank" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 hover:underline">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        View
                                    </a>
                                    <span v-else class="text-xs text-slate-400">—</span>
                                </td>
                                <td class="px-5 py-3 text-slate-500">
                                    {{ formatDate(order.placed_at ?? order.created_at) }}
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <div v-if="['pending', 'waiting_confirmation'].includes(order.payment_status)" class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700"
                                            @click="approve(order)"
                                        >
                                            Approve
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50"
                                            @click="startReject(order.id)"
                                        >
                                            Reject
                                        </button>
                                    </div>
                                    <span v-else class="text-xs text-slate-400">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="props.orders.last_page > 1" class="flex items-center justify-center gap-1 border-t border-slate-100 px-5 py-4">
                    <Link
                        v-for="link in props.orders.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                        :class="link.active ? 'bg-emerald-600 text-white' : link.url ? 'text-slate-600 hover:bg-slate-100' : 'text-slate-300 cursor-default'"
                        v-html="link.label"
                    />
                </div>
            </article>
        </div>

        <!-- Reject Modal -->
        <Teleport to="body">
            <div v-if="rejectingOrderId !== null" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" @click.self="cancelReject">
                <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                    <h3 class="text-lg font-bold text-slate-900">Reject Payment</h3>
                    <p class="mt-1 text-sm text-slate-500">Provide an optional reason for rejecting this payment.</p>
                    <textarea
                        v-model="rejectReason"
                        class="mt-4 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                        rows="3"
                        placeholder="Reason for rejection (optional)..."
                    />
                    <div class="mt-4 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                            @click="cancelReject"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700"
                            @click="confirmReject"
                        >
                            Reject Payment
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
