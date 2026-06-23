<template>
    <div class="max-w-2xl mx-auto px-4 py-10">
        <h1 class="text-xl font-bold text-gray-900 mb-4">{{ t('footer.imprint') }}</h1>
        <p class="text-sm text-gray-600">{{ t('imprint.placeholder') }}</p>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>
