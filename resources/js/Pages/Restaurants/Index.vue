<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

type RestaurantRow = {
    id: number;
    name?: string | null;
    title?: string | null;
    image?: string | null;
    created_at?: string | null;
};

const props = defineProps<{
    restaurants: RestaurantRow[];
}>();

const fallbackNames = [
    'New York Restaurants',
    'Los Angeles Restaurants',
    'Chicago Restaurants',
    'Houston Restaurants',
    'Phoenix Restaurants',
    'Philadelphia Restaurants',
    'San Antonio Restaurants',
    'San Diego Restaurants',
    'Dallas Restaurants',
    'San Jose Restaurants',
    'Austin Restaurants',
    'Jacksonville Restaurants',
];

const displayName = (restaurant: RestaurantRow, index: number): string =>
    restaurant.name ??
    restaurant.title ??
    fallbackNames[index % fallbackNames.length] ??
    `Restaurant #${restaurant.id}`;

const imageUrl = (restaurant: RestaurantRow): string => {
    if (!restaurant.image) {
        return '/images/restaurants.jpg';
    }

    if (restaurant.image.startsWith('http://') || restaurant.image.startsWith('https://')) {
        return restaurant.image;
    }

    return `/storage/${restaurant.image}`;
};
</script>

<template>
    <Head title="Restaurants" />

    <AuthenticatedLayout>
        <template #header>
            <section class="relative isolate overflow-hidden rounded-2xl shadow-xl">
                <img
                    src="/images/restaurants.jpg"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover"
                    loading="lazy"
                    decoding="async"
                />
                <div class="absolute inset-0 bg-black/50" aria-hidden="true" />
                <div class="absolute inset-0 z-10 flex items-center justify-between gap-6 px-6 py-10 sm:px-10 sm:py-12">
                    <div class="max-w-3xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gray-200">
                            Restaurants Directory
                        </p>
                        <h2 class="mt-3 text-3xl font-bold text-white sm:text-4xl lg:text-5xl">Restaurants</h2>
                        <p class="mt-3 text-sm text-gray-200 sm:text-base">
                            Manage locations, performance insights, and the dining experience across every
                            location in your portfolio.
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <p class="text-sm font-semibold text-gray-200">{{ props.restaurants.length }} total</p>
                        <Link
                            :href="route('dashboard')"
                            class="inline-flex items-center rounded-xl border border-white/30 bg-white/10 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-white/20"
                        >
                            Back to Dashboard
                        </Link>
                    </div>
                </div>
            </section>
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white/80 px-6 py-4 shadow-sm">
                <div>
                    <h3 class="text-xl font-semibold text-slate-900">All Restaurants</h3>
                    <p class="text-sm text-slate-600">Manage locations and performance insights.</p>
                </div>
            </div>

            <section class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                <div
                    v-if="props.restaurants.length === 0"
                    class="col-span-full rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500 shadow-sm"
                >
                    No restaurants found.
                </div>

                <article
                    v-for="(restaurant, index) in props.restaurants"
                    :key="restaurant.id"
                    class="group relative overflow-hidden rounded-2xl bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md"
                >
                    <img
                        :src="imageUrl(restaurant)"
                        :alt="displayName(restaurant, index)"
                        loading="lazy"
                        decoding="async"
                        class="h-52 w-full object-cover"
                    />
                    <div class="relative p-4">
                        <h4 class="text-lg font-semibold text-slate-900">
                            {{ displayName(restaurant, index) }}
                        </h4>
                        <span
                            class="absolute bottom-4 right-4 inline-flex items-center rounded-full bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow"
                        >
                            #{{ restaurant.id }}
                        </span>
                    </div>
                </article>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
