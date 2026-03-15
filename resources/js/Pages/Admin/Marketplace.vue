<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CategoryModal from '@/Components/Marketplace/CategoryModal.vue';
import CardModal from '@/Components/Marketplace/CardModal.vue';
import MarketplaceCardTile from '@/Components/Marketplace/MarketplaceCardTile.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type Card = {
    id: number;
    category_id?: number | null;
    parent_id?: number | null;
    store_id?: number | null;
    slug: string;
    title: string;
    description?: string | null;
    image_path?: string | null;
    icon: string;
    color: string;
    route_name: string;
    permission?: string | null;
    role?: string | null;
    sort_order: number;
    is_active: boolean;
};

type Category = {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    is_active: boolean;
    cards_count: number;
    cards: Card[];
};

type Store = {
    id: number;
    name: string;
    store_name?: string | null;
    card_title?: string | null;
    parent_title?: string | null;
    image_path?: string | null;
    products_count?: number;
};

type StoreWithCategory = {
    id: number;
    name: string;
    category_id?: number | null;
    category?: {
        id: number;
        name: string;
    } | null;
};

type Product = {
    id: number;
    store_id: number | null;
    name: string;
    price: string | number;
    image_path?: string | null;
    created_at: string;
    store?: Store | null;
};

type Paginated<T> = {
    data: T[];
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    meta?: {
        total?: number;
    };
};

const props = defineProps<{
    categories: Category[];
    unassignedCards: Card[];
    stores: Store[];
    leafCards: Array<{ id: number; title: string; path: string }>;
    storesByCategory: StoreWithCategory[];
    products: Paginated<Product>;
    productFilters: {
        search?: string;
        store?: number | null;
    };
    roles: string[];
    permissions: string[];
    colors: string[];
}>();

const page = usePage();
const userRoles = computed<string[]>(() => page.props.auth.user?.roles ?? []);
const canManageProducts = computed(() => userRoles.value.includes('admin') || userRoles.value.includes('manager'));

const createProductForm = useForm({
    name: '',
    description: '',
    price: '0.00',
    image: null as File | null,
    card_id: '',
    marketplace_simple: true,
    redirect_to: '',
});

const handleProductImageChange = (event: Event): void => {
    const target = event.target as HTMLInputElement | null;
    createProductForm.image = target?.files?.[0] ?? null;
};

const categories = ref<Category[]>(props.categories.map((category) => ({
    ...category,
    cards: [...category.cards],
})));
const unassignedCards = ref<Card[]>([...props.unassignedCards]);
const localProducts = ref<Product[]>([...props.products.data]);
const productSearch = ref(props.productFilters.search ?? '');
const productStore = ref(props.productFilters.store ? String(props.productFilters.store) : '');

const storeLocations = computed(() =>
    props.leafCards.map((card) => ({
        value: card.id,
        label: card.path,
    })),
);

const allCards = computed<Card[]>(() => [
    ...categories.value.flatMap((category) => category.cards),
    ...unassignedCards.value,
]);

watch(
    () => props.categories,
    (value) => {
        categories.value = value.map((category) => ({
            ...category,
            cards: [...category.cards],
        }));
    },
);

watch(
    () => props.unassignedCards,
    (value) => {
        unassignedCards.value = [...value];
    },
);

watch(
    () => props.products,
    (value) => {
        localProducts.value = [...value.data];
    },
);

watch(
    () => props.productFilters,
    (value) => {
        productSearch.value = value.search ?? '';
        productStore.value = value.store ? String(value.store) : '';
    },
);

const hasCategories = computed(() => categories.value.length > 0);
const hasUnassigned = computed(() => unassignedCards.value.length > 0);

const selectedCategoryId = ref<number | null>(
    props.categories.length > 0 ? props.categories[0].id : null,
);

const ensureSelection = (): void => {
    if (selectedCategoryId.value !== null) {
        const exists = categories.value.some((category) => category.id === selectedCategoryId.value);
        if (exists) {
            return;
        }
    }

    if (categories.value.length > 0) {
        selectedCategoryId.value = categories.value[0].id;
        return;
    }

    selectedCategoryId.value = null;
};

watch([categories, unassignedCards], () => ensureSelection());

const selectedCategory = computed(() =>
    selectedCategoryId.value === null
        ? null
        : categories.value.find((category) => category.id === selectedCategoryId.value) ?? null,
);

const selectedCards = computed(() => {
    if (selectedCategoryId.value === null) {
        return unassignedCards.value;
    }

    return selectedCategory.value?.cards ?? [];
});

const selectedLabel = computed(() =>
    selectedCategoryId.value === null
        ? 'Unassigned Cards'
        : selectedCategory.value?.name ?? 'Cards',
);

const selectedDescription = computed(() =>
    selectedCategoryId.value === null
        ? 'Cards without a category. Assign them to organize the marketplace.'
        : selectedCategory.value?.description ?? 'Cards in the selected category.',
);

const canCreateCard = computed(() => categories.value.length > 0);

const categoryModalOpen = ref(false);
const cardModalOpen = ref(false);
const editingCategory = ref<Category | null>(null);
const editingCard = ref<Card | null>(null);

const openCreateCategory = (): void => {
    editingCategory.value = null;
    categoryModalOpen.value = true;
};

const openEditCategory = (category: Category): void => {
    editingCategory.value = category;
    categoryModalOpen.value = true;
};

const closeCategoryModal = (): void => {
    categoryModalOpen.value = false;
};

const openCreateCard = (): void => {
    editingCard.value = null;
    cardModalOpen.value = true;
};

const openEditCard = (card: Card): void => {
    editingCard.value = card;
    cardModalOpen.value = true;
};

const closeCardModal = (): void => {
    cardModalOpen.value = false;
};

const selectCategory = (categoryId: number | null): void => {
    selectedCategoryId.value = categoryId;
    draggingIndex.value = null;
};

type DeleteTarget =
    | { type: 'category'; item: Category }
    | { type: 'card'; item: Card };

const deleteTarget = ref<DeleteTarget | null>(null);

const confirmDeleteCategory = (category: Category): void => {
    deleteTarget.value = { type: 'category', item: category };
};

const confirmDeleteCard = (card: Card): void => {
    deleteTarget.value = { type: 'card', item: card };
};

const closeDeleteModal = (): void => {
    deleteTarget.value = null;
};

const performDelete = (): void => {
    if (!deleteTarget.value) {
        return;
    }

    if (deleteTarget.value.type === 'category') {
        router.delete(route('admin.marketplace.categories.destroy', deleteTarget.value.item.id), {
            preserveScroll: true,
            onSuccess: () => closeDeleteModal(),
        });
        return;
    }

    router.delete(route('admin.marketplace.cards.destroy', deleteTarget.value.item.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
};

const draggingIndex = ref<number | null>(null);
const isSyncingOrder = ref(false);
const canReorder = computed(() => selectedCategoryId.value !== null);

const updateSelectedCards = (updated: Card[]): void => {
    if (selectedCategoryId.value === null) {
        unassignedCards.value = updated;
        return;
    }

    const index = categories.value.findIndex((category) => category.id === selectedCategoryId.value);
    if (index === -1) {
        return;
    }

    categories.value[index] = {
        ...categories.value[index],
        cards: updated,
    };
};

const moveCard = (fromIndex: number, toIndex: number): void => {
    if (fromIndex === toIndex) {
        return;
    }

    const updated = [...selectedCards.value];
    const [moved] = updated.splice(fromIndex, 1);
    updated.splice(toIndex, 0, moved);

    const reindexed = updated.map((card, index) => ({
        ...card,
        sort_order: index + 1,
    }));

    updateSelectedCards(reindexed);
};

const onDragStart = (event: DragEvent, index: number): void => {
    if (!canReorder.value) {
        return;
    }

    draggingIndex.value = index;
    event.dataTransfer?.setData('text/plain', String(index));
    event.dataTransfer?.setDragImage?.((event.currentTarget as HTMLElement) ?? new Image(), 20, 20);
};

const onDragEnd = (): void => {
    draggingIndex.value = null;
};

const onDrop = (index: number): void => {
    if (!canReorder.value || draggingIndex.value === null) {
        return;
    }

    moveCard(draggingIndex.value, index);
    draggingIndex.value = null;
    syncOrder();
};

const syncOrder = (): void => {
    if (selectedCategoryId.value === null) {
        return;
    }

    isSyncingOrder.value = true;

    router.put(
        route('admin.marketplace.cards.reorder'),
        {
            category_id: selectedCategoryId.value,
            order: selectedCards.value.map((card, index) => ({
                id: card.id,
                sort_order: index + 1,
            })),
        },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                isSyncingOrder.value = false;
            },
        },
    );
};

const submitProduct = (): void => {
    createProductForm.redirect_to = route('admin.marketplace.index');
    createProductForm.post(route('products.store'), {
        preserveScroll: true,
        onSuccess: () => createProductForm.reset('name', 'description', 'price', 'image'),
    });
};

const applyProductFilters = (): void => {
    router.get(
        route('admin.marketplace.index'),
        {
            product_search: productSearch.value || undefined,
            store: productStore.value || undefined,
        },
        { preserveScroll: true, preserveState: true, replace: true },
    );
};

const deleteProduct = (product: Product): void => {
    if (!confirm(`Delete "${product.name}"?`)) {
        return;
    }

    router.delete(route('products.destroy', product.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            localProducts.value = localProducts.value.filter((item) => item.id !== product.id);
        },
    });
};

const formatDate = (value: string): string => {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(date);
};

const formatStoreLabel = (store: Store): string => {
    const baseName = store.store_name ?? store.name ?? '';
    const trimmedName = baseName.trim();
    const isPlaceholder = /^[\?\s]+$/.test(trimmedName);
    if (trimmedName && !isPlaceholder) {
        return trimmedName;
    }

    return `Store #${store.id}`;
};

const formatStoreHierarchyLabel = (store: Store): string => {
    const base = formatStoreLabel(store);

    const parent = store.parent_title ?? '';
    const card = store.card_title ?? '';

    if (parent || card) {
        const sep = parent && card ? ' → ' : '';
        return `${base}\n— ${parent}${sep}${card}`.trim();
    }

    return `${base} — Store #${store.id}`;
};
</script>

<template>
    <Head title="Marketplace Control Panel" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="inline-flex flex-col rounded-2xl border border-white/50 bg-white/70 px-4 py-3 shadow-sm backdrop-blur">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-700 drop-shadow">
                        Admin
                    </p>
                    <h2 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900 drop-shadow">
                        Marketplace Control Panel
                    </h2>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                        @click="openCreateCategory"
                    >
                        Create Category
                    </button>
                </div>
            </div>
        </template>

        <div class="grid gap-6 lg:grid-cols-[280px_1fr]">
            <aside class="rounded-3xl border border-white/40 bg-white/60 backdrop-blur-lg shadow-xl">
                <div class="flex items-center justify-between border-b border-white/40 px-5 py-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Categories</h3>
                        <p class="text-xs text-slate-500">Manage marketplace sections</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                        @click="openCreateCategory"
                    >
                        Add
                    </button>
                </div>

                <div class="space-y-2 p-4">
                    <button
                        v-if="hasUnassigned"
                        type="button"
                        class="group flex w-full items-center justify-between rounded-2xl border px-4 py-3 text-left text-sm font-semibold transition"
                        :class="
                            selectedCategoryId === null
                                ? 'border-emerald-200 bg-emerald-50/80 text-emerald-800'
                                : 'border-white/60 bg-white/70 text-slate-700 hover:border-emerald-100'
                        "
                        @click="selectCategory(null)"
                    >
                        <span>
                            Unassigned
                            <span class="block text-xs font-normal text-slate-500">Needs category</span>
                        </span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                            {{ unassignedCards.length }}
                        </span>
                    </button>

                    <div
                        v-for="category in categories"
                        :key="category.id"
                        class="group flex items-center justify-between rounded-2xl border border-white/60 bg-white/70 px-4 py-3 text-sm transition hover:border-emerald-100"
                        :class="selectedCategoryId === category.id ? 'border-emerald-200 bg-emerald-50/80' : ''"
                    >
                        <button type="button" class="flex-1 text-left" @click="selectCategory(category.id)">
                            <p class="font-semibold text-slate-900">{{ category.name }}</p>
                            <p class="text-xs text-slate-500">
                                {{ category.is_active ? 'Active' : 'Inactive' }} · {{ category.cards_count }} cards
                            </p>
                        </button>
                        <div class="ml-3 flex items-center gap-2 opacity-0 transition group-hover:opacity-100">
                            <button
                                type="button"
                                class="text-xs font-semibold text-emerald-700 hover:text-emerald-800"
                                @click.stop="openEditCategory(category)"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                class="text-xs font-semibold text-rose-600 hover:text-rose-700"
                                @click.stop="confirmDeleteCategory(category)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="!hasCategories && !hasUnassigned"
                        class="rounded-2xl border border-dashed border-slate-200 bg-white/70 p-6 text-center text-sm text-slate-500"
                    >
                        Create your first category to start adding cards.
                    </div>
                </div>
            </aside>

            <section class="space-y-6">
                <div class="rounded-3xl border border-white/40 bg-white/60 p-6 shadow-xl">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Cards</p>
                            <h3 class="mt-1 text-xl font-semibold text-slate-900">{{ selectedLabel }}</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ selectedDescription }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                                {{ isSyncingOrder ? 'Saving order' : `${selectedCards.length} cards` }}
                            </span>
                            <button
                                type="button"
                                class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                                :disabled="!canCreateCard"
                                @click="openCreateCard"
                            >
                                New Card
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="selectedCards.length === 0"
                    class="rounded-3xl border border-dashed border-slate-200 bg-white/70 p-10 text-center text-sm text-slate-500"
                >
                    {{ canCreateCard ? 'No cards in this category yet.' : 'Create a category to add cards.' }}
                </div>

                <TransitionGroup
                    v-else
                    tag="div"
                    class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
                    move-class="transition-transform duration-300"
                >
                    <MarketplaceCardTile
                        v-for="(card, index) in selectedCards"
                        :key="card.id"
                        :card="card"
                        :draggable="canReorder"
                        :dragging="draggingIndex === index"
                        @dragstart="onDragStart($event, index)"
                        @dragend="onDragEnd"
                        @drop="onDrop(index)"
                        @edit="openEditCard(card)"
                        @delete="confirmDeleteCard(card)"
                    />
                </TransitionGroup>

                <p v-if="!canReorder && selectedCards.length" class="text-xs text-slate-500">
                    Assign cards to a category to enable drag-and-drop ordering.
                </p>

                <div v-if="canManageProducts" class="rounded-3xl border border-white/40 bg-white/60 p-6 shadow-xl">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Products</p>
                        <h3 class="mt-1 text-xl font-semibold text-slate-900">Create Product</h3>
                        <p class="mt-1 text-sm text-slate-500">Add a new product and link it to a location.</p>
                    </div>

                    <form @submit.prevent="submitProduct" class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <InputLabel for="marketplace-location" value="Location" />
                            <select
                                id="marketplace-location"
                                v-model="createProductForm.card_id"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                required
                            >
                                <option value="">Select location</option>
                                <option v-for="location in storeLocations" :key="location.value" :value="String(location.value)">
                                    {{ location.label }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="createProductForm.errors.card_id" />
                        </div>

                        <div class="md:col-span-2">
                            <InputLabel for="marketplace-product-name" value="Name" />
                            <input
                                id="marketplace-product-name"
                                v-model="createProductForm.name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                required
                            />
                            <InputError class="mt-2" :message="createProductForm.errors.name" />
                        </div>

                        <div class="md:col-span-2">
                            <InputLabel for="marketplace-product-description" value="Description" />
                            <textarea
                                id="marketplace-product-description"
                                v-model="createProductForm.description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            />
                            <InputError class="mt-2" :message="createProductForm.errors.description" />
                        </div>

                        <div>
                            <InputLabel for="marketplace-product-price" value="Price" />
                            <input
                                id="marketplace-product-price"
                                v-model="createProductForm.price"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                required
                            />
                            <InputError class="mt-2" :message="createProductForm.errors.price" />
                        </div>

                        <div>
                            <InputLabel for="marketplace-product-image" value="Image (optional)" />
                            <input
                                id="marketplace-product-image"
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                                class="mt-1 block w-full rounded-md border border-slate-300 bg-white text-sm text-slate-700 shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100"
                                @change="handleProductImageChange"
                            />
                            <InputError class="mt-2" :message="createProductForm.errors.image" />
                        </div>

                        <div class="md:col-span-2 flex items-center justify-end">
                            <PrimaryButton
                                :disabled="createProductForm.processing"
                                :class="{ 'opacity-25': createProductForm.processing }"
                            >
                                Create Product
                            </PrimaryButton>
                        </div>
                    </form>

                </div>

            </section>
        </div>

        <CategoryModal
            :show="categoryModalOpen"
            :category="editingCategory"
            @close="closeCategoryModal"
        />

        <CardModal
            :show="cardModalOpen"
            :card="editingCard"
            :categories="categories"
            :cards="allCards"
            :stores="storesByCategory"
            :roles="roles"
            :permissions="permissions"
            :colors="colors"
            :default-category-id="selectedCategoryId"
            @close="closeCardModal"
        />

        <Modal :show="deleteTarget !== null" maxWidth="md" @close="closeDeleteModal">
            <div class="space-y-4 p-6">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Confirm deletion</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        <span v-if="deleteTarget?.type === 'category'">
                            Delete "{{ deleteTarget?.item.name }}"? Cards inside will become unassigned.
                        </span>
                        <span v-else-if="deleteTarget?.type === 'card'">
                            Delete "{{ deleteTarget?.item.title }}"? This cannot be undone.
                        </span>
                    </p>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <SecondaryButton type="button" @click="closeDeleteModal">Cancel</SecondaryButton>
                    <DangerButton @click="performDelete">Delete</DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
