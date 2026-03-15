<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MarketplaceCard from '@/Components/MarketplaceCard.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Store = {
    id: number;
    name: string;
    category?: {
        id: number;
        name: string;
    } | null;
};

type Product = {
    id: number;
    store_id: number | null;
    sku: string;
    name: string;
    description?: string | null;
    price: string | number;
    is_active: boolean;
    store?: Store | null;
};

const props = defineProps<{
    products: Product[];
    stores: Store[];
    filters: {
        store?: number | null;
    };
}>();

const selectedStore = ref(props.filters.store ? String(props.filters.store) : '');

const showModal = ref(false);
const editingProduct = ref<Product | null>(null);

const form = useForm({
    store_id: '',
    name: '',
    sku: '',
    price: '0.00',
    description: '',
    is_active: true,
});

const modalTitle = computed(() => (editingProduct.value ? 'Edit Product' : 'Create Product'));

const openCreate = (): void => {
    editingProduct.value = null;
    form.reset();
    form.clearErrors();
    form.store_id = selectedStore.value || '';
    form.price = '0.00';
    form.is_active = true;
    showModal.value = true;
};

const openEdit = (product: Product): void => {
    editingProduct.value = product;
    form.clearErrors();
    form.store_id = product.store_id ? String(product.store_id) : '';
    form.name = product.name;
    form.sku = product.sku;
    form.price = String(product.price ?? '0.00');
    form.description = product.description ?? '';
    form.is_active = product.is_active;
    showModal.value = true;
};

const closeModal = (): void => {
    showModal.value = false;
};

const submit = (): void => {
    if (editingProduct.value) {
        form.put(route('admin.products.update', editingProduct.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
        return;
    }

    form.post(route('admin.products.store'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const deleteProduct = (product: Product): void => {
    if (!confirm(`Delete "${product.name}"?`)) {
        return;
    }

    router.delete(route('admin.products.destroy', product.id), { preserveScroll: true });
};

const applyFilter = (): void => {
    router.get(
        route('admin.products.index'),
        selectedStore.value ? { store: selectedStore.value } : {},
        { preserveScroll: true, preserveState: true, replace: true },
    );
};
</script>

<template>
    <Head title="Marketplace Products" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Marketplace</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900">Products</h2>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <Link
                        :href="route('admin.stores.index')"
                        class="rounded-xl border border-slate-200 bg-white/80 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700"
                    >
                        Back to Stores
                    </Link>
                    <PrimaryButton @click="openCreate">
                        New Product
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex flex-wrap items-center gap-3">
                <label class="text-sm font-semibold text-slate-700">Filter by store</label>
                <select
                    v-model="selectedStore"
                    class="rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    @change="applyFilter"
                >
                    <option value="">All Stores</option>
                    <option v-for="store in props.stores" :key="store.id" :value="String(store.id)">
                        {{ store.name }}{{ store.category ? ` (${store.category.name})` : '' }}
                    </option>
                </select>
            </div>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-if="props.products.length === 0"
                    class="rounded-2xl border border-dashed border-slate-200 bg-white/70 p-8 text-center text-sm text-slate-500"
                >
                    No products yet. Create a product for a store.
                </div>

                <div v-for="product in props.products" :key="product.id" class="flex h-full flex-col">
                    <MarketplaceCard
                        :title="product.name"
                        :description="`SKU: ${product.sku}`"
                        :meta="product.store?.name || 'Unassigned'"
                        :href="null"
                    >
                        <template #footer>
                            <div class="flex items-center justify-between">
                                <span>${{ Number(product.price).toFixed(2) }}</span>
                                <span class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-emerald-700 hover:text-emerald-800"
                                        @click.stop.prevent="openEdit(product)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-rose-600 hover:text-rose-700"
                                        @click.stop.prevent="deleteProduct(product)"
                                    >
                                        Delete
                                    </button>
                                </span>
                            </div>
                        </template>
                    </MarketplaceCard>
                </div>
            </section>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <form @submit.prevent="submit" class="space-y-6 p-6">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">{{ modalTitle }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Products belong to a single store.</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <InputLabel for="store_id" value="Store" />
                        <select
                            id="store_id"
                            v-model="form.store_id"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            required
                        >
                            <option value="">Select store</option>
                            <option v-for="store in props.stores" :key="store.id" :value="String(store.id)">
                                {{ store.name }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.store_id" />
                    </div>

                    <div>
                        <InputLabel for="name" value="Product Name" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="sku" value="SKU" />
                        <TextInput id="sku" v-model="form.sku" type="text" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.sku" />
                    </div>

                    <div>
                        <InputLabel for="price" value="Price" />
                        <TextInput id="price" v-model="form.price" type="number" step="0.01" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.price" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        />
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-emerald-600" />
                        Active
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <SecondaryButton type="button" @click="closeModal">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }">
                        Save
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
