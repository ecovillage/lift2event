<template>
    <span :class="['inline-flex items-center gap-1.5 font-semibold', sizeClass, colorClass]">
        <slot />
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: { type: String, default: 'warning' }, // 'offer' | 'request' | 'warning'
    size:    { type: String, default: 'md' },       // 'sm' | 'md'
});

const colorClass = computed(() => ({
    offer:   'bg-[var(--color-offer-light)] text-[var(--color-offer)]',
    request: 'bg-[var(--color-request-light)] text-[var(--color-request)]',
    warning: 'bg-orange-100 text-orange-600',
}[props.variant]));

const sizeClass = computed(() => props.size === 'sm'
    ? 'px-1.5 py-0.5 rounded text-[10px] uppercase tracking-wide'
    : 'px-3 py-1.5 rounded-full text-sm');
</script>
