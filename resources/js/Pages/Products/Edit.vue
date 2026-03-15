<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Category {
    id: number;
    name: string;
}

interface Product {
    id: number;
    name: string;
    sku: string;
    barcode?: string | null;
    category_id?: number | null;
    type: string;
    description?: string | null;
    price: string | number;
    cost?: string | number | null;
    tax_rate: string | number;
    track_inventory: boolean;
    stock_quantity: string | number;
    low_stock_threshold: string | number;
    unit: string;
    is_active: boolean;
}

const props = defineProps<{
    categories: Category[];
    product: Product;
}>();

const form = useForm({
    name: props.product.name,
    sku: props.product.sku,
    barcode: props.product.barcode ?? '',
    category_id: props.product.category_id ? String(props.product.category_id) : '',
    type: props.product.type,
    description: props.product.description ?? '',
    price: String(props.product.price),
    cost: props.product.cost ? String(props.product.cost) : '0.00',
    tax_rate: String(props.product.tax_rate),
    unit: props.product.unit,
    track_inventory: props.product.track_inventory,
    low_stock_threshold: String(props.product.low_stock_threshold),
    is_active: props.product.is_active,
});

const submit = (): void => {
    form.put(route('products.update', props.product.id));
};
</script>

<template>
    <Head :title="`Edit ${product.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-slate-800">Edit Product</h2>
                <Link :href="route('products.index')" class="pos-btn pos-btn-ghost text-sm">
                    Back to products
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-4xl px-4 pb-8 sm:px-6 lg:px-8">
            <form @submit.prevent="submit" class="pos-panel p-6">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="sku" value="SKU" />
                        <TextInput id="sku" v-model="form.sku" type="text" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.sku" />
                    </div>

                    <div>
                        <InputLabel for="barcode" value="Barcode" />
                        <TextInput id="barcode" v-model="form.barcode" type="text" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.barcode" />
                    </div>

                    <div>
                        <InputLabel for="category_id" value="Category" />
                        <select
                            id="category_id"
                            v-model="form.category_id"
                            class="pos-input mt-1"
                        >
                            <option value="">Uncategorized</option>
                            <option v-for="category in categories" :key="category.id" :value="String(category.id)">
                                {{ category.name }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.category_id" />
                    </div>

                    <div>
                        <InputLabel for="type" value="Type" />
                        <select
                            id="type"
                            v-model="form.type"
                            class="pos-input mt-1"
                        >
                            <option value="retail">Retail</option>
                            <option value="restaurant">Restaurant</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.type" />
                    </div>

                    <div>
                        <InputLabel for="unit" value="Unit" />
                        <TextInput id="unit" v-model="form.unit" type="text" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.unit" />
                    </div>

                    <div>
                        <InputLabel for="price" value="Price" />
                        <TextInput id="price" v-model="form.price" type="number" step="0.01" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.price" />
                    </div>

                    <div>
                        <InputLabel for="cost" value="Cost" />
                        <TextInput id="cost" v-model="form.cost" type="number" step="0.01" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.cost" />
                    </div>

                    <div>
                        <InputLabel for="tax_rate" value="Tax Rate (%)" />
                        <TextInput id="tax_rate" v-model="form.tax_rate" type="number" step="0.01" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.tax_rate" />
                    </div>

                    <div>
                        <InputLabel for="low_stock_threshold" value="Low Stock Threshold" />
                        <TextInput
                            id="low_stock_threshold"
                            v-model="form.low_stock_threshold"
                            type="number"
                            step="0.001"
                            class="mt-1 block w-full"
                        />
                        <InputError class="mt-2" :message="form.errors.low_stock_threshold" />
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-amber-100 bg-amber-50/70 p-3 text-sm text-amber-800">
                    Current stock: {{ Number(product.stock_quantity).toFixed(2) }} {{ product.unit }}.
                    Use the Inventory page to add or deduct stock.
                </div>

                <div class="mt-4">
                    <InputLabel for="description" value="Description" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="pos-input mt-1"
                    />
                    <InputError class="mt-2" :message="form.errors.description" />
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-6">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input
                            v-model="form.track_inventory"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                        />
                        Track inventory
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                        />
                        Active
                    </label>
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Update Product
                    </PrimaryButton>
                    <Link :href="route('products.index')" class="pos-btn pos-btn-ghost text-sm">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
