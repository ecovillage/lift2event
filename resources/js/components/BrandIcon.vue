<template>
    <svg viewBox="0 0 24 24" role="img" :aria-label="icon.title" :fill="color ?? `#${icon.hex}`">
        <path :d="icon.path" />
    </svg>
</template>

<script setup>
// Renders a simple-icons brand mark (https://simpleicons.org). Pair with lucide-vue
// icon components for everything that isn't a recognisable third-party brand/logo.
defineProps({
    icon:  { type: Object, required: true }, // e.g. `siWhatsapp` from 'simple-icons'
    color: { type: String, default: null },  // override the brand's default hex color
});
</script>
