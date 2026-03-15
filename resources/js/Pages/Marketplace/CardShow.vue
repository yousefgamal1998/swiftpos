<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCartStore } from '@/stores/cart';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

type Card = {
    id: number;
    parent_id?: number | null;
    store_id?: number | null;
    slug: string;
    title: string;
    description?: string | null;
    image_path?: string | null;
    color: string;
    route_name: string;
    permission?: string | null;
    role?: string | null;
};

type Product = {
    id: number;
    name: string;
    description?: string | null;
    price: string | number;
    image_path?: string | null;
};

const props = defineProps<{
    card: Card;
    products: Product[];
}>();

const hasProducts = computed(() => props.products.length > 0);

const { cartOpen, getQuantity, addToCart, decrement, checkout, toggleCart, isAuthenticated } = useCartStore();

const handleBuyNow = async (product: Product): Promise<void> => {
    if (getQuantity(product.id) === 0) {
        await addToCart(product);
    }

    if (isAuthenticated.value) {
        await checkout();
        return;
    }

    if (!cartOpen.value) {
        toggleCart();
    }
};
</script>

<template>
    <Head :title="props.card.title" />

    <AuthenticatedLayout>
        <template #header />

        <section v-if="hasProducts" class="mt-8 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="inline-flex flex-col rounded-2xl border border-white/60 bg-white/80 px-4 py-3 shadow-sm backdrop-blur">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Products</p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900">Available products</h3>
                </div>
                <div class="flex items-center gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                        {{ props.products.length }} items
                    </p>
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                <div
                    v-for="product in props.products"
                    :key="product.id"
                    class="group flex h-full flex-col overflow-hidden rounded-3xl border border-white/60 bg-white/85 shadow-lg transition duration-300 ease-out hover:-translate-y-0.5 hover:shadow-2xl"
                >
                    <div class="relative h-44 w-full overflow-hidden">
                        <img
                            v-if="product.image_path"
                            :src="`/storage/${product.image_path}`"
                            alt=""
                            class="h-full w-full object-cover"
                        />
                        <div v-else class="flex h-full items-center justify-center bg-slate-50">
                            <img src="/images/card-placeholder.svg" alt="" class="h-12 w-12 opacity-60" />
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col px-6 pb-6 pt-5">
                        <div class="flex-1 space-y-2">
                            <h4 class="text-lg font-semibold text-slate-900">{{ product.name }}</h4>
                            <p class="text-sm text-slate-600 line-clamp-2">
                                {{ product.description || 'No description available.' }}
                            </p>
                            <p class="text-base font-semibold text-emerald-700">
                                ${{ Number(product.price).toFixed(2) }}
                            </p>
                        </div>
                        <div class="mt-4 space-y-2">
                            <div v-if="getQuantity(product.id) === 0" class="grid gap-2">
                                <button
                                    type="button"
                                    class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-200"
                                    @click="addToCart(product)"
                                >
                                    Add to cart
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex w-full items-center justify-center rounded-xl border border-emerald-200 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-700 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-200"
                                    @click="handleBuyNow(product)"
                                >
                                    Buy now
                                </button>
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    class="inline-flex w-full items-center justify-between gap-3 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm"
                                >
                                    <button
                                        type="button"
                                        class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 transition hover:bg-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-200"
                                        @click="decrement(product.id)"
                                    >
                                        -
                                    </button>
                                    <span class="min-w-[1.75rem] text-center">
                                        {{ getQuantity(product.id) }}
                                    </span>
                                    <button
                                        type="button"
                                        class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 transition hover:bg-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-200"
                                        @click="addToCart(product)"
                                    >
                                        +
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    class="inline-flex w-full items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-200"
                                    @click="handleBuyNow(product)"
                                >
                                    Buy now
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </AuthenticatedLayout>
</template>
