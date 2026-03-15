<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface OrderItem {
    id: number;
    product_name: string;
    sku?: string | null;
    quantity: string | number;
    unit_price: string | number;
    discount_amount: string | number;
    tax_amount: string | number;
    line_total: string | number;
}

interface Payment {
    id: number;
    method: string;
    amount: string | number;
    status: string;
    paid_at: string;
    reference?: string | null;
}

interface Order {
    id: number;
    order_number: string;
    order_type: string;
    status: string;
    payment_status: string;
    customer_name?: string | null;
    notes?: string | null;
    subtotal: string | number;
    discount_amount: string | number;
    tax_amount: string | number;
    total_amount: string | number;
    paid_amount: string | number;
    change_amount: string | number;
    placed_at: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
    items: OrderItem[];
    payments: Payment[];
}

defineProps<{
    order: Order;
}>();

const currency = (value: string | number): string =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value));
</script>

<template>
    <Head :title="order.order_number" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-slate-800">Order {{ order.order_number }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ new Date(order.placed_at).toLocaleString() }}</p>
                </div>
                <Link :href="route('orders.index')" class="pos-btn pos-btn-ghost text-sm">
                    Back to orders
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 pb-8 sm:px-6 lg:px-8">
            <div class="grid gap-4 lg:grid-cols-4">
                <div class="pos-panel-solid p-4">
                    <p class="text-sm text-slate-500">Total</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ currency(order.total_amount) }}</p>
                </div>
                <div class="pos-panel-solid p-4">
                    <p class="text-sm text-slate-500">Paid</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ currency(order.paid_amount) }}</p>
                </div>
                <div class="pos-panel-solid p-4">
                    <p class="text-sm text-slate-500">Change</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ currency(order.change_amount) }}</p>
                </div>
                <div class="pos-panel-solid p-4">
                    <p class="text-sm text-slate-500">Payment Status</p>
                    <p class="mt-1 text-lg font-semibold capitalize text-slate-900">{{ order.payment_status }}</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="pos-panel lg:col-span-2 overflow-hidden">
                    <div class="pos-panel-header">
                        <h3 class="pos-panel-title">Items</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="pos-table">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3">Product</th>
                                    <th class="px-4 py-3">Qty</th>
                                    <th class="px-4 py-3">Unit Price</th>
                                    <th class="px-4 py-3">Discount</th>
                                    <th class="px-4 py-3">Tax</th>
                                    <th class="px-4 py-3">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in order.items" :key="item.id" class="border-t border-slate-100">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-slate-800">{{ item.product_name }}</p>
                                        <p class="text-xs text-slate-500">{{ item.sku }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ Number(item.quantity).toFixed(2) }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ currency(item.unit_price) }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ currency(item.discount_amount) }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ currency(item.tax_amount) }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ currency(item.line_total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="pos-panel p-4">
                        <h3 class="pos-panel-title">Order Info</h3>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Cashier</dt>
                                <dd class="font-medium text-slate-800">{{ order.user.name }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Customer</dt>
                                <dd class="text-slate-700">{{ order.customer_name || 'Walk-in' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Type</dt>
                                <dd class="capitalize text-slate-700">{{ order.order_type.replace('_', ' ') }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Status</dt>
                                <dd class="capitalize text-slate-700">{{ order.status }}</dd>
                            </div>
                            <div class="border-t border-slate-100 pt-2">
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">Subtotal</dt>
                                    <dd class="text-slate-700">{{ currency(order.subtotal) }}</dd>
                                </div>
                                <div class="mt-1 flex justify-between">
                                    <dt class="text-slate-500">Discount</dt>
                                    <dd class="text-slate-700">{{ currency(order.discount_amount) }}</dd>
                                </div>
                                <div class="mt-1 flex justify-between">
                                    <dt class="text-slate-500">Tax</dt>
                                    <dd class="text-slate-700">{{ currency(order.tax_amount) }}</dd>
                                </div>
                            </div>
                        </dl>
                    </div>

                    <div class="pos-panel p-4">
                        <h3 class="pos-panel-title">Payments</h3>
                        <div class="mt-3 space-y-3 text-sm">
                            <div v-if="order.payments.length === 0" class="text-slate-500">No payment records.</div>
                            <div v-for="payment in order.payments" :key="payment.id" class="rounded-2xl border border-slate-200 bg-white/80 p-3">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium capitalize text-slate-800">{{ payment.method.replace('_', ' ') }}</span>
                                    <span class="font-semibold text-slate-900">{{ currency(payment.amount) }}</span>
                                </div>
                                <p class="mt-1 text-xs capitalize text-slate-500">
                                    {{ payment.status }} on {{ new Date(payment.paid_at).toLocaleString() }}
                                </p>
                                <p v-if="payment.reference" class="mt-1 text-xs text-slate-500">
                                    Ref: {{ payment.reference }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
