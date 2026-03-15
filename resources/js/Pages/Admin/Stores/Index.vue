<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StorePromoCard from '@/Components/StorePromoCard.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Category = {
    id: number;
    name: string;
};

type Store = {
    id: number;
    name: string;
    description?: string | null;
    image?: string | null;
    image_path?: string | null;
    category: Category | null;
    category_id?: number | null;
    products_count: number;
};

const props = defineProps<{
    stores: Store[];
    categories: Category[];
    filters: {
        category?: number | null;
    };
}>();

const selectedCategory = ref(props.filters.category ? String(props.filters.category) : '');

const showModal = ref(false);
const editingStore = ref<Store | null>(null);

const form = useForm({
    category_id: '',
    name: '',
    description: '',
    image: null as File | null,
});

const selectedImageName = computed(() => form.image?.name ?? '');

const handleImageChange = (event: Event): void => {
    const target = event.target as HTMLInputElement | null;
    form.image = target?.files?.[0] ?? null;
};

const modalTitle = computed(() => (editingStore.value ? 'Edit Store' : 'Create Store'));

const openCreate = (): void => {
    editingStore.value = null;
    form.reset();
    form.clearErrors();
    form.category_id = selectedCategory.value || '';
    showModal.value = true;
};

const openEdit = (store: Store): void => {
    editingStore.value = store;
    form.clearErrors();
    form.category_id = store.category_id ? String(store.category_id) : '';
    form.name = store.name;
    form.description = store.description ?? '';
    form.image = null;
    showModal.value = true;
};

const closeModal = (): void => {
    showModal.value = false;
};

const submit = (): void => {
    if (editingStore.value) {
        form.put(route('admin.stores.update', editingStore.value.id), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => closeModal(),
        });
        return;
    }

    form.post(route('admin.stores.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => closeModal(),
    });
};

const deleteStore = (store: Store): void => {
    if (!confirm(`Delete "${store.name}"? This will also remove its products.`)) {
        return;
    }

    router.delete(route('admin.stores.destroy', store.id), { preserveScroll: true });
};

const applyFilter = (): void => {
    router.get(
        route('admin.stores.index'),
        selectedCategory.value ? { category: selectedCategory.value } : {},
        { preserveScroll: true, preserveState: true, replace: true },
    );
};
</script>

<template>
    <Head title="Marketplace Stores" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Marketplace</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900">Stores</h2>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <Link
                        :href="route('admin.categories.index')"
                        class="rounded-xl border border-slate-200 bg-white/80 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700"
                    >
                        Back to Categories
                    </Link>
                    <PrimaryButton @click="openCreate">
                        New Store
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex flex-wrap items-center gap-3">
                <label class="text-sm font-semibold text-slate-700">Filter by category</label>
                <select
                    v-model="selectedCategory"
                    class="rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    @change="applyFilter"
                >
                    <option value="">All Categories</option>
                    <option v-for="category in props.categories" :key="category.id" :value="String(category.id)">
                        {{ category.name }}
                    </option>
                </select>
            </div>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-if="props.stores.length === 0"
                    class="rounded-2xl border border-dashed border-slate-200 bg-white/70 p-8 text-center text-sm text-slate-500"
                >
                    No stores yet. Create a store inside a category.
                </div>

                <div v-for="store in props.stores" :key="store.id" class="flex h-full flex-col">
                    <StorePromoCard
                        :title="store.name"
                        :description="store.description || 'No description yet.'"
                        :meta="store.category?.name || 'Unassigned'"
                        :href="route('admin.products.index', { store: store.id })"
                        :image-path="store.image_path"
                    >
                        <template #footer>
                            <div class="flex items-center justify-between">
                                <span>{{ store.products_count }} products</span>
                                <span class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-emerald-700 hover:text-emerald-800"
                                        @click.stop.prevent="openEdit(store)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-rose-600 hover:text-rose-700"
                                        @click.stop.prevent="deleteStore(store)"
                                    >
                                        Delete
                                    </button>
                                </span>
                            </div>
                        </template>
                    </StorePromoCard>
                </div>
            </section>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <form @submit.prevent="submit" class="space-y-6 p-6">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">{{ modalTitle }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Stores are grouped under a marketplace category.</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <InputLabel for="category_id" value="Category" />
                        <select
                            id="category_id"
                            v-model="form.category_id"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            required
                        >
                            <option value="">Select category</option>
                            <option v-for="category in props.categories" :key="category.id" :value="String(category.id)">
                                {{ category.name }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.category_id" />
                    </div>

                    <div>
                        <InputLabel for="name" value="Store Name" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.name" />
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

                    <div>
                        <InputLabel for="image" value="Store Image (optional)" />
                        <input
                            id="image"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            class="mt-1 block w-full rounded-md border border-slate-300 bg-white text-sm text-slate-700 shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100"
                            @change="handleImageChange"
                        />
                        <p class="mt-2 text-xs text-slate-500">
                            Upload a PNG, JPG, or WEBP for this store.
                        </p>
                        <p v-if="selectedImageName" class="mt-2 text-xs font-semibold text-slate-600">
                            Selected: {{ selectedImageName }}
                        </p>
                        <InputError class="mt-2" :message="form.errors.image" />
                    </div>
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
