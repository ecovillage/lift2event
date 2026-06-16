<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-semibold">
                {{ isAdmin ? t('nav.events') : t('nav.my_events') }}
            </h1>
            <RouterLink :to="{ name: 'admin.events.create' }" class="btn-primary px-4 py-2 rounded font-medium text-sm text-white bg-[--color-primary] hover:bg-[--color-primary-dark] transition-colors">
                + {{ t('event.new') }}
            </RouterLink>
        </div>

        <div v-if="loading" class="text-center text-gray-400 py-16">…</div>

        <div v-else-if="events.length === 0" class="text-center text-gray-400 py-16">
            {{ t('event.none') }}
        </div>

        <div v-else class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-gray-500 text-xs uppercase tracking-wide">
                        <th class="px-4 py-3">{{ t('event.name') }}</th>
                        <th class="px-4 py-3 hidden sm:table-cell">{{ t('event.start') }}</th>
                        <th class="px-4 py-3 hidden md:table-cell">{{ t('event.end') }}</th>
                        <th class="px-4 py-3 hidden lg:table-cell">{{ t('event.location') }}</th>
                        <th class="px-4 py-3 text-right">{{ t('event.ride_count') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="event in events"
                        :key="event.id"
                        class="border-b border-gray-100 last:border-0 hover:bg-gray-50 cursor-pointer"
                        @click="router.push({ name: 'admin.events.edit', params: { id: event.id } })"
                    >
                        <td class="px-4 py-3 font-medium">{{ event.name }}</td>
                        <td class="px-4 py-3 hidden sm:table-cell text-gray-600">{{ fmt(event.start_at) }}</td>
                        <td class="px-4 py-3 hidden md:table-cell text-gray-600">{{ fmt(event.end_at) }}</td>
                        <td class="px-4 py-3 hidden lg:table-cell text-gray-600">{{ event.location?.address ?? '–' }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ event.rides_count }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import api from '@/api/axios';

const { t, locale } = useI18n();
const router = useRouter();
const { isAdmin } = useAuth();

const events  = ref([]);
const loading = ref(true);

const dtFmt = new Intl.DateTimeFormat(locale.value, {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
});
function fmt(iso) {
    return iso ? dtFmt.format(new Date(iso)) : '–';
}

onMounted(async () => {
    try {
        const { data } = await api.get('/events');
        events.value = data;
    } finally {
        loading.value = false;
    }
});
</script>
