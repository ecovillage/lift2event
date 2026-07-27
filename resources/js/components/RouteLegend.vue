<template>
    <div class="absolute bottom-2 left-2 z-[1000] bg-white rounded shadow px-2.5 py-1.5 text-xs space-y-1">
        <div class="flex items-center gap-2">
            <span class="inline-block w-8 h-1" :style="{ backgroundColor: OFFER_COLOR }"></span>
            <span>{{ t('ride.offer') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-block w-8 h-1" :style="{ backgroundColor: REQUEST_COLOR }"></span>
            <span>{{ t('ride.request') }}</span>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { OFFER_COLOR, REQUEST_COLOR } from '@/constants/rideColors';

const { t } = useI18n();
</script>
