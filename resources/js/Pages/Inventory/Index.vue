<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

interface ProductRow {
    id: number;
    name: string;
    sku: string;
    stock_quantity: string | number;
    low_stock_threshold: string | number;
    unit: string;
    track_inventory: boolean;
    category?: {
        id: number;
        name: string;
    } | null;
}

interface MovementRow {
    id: number;
    movement_type: string;
    quantity: string | number;
    previous_stock: string | number;
    new_stock: string | number;
    note?: string | null;
    created_at: string;
    product?: {
        id: number;
        name: string;
        sku: string;
        unit: string;
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
    products: {
        data: ProductRow[];
        links: PaginationLink[];
    };
    movements: MovementRow[];
    filters: {
        search?: string;
    };
}>();

const filterState = reactive({
    search: props.filters.search ?? '',
});

const adjustmentForm = useForm({
    product_id: '',
    quantity: '1',
    direction: 'in',
    movement_type: 'adjustment',
    note: '',
});

const submitAdjustment = (): void => {
    if (!adjustmentForm.product_id) {
        return;
    }

    adjustmentForm.post(route('inventory.adjust', adjustmentForm.product_id), {
        preserveScroll: true,
        onSuccess: () => {
            adjustmentForm.reset('quantity', 'direction', 'movement_type', 'note');
            adjustmentForm.quantity = '1';
            adjustmentForm.direction = 'in';
            adjustmentForm.movement_type = 'adjustment';
        },
    });
};
</script>

<template>
    <Head title="Inventory" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Inventory</h2>
        </template>

        <div class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="pos-panel p-5">
                    <h3 class="text-sm font-semibold text-slate-800">Quick Adjustment</h3>
                    <form @submit.prevent="submitAdjustment" class="mt-4 space-y-3">
                        <div>
                            <label class="block text-sm text-slate-700">Product</label>
                            <select
                                v-model="adjustmentForm.product_id"
                                class="pos-input mt-1"
                                required
                            >
                                <option value="">Select a product</option>
                                <option v-for="product in products.data" :key="product.id" :value="String(product.id)">
                                    {{ product.name }} ({{ product.sku }})
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Select from products in the current page.</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm text-slate-700">Direction</label>
                                <select
                                    v-model="adjustmentForm.direction"
                                    class="pos-input mt-1"
                                >
                                    <option value="in">Stock In</option>
                                    <option value="out">Stock Out</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-slate-700">Qty</label>
                                <input
                                    v-model="adjustmentForm.quantity"
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    class="pos-input mt-1"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-700">Movement Type</label>
                            <select
                                v-model="adjustmentForm.movement_type"
                                class="pos-input mt-1"
                            >
                                <option value="adjustment">Adjustment</option>
                                <option value="restock">Restock</option>
                                <option value="purchase">Purchase</option>
                                <option value="return">Return</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-700">Note</label>
                            <textarea
                                v-model="adjustmentForm.note"
                                rows="2"
                                class="pos-input mt-1"
                            />
                        </div>
                        <button
                            type="submit"
                            :disabled="adjustmentForm.processing"
                            class="pos-btn pos-btn-secondary w-full disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Apply Adjustment
                        </button>
                        <p v-if="adjustmentForm.errors.quantity" class="text-xs text-rose-600">
                            {{ adjustmentForm.errors.quantity }}
                        </p>
                    </form>
                </div>

                <div class="lg:col-span-2">
                    <div class="pos-panel overflow-hidden">
                        <div class="pos-panel-header">
                            <h3 class="pos-panel-title">Stock Levels</h3>
                            <Link :href="route('products.index')" class="pos-btn pos-btn-ghost text-sm">
                                Manage products
                            </Link>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="pos-table">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3">Product</th>
                                        <th class="px-4 py-3">SKU</th>
                                        <th class="px-4 py-3">Category</th>
                                        <th class="px-4 py-3">Stock</th>
                                        <th class="px-4 py-3">Threshold</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="products.data.length === 0">
                                        <td class="px-4 py-4 text-slate-500" colspan="5">No products available.</td>
                                    </tr>
                                    <tr v-for="product in products.data" :key="product.id" class="border-t border-slate-100">
                                        <td class="px-4 py-3 font-medium text-slate-800">{{ product.name }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ product.sku }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ product.category?.name || 'Uncategorized' }}</td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="font-semibold"
                                                :class="
                                                    Number(product.stock_quantity) <= Number(product.low_stock_threshold)
                                                        ? 'text-rose-600'
                                                        : 'text-slate-800'
                                                "
                                            >
                                                {{ Number(product.stock_quantity).toFixed(2) }} {{ product.unit }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">
                                            {{ Number(product.low_stock_threshold).toFixed(2) }} {{ product.unit }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 px-4 py-3">
                            <template v-for="link in products.links" :key="link.label">
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
            </div>

            <div class="pos-panel mt-6 overflow-hidden">
                <div class="pos-panel-header">
                    <h3 class="pos-panel-title">Recent Movements</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="pos-table">
                        <thead>
                            <tr>
                                <th class="px-4 py-3">Time</th>
                                <th class="px-4 py-3">Product</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Qty</th>
                                <th class="px-4 py-3">Stock</th>
                                <th class="px-4 py-3">By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="movements.length === 0">
                                <td class="px-4 py-4 text-slate-500" colspan="6">No inventory movements yet.</td>
                            </tr>
                            <tr v-for="movement in movements" :key="movement.id" class="border-t border-slate-100">
                                <td class="px-4 py-3 text-slate-600">{{ new Date(movement.created_at).toLocaleString() }}</td>
                                <td class="px-4 py-3 font-medium text-slate-800">
                                    {{ movement.product?.name || 'Unknown product' }}
                                </td>
                                <td class="px-4 py-3 capitalize text-slate-700">{{ movement.movement_type }}</td>
                                <td class="px-4 py-3">
                                    <span :class="Number(movement.quantity) < 0 ? 'text-rose-600' : 'text-emerald-700'">
                                        {{ Number(movement.quantity).toFixed(2) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ Number(movement.previous_stock).toFixed(2) }} ->
                                    {{ Number(movement.new_stock).toFixed(2) }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ movement.user?.name || 'System' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
