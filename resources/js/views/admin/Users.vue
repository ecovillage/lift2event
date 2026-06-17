<template>
    <div>
        <h1 class="text-xl font-semibold mb-6">{{ t('nav.users') }}</h1>

        <div v-if="loading" class="text-center text-gray-400 py-16">…</div>

        <div v-else class="bg-white rounded-lg shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-gray-500 text-xs uppercase tracking-wide">
                        <th class="px-4 py-3">{{ t('users.name') }}</th>
                        <th class="px-4 py-3 hidden sm:table-cell">{{ t('users.email') }}</th>
                        <th class="px-4 py-3 text-right hidden md:table-cell">{{ t('users.event_count') }}</th>
                        <th class="px-4 py-3 text-center">{{ t('users.approved') }}</th>
                        <th class="px-4 py-3 w-16"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="u in users"
                        :key="u.id"
                        class="border-b border-gray-100 last:border-0"
                        :data-testid="`user-row-${u.id}`"
                    >
                        <td class="px-4 py-3 font-medium">{{ u.name }}</td>
                        <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ u.email }}</td>
                        <td class="px-4 py-3 text-right hidden md:table-cell text-gray-600">{{ u.events_count }}</td>
                        <td class="px-4 py-3 text-center">
                            <button
                                :class="['px-3 py-1 rounded-full text-xs font-medium transition-colors',
                                    u.approved
                                        ? 'bg-green-100 text-green-700 hover:bg-green-200'
                                        : 'bg-gray-100 text-gray-500 hover:bg-gray-200']"
                                :data-testid="`approve-btn-${u.id}`"
                                @click="toggleApprove(u)"
                            >
                                {{ u.approved ? '✓ ' + t('users.approved') : t('users.approve') }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button
                                v-if="u.id !== me?.id"
                                class="text-xs text-red-500 hover:text-red-700 px-2 py-1 rounded hover:bg-red-50"
                                :data-testid="`delete-btn-${u.id}`"
                                @click="confirmDelete(u)"
                            >{{ t('users.delete') }}</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuth } from '@/composables/useAuth';
import api from '@/api/axios';

const { t } = useI18n();
const { state } = useAuth();
const me = computed(() => state.user);

const users   = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const { data } = await api.get('/users');
        users.value = data;
    } finally {
        loading.value = false;
    }
});

async function toggleApprove(user) {
    const { data } = await api.put(`/users/${user.id}/approve`);
    const idx = users.value.findIndex(u => u.id === user.id);
    if (idx !== -1) users.value[idx] = data;
}

async function confirmDelete(user) {
    if (! window.confirm(`${user.name} wirklich löschen?`)) return;
    await api.delete(`/users/${user.id}`);
    users.value = users.value.filter(u => u.id !== user.id);
}
</script>
