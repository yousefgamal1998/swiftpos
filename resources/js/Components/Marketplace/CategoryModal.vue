<script setup lang="ts">
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

type Category = {
    id: number;
    name: string;
    description?: string | null;
    is_active: boolean;
};

const props = defineProps<{
    show: boolean;
    category: Category | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const form = useForm({
    name: '',
    description: '',
    is_active: true,
});

const modalTitle = computed(() => (props.category ? 'Edit Category' : 'Create Category'));

const fillForm = (category: Category | null): void => {
    form.reset();
    form.clearErrors();
    form.name = category?.name ?? '';
    form.description = category?.description ?? '';
    form.is_active = category?.is_active ?? true;
};

watch(
    () => [props.show, props.category] as const,
    ([show, category]) => {
        if (show) {
            fillForm(category);
        }
    },
);

const close = (): void => {
    emit('close');
};

const submit = (): void => {
    if (props.category) {
        form.put(route('admin.marketplace.categories.update', props.category.id), {
            preserveScroll: true,
            onSuccess: () => close(),
        });
        return;
    }

    form.post(route('admin.marketplace.categories.store'), {
        preserveScroll: true,
        onSuccess: () => close(),
    });
};
</script>

<template>
    <Modal :show="show" maxWidth="xl" @close="close">
        <form @submit.prevent="submit" class="space-y-6 p-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Marketplace</p>
                <h3 class="mt-1 text-lg font-semibold text-slate-900">{{ modalTitle }}</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Categories organize marketplace cards across your admin dashboard.
                </p>
            </div>

            <div class="space-y-4">
                <div>
                    <InputLabel for="category-name" value="Name" />
                    <TextInput id="category-name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="category-description" value="Description" />
                    <textarea
                        id="category-description"
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
                <SecondaryButton type="button" @click="close">Cancel</SecondaryButton>
                <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }">
                    Save
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
