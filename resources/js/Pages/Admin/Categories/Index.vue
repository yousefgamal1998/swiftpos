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

type Category = {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    is_active: boolean;
    stores_count: number;
};

const props = defineProps<{
    categories: Category[];
}>();

const showModal = ref(false);
const editingCategory = ref<Category | null>(null);

const form = useForm({
    name: '',
    slug: '',
    description: '',
    is_active: true,
});

const modalTitle = computed(() => (editingCategory.value ? 'Edit Category' : 'Create Category'));

const openCreate = (): void => {
    editingCategory.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    showModal.value = true;
};

const openEdit = (category: Category): void => {
    editingCategory.value = category;
    form.clearErrors();
    form.name = category.name;
    form.slug = category.slug;
    form.description = category.description ?? '';
    form.is_active = category.is_active;
    showModal.value = true;
};

const closeModal = (): void => {
    showModal.value = false;
};

const submit = (): void => {
    if (editingCategory.value) {
        form.put(route('admin.categories.update', editingCategory.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
        return;
    }

    form.post(route('admin.categories.store'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const deleteCategory = (category: Category): void => {
    if (!confirm(`Delete "${category.name}"? This will also detach its stores.`)) {
        return;
    }

    router.delete(route('admin.categories.destroy', category.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Marketplace Categories" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Marketplace</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900">Business Categories</h2>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('dashboard')"
                        class="rounded-xl border border-slate-200 bg-white/80 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700"
                    >
                        Back to Dashboard
                    </Link>
                    <PrimaryButton @click="openCreate">
                        New Category
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-if="props.categories.length === 0"
                    class="rounded-2xl border border-dashed border-slate-200 bg-white/70 p-8 text-center text-sm text-slate-500"
                >
                    No categories yet. Create your first business category.
                </div>

                <div v-for="category in props.categories" :key="category.id" class="flex h-full flex-col">
                    <MarketplaceCard
                        :title="category.name"
                        :description="category.description || 'No description yet.'"
                        :meta="category.is_active ? 'Active' : 'Inactive'"
                        :href="route('admin.stores.index', { category: category.id })"
                    >
                        <template #footer>
                            <div class="flex items-center justify-between">
                                <span>{{ category.stores_count }} stores</span>
                                <span class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-emerald-700 hover:text-emerald-800"
                                        @click.stop.prevent="openEdit(category)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-rose-600 hover:text-rose-700"
                                        @click.stop.prevent="deleteCategory(category)"
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
                    <p class="mt-1 text-sm text-slate-500">
                        Categories appear as dashboard cards and group stores together.
                    </p>
                </div>

                <div class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="slug" value="Slug (optional)" />
                        <TextInput id="slug" v-model="form.slug" type="text" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.slug" />
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
