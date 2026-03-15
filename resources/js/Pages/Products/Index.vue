<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

interface Category {
    id: number;
    name: string;
}

interface ProductRow {
    id: number;
    name: string;
    sku: string;
    category_id: number | null;
    type: string;
    price: string | number;
    stock_quantity: string | number;
    low_stock_threshold: string | number;
    unit: string;
    is_active: boolean;
    category?: Category | null;
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
    categories: Category[];
    filters: {
        search?: string;
        category_id?: string;
        status?: string;
    };
}>();

const filterForm = reactive({
    search: props.filters.search ?? '',
    category_id: props.filters.category_id ?? '',
    status: props.filters.status ?? 'all',
});

const applyFilters = (): void => {
    router.get(
        route('products.index'),
        {
            search: filterForm.search || undefined,
            category_id: filterForm.category_id || undefined,
            status: filterForm.status !== 'all' ? filterForm.status : undefined,
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
    <Head title="Products" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-slate-800">Products</h2>
                <Link :href="route('products.create')" class="pos-btn pos-btn-primary">
                    New Product
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8">
            <div class="pos-panel p-5">
                <div class="grid gap-3 md:grid-cols-4">
                    <input
                        v-model="filterForm.search"
                        type="text"
                        class="pos-input"
                        placeholder="Search by name, SKU, barcode"
                    />
                    <select
                        v-model="filterForm.category_id"
                        class="pos-input"
                    >
                        <option value="">All categories</option>
                        <option v-for="category in categories" :key="category.id" :value="String(category.id)">
                            {{ category.name }}
                        </option>
                    </select>
                    <select
                        v-model="filterForm.status"
                        class="pos-input"
                    >
                        <option value="all">All status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <button
                        type="button"
                        @click="applyFilters"
                        class="pos-btn pos-btn-secondary"
                    >
                        Apply Filters
                    </button>
                </div>
            </div>

            <div class="pos-panel mt-4 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="pos-table">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">SKU</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Price</th>
                                <th class="px-4 py-3">Stock</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="products.data.length === 0">
                                <td class="px-4 py-6 text-center text-slate-500" colspan="7">No products found.</td>
                            </tr>
                            <tr v-for="product in products.data" :key="product.id" class="border-t border-slate-100">
                                <td class="px-4 py-3 font-medium text-slate-800">{{ product.name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ product.sku }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ product.category?.name || 'Uncategorized' }}</td>
                                <td class="px-4 py-3 text-slate-800">{{ currency(product.price) }}</td>
                                <td class="px-4 py-3 text-slate-800">
                                    {{ Number(product.stock_quantity).toFixed(2) }} {{ product.unit }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="pos-badge"
                                        :class="
                                            product.is_active
                                                ? 'pos-badge-success'
                                                : 'pos-badge-neutral'
                                        "
                                    >
                                        {{ product.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            :href="route('products.edit', product.id)"
                                            class="pos-btn pos-btn-outline px-3 py-1.5 text-xs"
                                        >
                                            Edit
                                        </Link>
                                        <Link
                                            :href="route('products.destroy', product.id)"
                                            method="delete"
                                            as="button"
                                            class="pos-btn pos-btn-danger px-3 py-1.5 text-xs"
                                        >
                                            Delete
                                        </Link>
                                    </div>
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
    </AuthenticatedLayout>
</template>
