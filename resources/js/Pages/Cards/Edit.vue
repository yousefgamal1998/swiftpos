<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

type Card = {
    id: number;
    title: string;
    description?: string | null;
    icon: string;
    image_path?: string | null;
    color: string;
    route_name: string;
    permission?: string | null;
    role?: string | null;
    sort_order: number;
    is_active: boolean;
};

const props = defineProps<{
    card: Card;
    roles: string[];
    permissions: string[];
    colors: string[];
}>();

const form = useForm({
    title: props.card.title ?? '',
    description: props.card.description ?? '',
    image: null as File | null,
    color: props.card.color ?? (props.colors[0] ?? 'slate'),
    route_name: props.card.route_name ?? '',
    permission: props.card.permission ?? '',
    role: props.card.role ?? '',
    sort_order: String(props.card.sort_order ?? ''),
    is_active: props.card.is_active ?? true,
});

const imageName = computed(() => form.image?.name ?? '');
const imagePreview = ref<string | null>(null);
const currentImagePath = ref<string | null>(props.card.image_path ?? null);
let previewUrl: string | null = null;

const handleImageChange = (event: Event): void => {
    const target = event.target as HTMLInputElement | null;
    form.image = target?.files?.[0] ?? null;
};

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

const clearSelectedImage = (): void => {
    form.image = null;
    updatePreview(null);
};

const removeStoredImage = (): void => {
    if (!currentImagePath.value) {
        return;
    }

    if (!confirm('Remove the current card image?')) {
        return;
    }

    router.patch(
        route('admin.cards.remove-image', props.card.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                currentImagePath.value = null;
            },
        },
    );
};

const submit = (): void => {
    form.transform((data) => ({
        ...data,
        permission: data.permission || null,
        role: data.role || null,
        sort_order: data.sort_order ? Number(data.sort_order) : null,
    })).put(route('admin.cards.update', props.card.id));
};
</script>

<template>
    <Head title="Edit Card" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-slate-800">Edit Dashboard Card</h2>
                <Link :href="route('admin.cards.index')" class="text-sm font-semibold text-slate-700 hover:text-slate-900">
                    Back to cards
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-4xl px-4 pb-8 sm:px-6 lg:px-8">
            <form @submit.prevent="submit" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel for="title" value="Title" />
                        <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.title" />
                    </div>

                    <div>
                        <InputLabel for="route_name" value="Route Name" />
                        <TextInput id="route_name" v-model="form.route_name" type="text" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.route_name" />
                    </div>

                    <div>
                        <InputLabel for="color" value="Color" />
                        <select
                            id="color"
                            v-model="form.color"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            <option v-for="color in props.colors" :key="color" :value="color">
                                {{ color }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.color" />
                    </div>

                    <div>
                        <InputLabel for="sort_order" value="Sort Order (optional)" />
                        <TextInput id="sort_order" v-model="form.sort_order" type="number" min="1" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.sort_order" />
                    </div>

                    <div>
                        <InputLabel for="permission" value="Permission (optional)" />
                        <select
                            id="permission"
                            v-model="form.permission"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            <option value="">None</option>
                            <option v-for="permission in props.permissions" :key="permission" :value="permission">
                                {{ permission }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.permission" />
                    </div>

                    <div>
                        <InputLabel for="role" value="Role (optional)" />
                        <select
                            id="role"
                            v-model="form.role"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            <option value="">None</option>
                            <option v-for="role in props.roles" :key="role" :value="role">
                                {{ role }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.role" />
                    </div>
                </div>

                <div class="mt-4">
                    <InputLabel for="description" value="Description" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    />
                    <InputError class="mt-2" :message="form.errors.description" />
                </div>

                <div class="mt-4">
                    <InputLabel for="image" value="Card Image (optional)" />
                    <input
                        id="image"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        class="mt-1 block w-full rounded-md border border-slate-300 bg-white text-sm text-slate-700 shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100"
                        @change="handleImageChange"
                    />
                    <p class="mt-2 text-xs text-slate-500">
                        Upload a PNG, JPG, or WebP to replace the current image on the dashboard.
                    </p>
                    <p v-if="imageName" class="mt-2 text-xs font-semibold text-slate-600">
                        Selected: {{ imageName }}
                    </p>
                    <InputError class="mt-2" :message="form.errors.image" />
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Preview</p>
                            <img
                                v-if="imagePreview || currentImagePath"
                                :src="imagePreview ?? `/storage/${currentImagePath}`"
                                alt=""
                                class="mt-2 h-14 w-14 object-contain"
                            />
                            <svg
                                v-else-if="props.card.icon"
                                class="mt-2 h-12 w-12 text-slate-500"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="props.card.icon" />
                            </svg>
                            <img
                                v-else
                                src="/images/card-placeholder.svg"
                                alt=""
                                class="mt-2 h-14 w-14 object-contain opacity-70"
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
                            Remove Image
                        </button>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-6">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-emerald-600" />
                        Active
                    </label>
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Save Changes
                    </PrimaryButton>
                    <Link :href="route('admin.cards.index')" class="text-sm text-slate-600 hover:text-slate-900">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
