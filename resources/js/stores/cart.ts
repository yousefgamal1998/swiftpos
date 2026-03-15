import axios from 'axios';
import { computed, reactive, ref } from 'vue';

export type CartProduct = {
    id: number;
    name: string;
    description?: string | null;
    price: string | number;
};

type CartItem = {
    cartItemId?: number;
    product: CartProduct;
    quantity: number;
};

type CartPayloadItem = {
    id: number;
    product_id: number;
    quantity: number;
    product: CartProduct;
};

const cartItemsByProduct = reactive<Record<number, CartItem>>({});
const cartOpen = ref(false);
const cartLoaded = ref(false);
const cartLoading = ref(false);
const isAuthenticated = ref(false);
const guestStorageKey = 'marketplace_guest_cart';

const cartItems = computed(() =>
    Object.values(cartItemsByProduct).map((item) => ({
        ...item,
        total: Number(item.product.price) * item.quantity,
    })),
);

const cartCount = computed(() =>
    Object.values(cartItemsByProduct).reduce((total, item) => total + item.quantity, 0),
);

const cartTotal = computed(() =>
    cartItems.value.reduce((total, item) => total + item.total, 0),
);

const getQuantity = (productId: number): number => cartItemsByProduct[productId]?.quantity ?? 0;

const syncCart = (items: CartPayloadItem[]): void => {
    Object.keys(cartItemsByProduct).forEach((key) => {
        delete cartItemsByProduct[Number(key)];
    });

    items.forEach((item) => {
        cartItemsByProduct[item.product_id] = {
            cartItemId: item.id,
            product: item.product,
            quantity: item.quantity,
        };
    });
};

const fetchCart = async (): Promise<void> => {
    if (!isAuthenticated.value) {
        loadGuestCart();
        cartLoaded.value = true;
        return;
    }

    if (cartLoading.value) {
        return;
    }

    cartLoading.value = true;
    try {
        const { data } = await axios.get(route('cart.show'));
        syncCart(data.items ?? []);
        cartLoaded.value = true;
    } finally {
        cartLoading.value = false;
    }
};

const addToCart = async (product: CartProduct): Promise<void> => {
    const existing = cartItemsByProduct[product.id];
    if (existing) {
        await updateQuantity(product.id, existing.quantity + 1);
        return;
    }

    if (!isAuthenticated.value) {
        cartItemsByProduct[product.id] = {
            product,
            quantity: 1,
        };
        persistGuestCart();
        return;
    }

    const { data } = await axios.post(route('cart.items.store'), {
        product_id: product.id,
        quantity: 1,
    });
    syncCart(data.items ?? []);
};

const updateQuantity = async (productId: number, quantity: number): Promise<void> => {
    const item = cartItemsByProduct[productId];
    if (!item) {
        return;
    }

    if (!isAuthenticated.value) {
        if (quantity <= 0) {
            delete cartItemsByProduct[productId];
        } else {
            cartItemsByProduct[productId].quantity = quantity;
        }
        persistGuestCart();
        return;
    }

    const { data } = await axios.patch(route('cart.items.update', item.cartItemId), { quantity });
    syncCart(data.items ?? []);
};

const decrement = async (productId: number): Promise<void> => {
    const current = getQuantity(productId) - 1;
    if (current <= 0) {
        await removeItem(productId);
        return;
    }

    await updateQuantity(productId, current);
};

const removeItem = async (productId: number): Promise<void> => {
    const item = cartItemsByProduct[productId];
    if (!item) {
        return;
    }

    if (!isAuthenticated.value) {
        delete cartItemsByProduct[productId];
        persistGuestCart();
        return;
    }

    const { data } = await axios.delete(route('cart.items.destroy', item.cartItemId));
    syncCart(data.items ?? []);
};

const toggleCart = (): void => {
    cartOpen.value = !cartOpen.value;
};

const closeCart = (): void => {
    cartOpen.value = false;
};

const readGuestCart = (): CartItem[] => {
    const stored = window.localStorage.getItem(guestStorageKey);
    if (!stored) {
        return [];
    }

    try {
        const parsed = JSON.parse(stored) as Array<{ product: CartProduct; quantity: number }>;
        if (!Array.isArray(parsed)) {
            return [];
        }

        return parsed
            .filter((item) => item?.product?.id && item.quantity > 0)
            .map((item) => ({
                product: item.product,
                quantity: item.quantity,
            }));
    } catch {
        window.localStorage.removeItem(guestStorageKey);
        return [];
    }
};

const loadGuestCart = (): void => {
    const items = readGuestCart();
    Object.keys(cartItemsByProduct).forEach((key) => {
        delete cartItemsByProduct[Number(key)];
    });
    items.forEach((item) => {
        cartItemsByProduct[item.product.id] = {
            product: item.product,
            quantity: item.quantity,
        };
    });
};

const persistGuestCart = (): void => {
    const payload = Object.values(cartItemsByProduct).map((item) => ({
        product: item.product,
        quantity: item.quantity,
    }));
    window.localStorage.setItem(guestStorageKey, JSON.stringify(payload));
};

const mergeGuestCart = async (): Promise<void> => {
    const items = readGuestCart();
    if (!items.length) {
        await fetchCart();
        return;
    }

    for (const item of items) {
        await axios.post(route('cart.items.store'), {
            product_id: item.product.id,
            quantity: item.quantity,
        });
    }

    window.localStorage.removeItem(guestStorageKey);
    await fetchCart();
};

const setUser = async (userId: number | null): Promise<void> => {
    const wasAuthenticated = isAuthenticated.value;
    isAuthenticated.value = Boolean(userId);

    if (isAuthenticated.value && !wasAuthenticated) {
        await mergeGuestCart();
        return;
    }

    if (isAuthenticated.value) {
        await fetchCart();
        return;
    }

    loadGuestCart();
    cartLoaded.value = true;
};

const buyNowInstapay = async (productId: number, quantity: number = 1): Promise<void> => {
    const { data } = await axios.post(route('instapay.initiate'), {
        product_id: productId,
        quantity,
    });

    if (data?.redirect) {
        window.location.href = data.redirect;
    }
};

const checkout = async (): Promise<void> => {
    if (!cartCount.value || !isAuthenticated.value) {
        return;
    }

    const { data } = await axios.post(route('cart.checkout'));
    syncCart([]);
    if (data?.order_id) {
        window.location.href = route('orders.show', data.order_id);
    }
};

export const useCartStore = () => ({
    cartItems,
    cartCount,
    cartTotal,
    cartOpen,
    cartLoaded,
    cartLoading,
    isAuthenticated,
    getQuantity,
    fetchCart,
    addToCart,
    updateQuantity,
    decrement,
    removeItem,
    toggleCart,
    closeCart,
    setUser,
    buyNowInstapay,
    checkout,
});
