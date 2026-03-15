<script setup lang="ts">
withDefaults(
    defineProps<{
        variant?: 'full' | 'mark';
    }>(),
    {
        variant: 'full',
    },
);
</script>

<template>
    <svg
        v-if="variant === 'mark'"
        viewBox="0 0 64 64"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        aria-label="SwiftPOS"
    >
        <defs>
            <linearGradient id="swiftpos-mark-gradient" x1="14" y1="10" x2="50" y2="52" gradientUnits="userSpaceOnUse">
                <stop stop-color="#34d399" />
                <stop offset="1" stop-color="#10b981" />
            </linearGradient>
        </defs>

        <rect x="6" y="6" width="52" height="52" rx="16" fill="#0f172a" />
        <rect x="18" y="21" width="28" height="26" rx="6" fill="url(#swiftpos-mark-gradient)" />
        <path d="M23 22v-3a9 9 0 0 1 18 0v3" stroke="#0f172a" stroke-width="3" stroke-linecap="round" />
        <path d="M25.5 33h13" stroke="#0f172a" stroke-width="2.6" stroke-linecap="round" />
        <path d="M25.5 39.5h8" stroke="#0f172a" stroke-width="2.6" stroke-linecap="round" />
        <circle cx="43" cy="43" r="6" fill="#f59e0b" />
        <path d="M40.7 43h4.6" stroke="#0f172a" stroke-width="2.2" stroke-linecap="round" />
    </svg>

    <svg
        v-else
        viewBox="0 0 272 60"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        aria-label="SwiftPOS"
    >
        <defs>
            <linearGradient id="swiftpos-wordmark-gradient" x1="12" y1="8" x2="48" y2="48" gradientUnits="userSpaceOnUse">
                <stop stop-color="#34d399" />
                <stop offset="1" stop-color="#10b981" />
            </linearGradient>
        </defs>

        <rect x="4" y="6" width="48" height="48" rx="15" fill="#0f172a" />
        <rect x="15" y="21" width="26" height="23" rx="5" fill="url(#swiftpos-wordmark-gradient)" />
        <path d="M20 22v-2.5a8 8 0 1 1 16 0V22" stroke="#0f172a" stroke-width="2.8" stroke-linecap="round" />
        <path d="M22 31.5h12" stroke="#0f172a" stroke-width="2.3" stroke-linecap="round" />
        <path d="M22 37h7" stroke="#0f172a" stroke-width="2.3" stroke-linecap="round" />
        <circle cx="38.5" cy="39.5" r="5.2" fill="#f59e0b" />
        <path d="M36.5 39.5h4" stroke="#0f172a" stroke-width="2" stroke-linecap="round" />

        <g fill="currentColor" font-family="Manrope, 'Segoe UI', sans-serif">
            <text x="62" y="32" font-size="29" font-weight="700" letter-spacing="0.4">SwiftPOS</text>
            <text x="63" y="45" font-size="10" font-weight="600" letter-spacing="2.1" opacity="0.62">RESTAURANT + RETAIL</text>
        </g>
    </svg>
</template>
