<script setup lang="ts">
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

type CategoryOption = {
    id: number;
    name: string;
    is_active: boolean;
};

type StoreOption = {
    id: number;
    name: string;
    category_id?: number | null;
};

type Card = {
    id: number;
    category_id?: number | null;
    parent_id?: number | null;
    store_id?: number | null;
    title: string;
    description?: string | null;
    image_path?: string | null;
    color: string;
    route_name: string;
    permission?: string | null;
    role?: string | null;
    sort_order: number;
    is_active: boolean;
};

const props = withDefaults(
    defineProps<{
        show: boolean;
        card: Card | null;
        categories: CategoryOption[];
        cards: Card[];
        stores: StoreOption[];
        roles: string[];
        permissions: string[];
        colors: string[];
        defaultCategoryId?: number | null;
    }>(),
    {
        show: false,
        card: null,
        defaultCategoryId: null,
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const form = useForm({
    category_id: '',
    parent_id: '',
    store_id: '',
    title: '',
    description: '',
    image: null as File | null,
    color: props.colors[0] ?? 'slate',
    permission: '',
    role: '',
    is_active: true,
    remove_image: false,
});

const imageName = computed(() => form.image?.name ?? '');
const imagePreview = ref<string | null>(null);
const currentImagePath = ref<string | null>(null);
const selectedCategoryId = computed(() => (form.category_id ? Number(form.category_id) : null));

const handleImageChange = (event: Event): void => {
    const target = event.target as HTMLInputElement | null;
    form.image = target?.files?.[0] ?? null;
};

const parentOptions = computed(() => {
    if (!selectedCategoryId.value) {
        return [];
    }

    return props.cards.filter(
        (cardOption) =>
            cardOption.category_id === selectedCategoryId.value && cardOption.id !== props.card?.id,
    );
});

const storeOptions = computed(() => {
    if (!selectedCategoryId.value) {
        return props.stores;
    }

    return props.stores.filter((store) => store.category_id === selectedCategoryId.value);
});

watch(parentOptions, (options) => {
    if (!form.parent_id) {
        return;
    }

    const exists = options.some((option) => String(option.id) === String(form.parent_id));
    if (!exists) {
        form.parent_id = '';
    }
});

watch(storeOptions, (options) => {
    if (!form.store_id) {
        return;
    }

    const exists = options.some((option) => String(option.id) === String(form.store_id));
    if (!exists) {
        form.store_id = '';
    }
});
let previewUrl: string | null = null;

const updatePreview = (file: File | null): void => {
    if (previewUrl) {
        URL.revokeObjectURL(previewUrl);
    }

    previewUrl = file ? URL.createObjectURL(file) : null;
    imagePreview.value = previewUrl;
};

watch(
    () => form.image,
    (file) => updatePreview(file),
);

onBeforeUnmount(() => {
    if (previewUrl) {
        URL.revokeObjectURL(previewUrl);
    }
});

const fillForm = (card: Card | null): void => {
    form.reset();
    form.clearErrors();

    form.category_id = card?.category_id
        ? String(card.category_id)
        : props.defaultCategoryId
            ? String(props.defaultCategoryId)
            : '';
    form.parent_id = card?.parent_id ? String(card.parent_id) : '';
    form.store_id = card?.store_id ? String(card.store_id) : '';
    form.title = card?.title ?? '';
    form.description = card?.description ?? '';
    form.color = card?.color ?? (props.colors[0] ?? 'slate');
    form.permission = card?.permission ?? '';
    form.role = card?.role ?? '';
    form.is_active = card?.is_active ?? true;
    form.image = null;
    form.remove_image = false;

    currentImagePath.value = card?.image_path ?? null;
    updatePreview(null);
};

watch(
    () => [props.show, props.card, props.defaultCategoryId] as const,
    ([show, card]) => {
        if (show) {
            fillForm(card);
        }
    },
);

const close = (): void => {
    emit('close');
};

const clearSelectedImage = (): void => {
    form.image = null;
    updatePreview(null);
};

const removeStoredImage = (): void => {
    if (!currentImagePath.value) {
        return;
    }

    form.remove_image = true;
    currentImagePath.value = null;
};

const submit = (): void => {
    const transform = (data: ReturnType<typeof form.data>) => ({
        ...data,
        category_id: data.category_id ? Number(data.category_id) : null,
        parent_id: data.parent_id ? Number(data.parent_id) : null,
        store_id: data.store_id ? Number(data.store_id) : null,
        permission: data.permission || null,
        role: data.role || null,
        remove_image: data.remove_image || false,
    });

    if (props.card) {
        form.transform(transform).put(route('admin.marketplace.cards.update', props.card.id), {
            preserveScroll: true,
            onSuccess: () => close(),
        });
        return;
    }

    form.transform(transform).post(route('admin.marketplace.cards.store'), {
        preserveScroll: true,
        onSuccess: () => close(),
    });
};
</script>

<template>
    <Modal :show="show" maxWidth="2xl" @close="close">
        <form @submit.prevent="submit" class="space-y-6 p-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Marketplace</p>
                <h3 class="mt-1 text-lg font-semibold text-slate-900">
                    {{ card ? 'Edit Card' : 'Create Card' }}
                </h3>
                <p class="mt-1 text-sm text-slate-500">
                    Cards surface key actions on the admin dashboard. Assign them to the right category.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <InputLabel for="card-category" value="Category" />
                    <select
                        id="card-category"
                        v-model="form.category_id"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        required
                    >
                        <option value="" disabled>Select a category</option>
                        <option v-for="category in categories" :key="category.id" :value="String(category.id)">
                            {{ category.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.category_id" />
                </div>

                <div class="md:col-span-2">
                    <InputLabel for="card-parent" value="Parent Card (optional)" />
                    <select
                        id="card-parent"
                        v-model="form.parent_id"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                        <option value="">No parent</option>
                        <option v-for="parent in parentOptions" :key="parent.id" :value="String(parent.id)">
                            {{ parent.title }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.parent_id" />
                </div>

                <div class="md:col-span-2">
                    <InputLabel for="card-store" value="Store (optional)" />
                    <select
                        id="card-store"
                        v-model="form.store_id"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                        <option value="">No store</option>
                        <option v-for="store in storeOptions" :key="store.id" :value="String(store.id)">
                            {{ store.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.store_id" />
                </div>

                <div>
                    <InputLabel for="card-title" value="Title" />
                    <TextInput id="card-title" v-model="form.title" type="text" class="mt-1 block w-full" required />
                    <InputError class="mt-2" :message="form.errors.title" />
                </div>

                <div>
                    <InputLabel for="card-color" value="Accent Color" />
                    <select
                        id="card-color"
                        v-model="form.color"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                        <option v-for="color in colors" :key="color" :value="color">
                            {{ color }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.color" />
                </div>

                <div>
                    <InputLabel for="card-permission" value="Permission (optional)" />
                    <select
                        id="card-permission"
                        v-model="form.permission"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                        <option value="">None</option>
                        <option v-for="permission in permissions" :key="permission" :value="permission">
                            {{ permission }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.permission" />
                </div>

                <div>
                    <InputLabel for="card-role" value="Role (optional)" />
                    <select
                        id="card-role"
                        v-model="form.role"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                        <option value="">None</option>
                        <option v-for="role in roles" :key="role" :value="role">
                            {{ role }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.role" />
                </div>
            </div>

            <div>
                <InputLabel for="card-description" value="Description" />
                <textarea
                    id="card-description"
                    v-model="form.description"
                    rows="3"
                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                />
                <InputError class="mt-2" :message="form.errors.description" />
            </div>

            <div class="space-y-3">
                <InputLabel for="card-image" value="Card Image (optional)" />
                <input
                    id="card-image"
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    class="mt-1 block w-full rounded-md border border-slate-300 bg-white text-sm text-slate-700 shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100"
                    @change="handleImageChange"
                />
                <p class="text-xs text-slate-500">
                    Upload a JPG, PNG, or WebP image under 2MB. SVG files are not allowed.
                </p>
                <p v-if="imageName" class="text-xs font-semibold text-slate-600">
                    Selected: {{ imageName }}
                </p>
                <InputError class="mt-2" :message="form.errors.image" />

                <div class="flex flex-wrap items-center gap-3">
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Preview</p>
                        <img
                            v-if="imagePreview || currentImagePath"
                            :src="imagePreview ?? `/storage/${currentImagePath}`"
                            alt=""
                            class="mt-2 h-16 w-16 object-contain"
                        />
                        <img
                            v-else
                            src="/images/card-placeholder.svg"
                            alt=""
                            class="mt-2 h-16 w-16 object-contain opacity-70"
                        />
                    </div>
                    <button
                        v-if="form.image"
                        type="button"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-rose-200 hover:text-rose-700"
                        @click="clearSelectedImage"
                    >
                        Remove Selected Image
                    </button>
                    <button
                        v-if="currentImagePath"
                        type="button"
                        class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100"
                        @click="removeStoredImage"
                    >
                        Remove Stored Image
                    </button>
                </div>
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-emerald-600" />
                Active
            </label>

            <div class="flex items-center justify-end gap-3">
                <SecondaryButton type="button" @click="close">Cancel</SecondaryButton>
                <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }">
                    Save
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
