<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCartStore } from '@/stores/cart';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Card = {
    id: number;
    title?: string | null;
    name?: string | null;
    slug?: string | null;
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

const cardName = computed(() => props.card.name ?? props.card.title ?? 'Restaurant');
const hasProducts = computed(() => props.products.length > 0);

const { getQuantity, addToCart, decrement, buyNowInstapay } = useCartStore();

const formatPrice = (value: string | number): string => {
    const numeric = Number(value);

    return Number.isFinite(numeric) ? numeric.toFixed(2) : '0.00';
};

const buyNowLoading = ref<number | null>(null);

const handleBuyNow = async (product: Product): Promise<void> => {
    buyNowLoading.value = product.id;
    try {
        const quantity = Math.max(getQuantity(product.id), 1);
        await buyNowInstapay(product.id, quantity);
    } finally {
        buyNowLoading.value = null;
    }
};
</script>

<template>
    <Head :title="cardName" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="rounded-3xl border border-white/60 bg-emerald-50/85 px-6 py-5 shadow-xl backdrop-blur-md backdrop-saturate-150"
            >
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="mb-2 flex items-center gap-2">
                            <Link
                                :href="route('dashboard')"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white/80 px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                                </svg>
                                Dashboard
                            </Link>
                        </div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Products</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900">
                            {{ cardName }}
                        </h3>
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                        {{ props.products.length }} items
                    </p>
                </div>
            </div>
        </template>

        <section class="mt-2 space-y-5">

            <div
                v-if="!hasProducts"
                class="rounded-3xl border border-dashed border-slate-200 bg-white/70 p-10 text-center text-sm text-slate-500"
            >
                No products available for this restaurant yet.
            </div>

            <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                <div
                    v-for="product in props.products"
                    :key="product.id"
                    class="group flex h-full flex-col overflow-hidden rounded-3xl border border-white/60 bg-white/85 shadow-lg transition duration-300 ease-out hover:-translate-y-0.5 hover:shadow-2xl"
                >
                    <div class="relative h-44 w-full overflow-hidden">
                        <img
                            v-if="product.image_path"
                            :src="`/storage/${product.image_path}`"
                            :alt="product.name"
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
                                ${{ formatPrice(product.price) }}
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
