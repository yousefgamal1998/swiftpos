<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed, reactive, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Product {
    id: number;
    category_id: number | null;
    sku: string;
    name: string;
    price: string | number;
    tax_rate: string | number;
    track_inventory: boolean;
    stock_quantity: string | number;
    unit: string;
}

interface Category {
    id: number;
    name: string;
}

interface PosSession {
    id: number;
    opened_at: string;
    opening_cash: string | number;
    status: string;
}

interface RecentOrder {
    id: number;
    order_number: string;
    total_amount: string | number;
    payment_status: string;
    placed_at: string;
}

interface CartItem {
    product: Product;
    quantity: number;
}

const props = defineProps<{
    products: Product[];
    categories: Category[];
    session: PosSession | null;
    recentOrders: RecentOrder[];
    paymentMethods: string[];
    orderTypes: string[];
    filters: {
        search?: string;
        category_id?: number | null;
    };
}>();

const search = ref(props.filters.search ?? '');
const selectedCategory = ref(props.filters.category_id ? String(props.filters.category_id) : '');
const cart = reactive<CartItem[]>([]);

const orderType = ref('retail');
const paymentMethod = ref('cash');
const amountTendered = ref(0);
const customerName = ref('');
const notes = ref('');

const openSessionForm = useForm({
    opening_cash: '0',
    notes: '',
});

const closeSessionForm = useForm({
    closing_cash: '0',
});

const checkoutForm = useForm({
    pos_session_id: props.session?.id ?? null,
    order_type: 'retail',
    customer_name: '',
    notes: '',
    payment_method: 'cash',
    amount_tendered: 0,
    payment_reference: '',
    items: [] as Array<{
        product_id: number;
        quantity: number;
    }>,
});

const filteredProducts = computed(() =>
    props.products.filter((product) => {
        const matchesCategory =
            selectedCategory.value === '' || Number(selectedCategory.value) === product.category_id;
        const term = search.value.trim().toLowerCase();
        const matchesSearch =
            term === '' ||
            product.name.toLowerCase().includes(term) ||
            product.sku.toLowerCase().includes(term);

        return matchesCategory && matchesSearch;
    }),
);

const subtotal = computed(() =>
    cart.reduce((sum, item) => sum + Number(item.product.price) * item.quantity, 0),
);

const taxTotal = computed(() =>
    cart.reduce(
        (sum, item) =>
            sum + Number(item.product.price) * item.quantity * (Number(item.product.tax_rate) / 100),
        0,
    ),
);

const grandTotal = computed(() => Math.max(subtotal.value + taxTotal.value, 0));
const changeAmount = computed(() => Math.max(amountTendered.value - grandTotal.value, 0));

const addProduct = (product: Product): void => {
    const line = cart.find((item) => item.product.id === product.id);
    const nextQty = line ? line.quantity + 1 : 1;

    if (product.track_inventory && nextQty > Number(product.stock_quantity)) {
        return;
    }

    if (line) {
        line.quantity += 1;
        return;
    }

    cart.push({ product, quantity: 1 });
};

const removeProduct = (productId: number): void => {
    const index = cart.findIndex((item) => item.product.id === productId);
    if (index !== -1) {
        cart.splice(index, 1);
    }
};

const updateQuantity = (product: Product, quantity: number): void => {
    const line = cart.find((item) => item.product.id === product.id);
    if (!line) {
        return;
    }

    if (quantity <= 0) {
        removeProduct(product.id);
        return;
    }

    if (product.track_inventory && quantity > Number(product.stock_quantity)) {
        return;
    }

    line.quantity = quantity;
};

const submitCheckout = (): void => {
    if (!props.session || cart.length === 0) {
        return;
    }

    checkoutForm.pos_session_id = props.session.id;
    checkoutForm.order_type = orderType.value;
    checkoutForm.customer_name = customerName.value;
    checkoutForm.notes = notes.value;
    checkoutForm.payment_method = paymentMethod.value;
    checkoutForm.amount_tendered = amountTendered.value;
    checkoutForm.items = cart.map((item) => ({
        product_id: item.product.id,
        quantity: item.quantity,
    }));

    checkoutForm.post(route('pos.checkout'), {
        preserveScroll: true,
        onSuccess: () => {
            cart.splice(0, cart.length);
            amountTendered.value = 0;
            customerName.value = '';
            notes.value = '';
            orderType.value = 'retail';
            paymentMethod.value = 'cash';
        },
    });
};

const openSession = (): void => {
    openSessionForm.post(route('pos.sessions.open'));
};

const closeSession = (): void => {
    if (!props.session) {
        return;
    }

    closeSessionForm.post(route('pos.sessions.close', props.session.id));
};

const currency = (value: string | number): string =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value));
</script>

<template>
    <Head title="Cashier POS" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-xl font-semibold leading-tight text-slate-800">Cashier POS</h2>
                <div class="text-sm text-slate-600">
                    <span v-if="session" class="rounded bg-emerald-100 px-2 py-1 font-medium text-emerald-700">
                        Session Open
                    </span>
                    <span v-else class="rounded bg-amber-100 px-2 py-1 font-medium text-amber-700">Session Closed</span>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 pb-8 sm:px-6 lg:px-8">
            <div v-if="!session" class="rounded-3xl border border-amber-200 bg-amber-50/80 p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-amber-800">Open POS Session</h3>
                <form @submit.prevent="openSession" class="mt-4 flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs text-amber-700">Opening Cash</label>
                        <input
                            v-model="openSessionForm.opening_cash"
                            type="number"
                            step="0.01"
                            min="0"
                            class="pos-input mt-1 border-amber-200 bg-white"
                        />
                    </div>
                    <div class="min-w-52 flex-1">
                        <label class="block text-xs text-amber-700">Notes</label>
                        <input
                            v-model="openSessionForm.notes"
                            type="text"
                            class="pos-input mt-1"
                            placeholder="Optional shift note"
                        />
                    </div>
                    <button
                        type="submit"
                        class="pos-btn pos-btn-secondary bg-amber-600 hover:bg-amber-700"
                    >
                        Open Session
                    </button>
                </form>
            </div>

            <div v-else class="pos-panel p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="text-sm text-slate-700">
                        Session #{{ session.id }} opened at {{ new Date(session.opened_at).toLocaleString() }} with
                        {{ currency(session.opening_cash) }}
                    </div>
                    <div class="flex items-center gap-2">
                        <input
                            v-model="closeSessionForm.closing_cash"
                            type="number"
                            step="0.01"
                            min="0"
                            class="pos-input max-w-[140px]"
                            placeholder="Closing cash"
                        />
                        <button
                            type="button"
                            @click="closeSession"
                            class="pos-btn pos-btn-danger"
                        >
                            Close Session
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="xl:col-span-2">
                    <div class="pos-panel p-4">
                        <div class="grid gap-3 md:grid-cols-3">
                            <input
                                v-model="search"
                                type="text"
                                class="pos-input"
                                placeholder="Search products"
                            />
                            <select
                                v-model="selectedCategory"
                                class="pos-input"
                            >
                                <option value="">All categories</option>
                                <option v-for="category in categories" :key="category.id" :value="String(category.id)">
                                    {{ category.name }}
                                </option>
                            </select>
                            <div class="text-sm text-slate-600">
                                Showing {{ filteredProducts.length }} products
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <button
                                v-for="product in filteredProducts"
                                :key="product.id"
                                type="button"
                                @click="addProduct(product)"
                                class="rounded-2xl border border-slate-200 bg-white/80 p-3 text-left shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50/80"
                            >
                                <p class="font-medium text-slate-800">{{ product.name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ product.sku }}</p>
                                <div class="mt-2 flex items-center justify-between">
                                    <span class="text-sm font-semibold text-emerald-700">{{ currency(product.price) }}</span>
                                    <span class="text-xs text-slate-500">
                                        {{ Number(product.stock_quantity).toFixed(2) }} {{ product.unit }}
                                    </span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="pos-panel p-4">
                    <h3 class="text-sm font-semibold text-slate-800">Current Cart</h3>

                    <div class="mt-3 max-h-72 space-y-2 overflow-y-auto">
                        <div v-if="cart.length === 0" class="rounded border border-dashed border-slate-300 p-3 text-sm text-slate-500">
                            Add products to start a sale.
                        </div>
                        <div v-for="line in cart" :key="line.product.id" class="rounded-2xl border border-slate-200 bg-white/80 p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-medium text-slate-800">{{ line.product.name }}</p>
                                    <p class="text-xs text-slate-500">{{ currency(line.product.price) }} each</p>
                                </div>
                                <button type="button" @click="removeProduct(line.product.id)" class="text-xs font-semibold text-rose-600">
                                    Remove
                                </button>
                            </div>
                            <div class="mt-2 flex items-center justify-between">
                                <input
                                    :value="line.quantity"
                                    type="number"
                                    min="1"
                                    step="1"
                                    class="pos-input w-20"
                                    @input="
                                        updateQuantity(
                                            line.product,
                                            Number(($event.target as HTMLInputElement).value),
                                        )
                                    "
                                />
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ currency(Number(line.product.price) * line.quantity) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2 border-t border-slate-200 pt-4 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">Subtotal</span>
                            <span class="font-medium text-slate-900">{{ currency(subtotal) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">Tax</span>
                            <span class="font-medium text-slate-900">{{ currency(taxTotal) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">Order Discount</span>
                            <span class="font-medium text-slate-900">{{ currency(0) }}</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-200 pt-2">
                            <span class="text-base font-semibold text-slate-800">Total</span>
                            <span class="text-lg font-bold text-emerald-700">{{ currency(grandTotal) }}</span>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3">
                        <select
                            v-model="orderType"
                            class="pos-input"
                        >
                            <option v-for="type in orderTypes" :key="type" :value="type">
                                {{ type.replace('_', ' ') }}
                            </option>
                        </select>
                        <input
                            v-model="customerName"
                            type="text"
                            class="pos-input"
                            placeholder="Customer name (optional)"
                        />
                        <select
                            v-model="paymentMethod"
                            class="pos-input"
                        >
                            <option v-for="method in paymentMethods" :key="method" :value="method">
                                {{ method.replace('_', ' ') }}
                            </option>
                        </select>
                        <input
                            v-model.number="amountTendered"
                            type="number"
                            step="0.01"
                            min="0"
                            class="pos-input"
                            placeholder="Amount tendered"
                        />
                        <textarea
                            v-model="notes"
                            rows="2"
                            class="pos-input"
                            placeholder="Order notes"
                        />
                    </div>

                    <div class="mt-3 text-right text-sm text-slate-600">
                        Change: <span class="font-semibold text-slate-900">{{ currency(changeAmount) }}</span>
                    </div>

                    <button
                        type="button"
                        :disabled="!session || cart.length === 0 || checkoutForm.processing"
                        @click="submitCheckout"
                        class="pos-btn pos-btn-primary mt-4 w-full disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Complete Checkout
                    </button>
                    <p v-if="checkoutForm.errors.items" class="mt-2 text-xs text-rose-600">
                        {{ checkoutForm.errors.items }}
                    </p>
                </div>
            </div>

            <div class="pos-panel overflow-hidden">
                <div class="pos-panel-header">
                    <h3 class="pos-panel-title">My Recent Orders</h3>
                    <Link :href="route('orders.index')" class="pos-btn pos-btn-ghost text-sm">
                        View all
                    </Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="pos-table">
                        <thead>
                            <tr>
                                <th class="px-4 py-3">Order</th>
                                <th class="px-4 py-3">Total</th>
                                <th class="px-4 py-3">Payment</th>
                                <th class="px-4 py-3">Placed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="recentOrders.length === 0">
                                <td class="px-4 py-4 text-slate-500" colspan="4">No recent orders.</td>
                            </tr>
                            <tr v-for="order in recentOrders" :key="order.id" class="border-t border-slate-100">
                                <td class="px-4 py-3 font-medium text-slate-800">
                                    <Link :href="route('orders.show', order.id)" class="hover:text-emerald-700">
                                        {{ order.order_number }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ currency(order.total_amount) }}</td>
                                <td class="px-4 py-3 capitalize text-slate-700">{{ order.payment_status }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ new Date(order.placed_at).toLocaleString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
