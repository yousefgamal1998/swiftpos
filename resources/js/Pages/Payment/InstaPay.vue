<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import QRCode from 'qrcode';

type OrderItem = {
    id: number;
    product_name: string;
    quantity: string;
    unit_price: string;
    line_total: string;
};

type Order = {
    id: number;
    order_number: string;
    total_amount: string;
    payment_status: string;
    status: string;
    access_token: string | null;
    instapay_screenshot: string | null;
    placed_at: string;
    expires_at: string | null;
    items: OrderItem[];
};

type InstaPayConfig = {
    phone: string;
    account_name: string;
    deeplink_base: string;
};

const props = defineProps<{
    order: Order;
    instapay: InstaPayConfig;
}>();

const page = usePage();
const flash = computed(() => (page.props as Record<string, any>).flash ?? {});

const qrCanvas = ref<HTMLCanvasElement | null>(null);
const uploading = ref(false);
const confirming = ref(false);
const screenshotFile = ref<File | null>(null);
const screenshotPreview = ref<string | null>(null);

const paymentReference = computed(() => `ORDER-${props.order.id}`);
const instapayRouteParams = computed(() => {
    const params: Record<string, string | number> = { order: props.order.id };
    if (props.order.access_token) {
        params.token = props.order.access_token;
    }
    return params;
});

const deepLink = computed(() => {
    const params = new URLSearchParams({
        phone: props.instapay.phone,
        amount: props.order.total_amount,
        note: paymentReference.value,
    });

    return `${props.instapay.deeplink_base}?${params.toString()}`;
});

const qrData = computed(() =>
    `${props.instapay.phone}|${props.order.total_amount}|${paymentReference.value}`,
);

const isPending = computed(() =>
    ['pending', 'unpaid'].includes(props.order.payment_status),
);
const isWaiting = computed(() => props.order.payment_status === 'waiting_confirmation');
const isPaid = computed(() => props.order.payment_status === 'paid');
const isRejected = computed(() => props.order.payment_status === 'rejected');

const statusConfig = computed(() => {
    if (isPaid.value) {
        return { label: 'Payment Confirmed', color: 'bg-emerald-100 text-emerald-700 ring-emerald-200', icon: '✓' };
    }
    if (isWaiting.value) {
        return { label: 'Awaiting Verification', color: 'bg-amber-100 text-amber-700 ring-amber-200', icon: '⏳' };
    }
    if (isRejected.value) {
        return { label: 'Payment Rejected', color: 'bg-rose-100 text-rose-700 ring-rose-200', icon: '✕' };
    }
    return { label: 'Awaiting Payment', color: 'bg-blue-100 text-blue-700 ring-blue-200', icon: '💳' };
});

const currency = (val: string | number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 2 }).format(Number(val));

const handleConfirmSent = () => {
    confirming.value = true;
    router.post(
        route('instapay.confirm', instapayRouteParams.value),
        {},
        {
            preserveScroll: true,
            onFinish: () => { confirming.value = false; },
        },
    );
};

const onFileSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    screenshotFile.value = file;
    screenshotPreview.value = URL.createObjectURL(file);
};

const handleUpload = () => {
    if (!screenshotFile.value) return;

    uploading.value = true;
    router.post(
        route('instapay.screenshot', instapayRouteParams.value),
        { screenshot: screenshotFile.value } as any,
        {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                uploading.value = false;
                screenshotFile.value = null;
                screenshotPreview.value = null;
            },
        },
    );
};

onMounted(async () => {
    if (qrCanvas.value) {
        try {
            await QRCode.toCanvas(qrCanvas.value, qrData.value, {
                width: 220,
                margin: 2,
                color: { dark: '#0f172a', light: '#ffffff' },
            });
        } catch (err) {
            console.error('QR generation failed', err);
        }
    }
});
</script>

<template>
    <Head :title="`Pay — ${props.order.order_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">InstaPay Payment</p>
                    <h2 class="mt-1 text-2xl font-bold text-slate-900">
                        {{ props.order.order_number }}
                    </h2>
                </div>
                <span class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold ring-1" :class="statusConfig.color">
                    <span>{{ statusConfig.icon }}</span>
                    {{ statusConfig.label }}
                </span>
            </div>
        </template>

        <!-- Flash messages -->
        <div v-if="flash.success" class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-medium text-emerald-700">
            {{ flash.success }}
        </div>
        <div v-if="flash.error" class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm font-medium text-rose-700">
            {{ flash.error }}
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr,380px]">
            <!-- Left Column: Payment Instructions -->
            <div class="space-y-6">
                <!-- Order Summary -->
                <article class="overflow-hidden rounded-3xl border border-white/40 bg-white/70 shadow-xl backdrop-blur-md">
                    <header class="border-b border-slate-100 px-6 py-4">
                        <h3 class="text-base font-semibold text-slate-900">Order Summary</h3>
                    </header>
                    <div class="divide-y divide-slate-100">
                        <div v-for="item in props.order.items" :key="item.id" class="flex items-center justify-between px-6 py-3">
                            <div>
                                <p class="font-medium text-slate-800">{{ item.product_name }}</p>
                                <p class="text-xs text-slate-500">Qty {{ Number(item.quantity).toFixed(0) }} × {{ currency(item.unit_price) }}</p>
                            </div>
                            <p class="font-semibold text-slate-900">{{ currency(item.line_total) }}</p>
                        </div>
                    </div>
                    <footer class="flex items-center justify-between border-t border-slate-200 bg-slate-50/60 px-6 py-4">
                        <span class="font-semibold text-slate-700">Total</span>
                        <span class="text-xl font-bold text-emerald-700">{{ currency(props.order.total_amount) }}</span>
                    </footer>
                </article>

                <!-- Payment Instructions Card -->
                <article class="overflow-hidden rounded-3xl border border-white/40 bg-white/70 shadow-xl backdrop-blur-md">
                    <header class="border-b border-slate-100 px-6 py-4">
                        <h3 class="text-base font-semibold text-slate-900">How to Pay</h3>
                    </header>
                    <div class="space-y-5 px-6 py-5">
                        <div class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">1</span>
                            <div>
                                <p class="font-medium text-slate-800">Open your InstaPay app</p>
                                <p class="text-sm text-slate-500">Or scan the QR code on the right with your phone camera.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">2</span>
                            <div>
                                <p class="font-medium text-slate-800">Send payment to this number</p>
                                <div class="mt-2 flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3">
                                    <span class="text-2xl font-bold tracking-wide text-slate-900">{{ props.instapay.phone }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">Account: <strong>{{ props.instapay.account_name }}</strong></p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">3</span>
                            <div>
                                <p class="font-medium text-slate-800">Include this reference in your payment note</p>
                                <div class="mt-2 flex items-center gap-3 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3">
                                    <span class="text-lg font-bold tracking-wide text-amber-800">{{ paymentReference }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">4</span>
                            <div>
                                <p class="font-medium text-slate-800">Confirm your payment below</p>
                                <p class="text-sm text-slate-500">Click "I Have Sent The Payment" and optionally upload a screenshot.</p>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Actions -->
                <article v-if="isPending || isWaiting" class="overflow-hidden rounded-3xl border border-white/40 bg-white/70 shadow-xl backdrop-blur-md">
                    <div class="space-y-4 px-6 py-5">
                        <!-- Open InstaPay App -->
                        <a
                            :href="deepLink"
                            class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-emerald-500/30"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                            Open InstaPay App
                        </a>

                        <!-- I Have Sent Payment -->
                        <button
                            v-if="isPending"
                            type="button"
                            :disabled="confirming"
                            class="flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-amber-300 bg-amber-50 px-6 py-3.5 text-sm font-semibold text-amber-800 transition hover:-translate-y-0.5 hover:border-amber-400 hover:bg-amber-100 disabled:opacity-50"
                            @click="handleConfirmSent"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ confirming ? 'Confirming...' : 'I Have Sent The Payment' }}
                        </button>

                        <!-- Screenshot Upload -->
                        <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50 p-5">
                            <p class="mb-3 text-sm font-medium text-slate-700">Upload payment screenshot (optional)</p>
                            <label class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-5 transition hover:border-emerald-300 hover:bg-emerald-50/30">
                                <svg class="h-8 w-8 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                                <span class="text-sm text-slate-500">Click to choose a file</span>
                                <input type="file" accept="image/*" class="sr-only" @change="onFileSelected" />
                            </label>
                            <div v-if="screenshotPreview" class="mt-4 space-y-3">
                                <img :src="screenshotPreview" alt="Screenshot preview" class="max-h-48 rounded-xl border border-slate-200 shadow-sm" />
                                <button
                                    type="button"
                                    :disabled="uploading"
                                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50"
                                    @click="handleUpload"
                                >
                                    {{ uploading ? 'Uploading...' : 'Upload Screenshot' }}
                                </button>
                            </div>
                        </div>

                        <!-- Already uploaded screenshot -->
                        <div v-if="props.order.instapay_screenshot && !screenshotPreview" class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4">
                            <p class="mb-2 text-sm font-medium text-emerald-700">📎 Screenshot uploaded</p>
                            <img :src="`/storage/${props.order.instapay_screenshot}`" alt="Payment screenshot" class="max-h-48 rounded-xl border border-emerald-200 shadow-sm" />
                        </div>
                    </div>
                </article>

                <!-- Paid / Rejected state -->
                <article v-if="isPaid" class="rounded-3xl border border-emerald-200 bg-emerald-50 px-6 py-8 text-center shadow-xl">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                        <svg class="h-8 w-8 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-emerald-800">Payment Confirmed!</h3>
                    <p class="mt-2 text-sm text-emerald-600">Your order has been confirmed and is being processed.</p>
                </article>

                <article v-if="isRejected" class="rounded-3xl border border-rose-200 bg-rose-50 px-6 py-8 text-center shadow-xl">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-rose-100">
                        <svg class="h-8 w-8 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-rose-800">Payment Rejected</h3>
                    <p class="mt-2 text-sm text-rose-600">Your payment could not be verified. Please contact support or try again.</p>
                </article>
            </div>

            <!-- Right Column: QR Code & Info -->
            <div class="space-y-6">
                <article class="sticky top-6 overflow-hidden rounded-3xl border border-white/40 bg-white/70 shadow-xl backdrop-blur-md">
                    <div class="flex flex-col items-center px-6 py-6">
                        <p class="mb-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Scan to Pay</p>
                        <div class="rounded-2xl border border-slate-100 bg-white p-3 shadow-inner">
                            <canvas ref="qrCanvas" />
                        </div>
                        <p class="mt-4 text-center text-xs text-slate-500">
                            Scan this QR code with your InstaPay app to auto-fill payment details.
                        </p>
                    </div>

                    <div class="space-y-3 border-t border-slate-100 px-6 py-5">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Order ID</span>
                            <span class="font-semibold text-slate-900">{{ props.order.id }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Phone</span>
                            <span class="font-semibold text-slate-900">{{ props.instapay.phone }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Amount</span>
                            <span class="font-semibold text-emerald-700">{{ currency(props.order.total_amount) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Reference</span>
                            <span class="font-semibold text-amber-700">{{ paymentReference }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Account</span>
                            <span class="font-semibold text-slate-900">{{ props.instapay.account_name }}</span>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
