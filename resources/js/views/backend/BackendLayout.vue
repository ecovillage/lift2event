<template>
    <div class="min-h-screen flex flex-col bg-gray-100">
        <header class="bg-white shadow-sm">
            <div class="max-w-6xl mx-auto px-4 flex items-center justify-between h-14">
                <RouterLink :to="{ name: 'backend.events' }" class="flex items-center gap-2">
                    <img src="/logo.svg" alt="Lift2Event" class="h-8" />
                </RouterLink>

                <!-- Desktop nav -->
                <nav class="hidden sm:flex items-center gap-1">
                    <RouterLink
                        v-if="isAdmin"
                        :to="{ name: 'backend.users' }"
                        class="nav-link"
                        :class="{ active: $route.name === 'backend.users' }"
                    >{{ t('nav.users') }}</RouterLink>

                    <RouterLink
                        :to="{ name: 'backend.events' }"
                        class="nav-link"
                        :class="{ active: $route.name?.startsWith('backend.events') }"
                    >{{ isAdmin ? t('nav.events') : t('nav.my_events') }}</RouterLink>

                    <button @click="openAnalytics" class="nav-link">
                        {{ t('nav.analytics') }}
                    </button>

                    <RouterLink
                        v-if="isAdmin"
                        :to="{ name: 'backend.settings' }"
                        class="nav-link"
                        :class="{ active: $route.name === 'backend.settings' }"
                    >{{ t('nav.settings') }}</RouterLink>

                    <RouterLink
                        :to="{ name: 'backend.profile' }"
                        class="nav-link"
                        :class="{ active: $route.name === 'backend.profile' }"
                    >{{ t('nav.profile') }}</RouterLink>

                    <button @click="doLogout" class="nav-link text-gray-500">
                        {{ t('nav.logout') }}
                    </button>
                </nav>

                <!-- Mobile hamburger -->
                <button
                    class="sm:hidden p-2 rounded text-gray-600 hover:bg-gray-100"
                    @click="menuOpen = !menuOpen"
                    aria-label="Menü"
                >
                    <Menu v-if="!menuOpen" class="w-6 h-6" :stroke-width="2" />
                    <X v-else class="w-6 h-6" :stroke-width="2" />
                </button>
            </div>

            <!-- Mobile dropdown -->
            <nav v-if="menuOpen" class="sm:hidden border-t border-gray-200 bg-white px-4 py-2 flex flex-col gap-1">
                <RouterLink
                    v-if="isAdmin"
                    :to="{ name: 'backend.users' }"
                    class="nav-link"
                    @click="menuOpen = false"
                >{{ t('nav.users') }}</RouterLink>

                <RouterLink
                    :to="{ name: 'backend.events' }"
                    class="nav-link"
                    @click="menuOpen = false"
                >{{ isAdmin ? t('nav.events') : t('nav.my_events') }}</RouterLink>

                <button @click="openAnalytics(); menuOpen = false" class="nav-link text-left">
                    {{ t('nav.analytics') }}
                </button>

                <RouterLink
                    v-if="isAdmin"
                    :to="{ name: 'backend.settings' }"
                    class="nav-link"
                    @click="menuOpen = false"
                >{{ t('nav.settings') }}</RouterLink>

                <RouterLink
                    :to="{ name: 'backend.profile' }"
                    class="nav-link"
                    @click="menuOpen = false"
                >{{ t('nav.profile') }}</RouterLink>

                <button @click="doLogout" class="nav-link text-left text-gray-500">
                    {{ t('nav.logout') }}
                </button>
            </nav>
        </header>

        <main class="flex-1 max-w-6xl w-full mx-auto px-4 py-6">
            <RouterView />
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter, RouterView, RouterLink } from 'vue-router';
import { Menu, X } from '@lucide/vue';
import { useAuth } from '@/composables/useAuth';
import api from '@/api/axios';

const { t } = useI18n();
const router = useRouter();
const { isAdmin, logout } = useAuth();

const menuOpen = ref(false);

async function doLogout() {
    menuOpen.value = false;
    await logout();
    await router.push({ name: 'login' });
}

async function openAnalytics() {
    // The dashboard is a plain server-rendered page authenticated via a web
    // session, not the SPA's bearer token — bridge the two before navigating.
    await api.get('/analytics-login');
    window.location.href = '/analytics';
}
</script>

<style scoped>
@reference "tailwindcss";

.nav-link {
    @apply px-3 py-1.5 rounded text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors;
}
.nav-link.active {
    @apply bg-gray-100 text-[var(--color-primary)];
}
</style>
