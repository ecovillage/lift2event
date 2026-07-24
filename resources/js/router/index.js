import { createRouter, createWebHistory } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const routes = [
    // Public event pages. The /e/... paths are aliases kept so previously
    // shared/emailed links (old adjective-noun slugs) stay reachable.
    {
        path: '/events/:slug',
        alias: '/e/:slug',
        name: 'event.show',
        component: () => import('@/views/public/EventPage.vue'),
    },
    {
        path: '/events/:slug/ride/:id/edit',
        alias: '/e/:slug/ride/:id/edit',
        name: 'ride.edit',
        component: () => import('@/views/public/RideEdit.vue'),
    },
    {
        path: '/events/:slug/ride/:id/delete',
        alias: '/e/:slug/ride/:id/delete',
        name: 'ride.delete',
        component: () => import('@/views/public/RideDelete.vue'),
    },
    {
        path: '/events/:slug/ride/:id/confirm',
        alias: '/e/:slug/ride/:id/confirm',
        name: 'ride.confirm',
        component: () => import('@/views/public/RideConfirm.vue'),
    },
    {
        path: '/events/:slug/ride/:id/book',
        alias: '/e/:slug/ride/:id/book',
        name: 'ride.book',
        component: () => import('@/views/public/RideBook.vue'),
    },
    // Auth
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/backend/Login.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('@/views/backend/Register.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/forgot-password',
        name: 'password.request',
        component: () => import('@/views/backend/ForgotPassword.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/reset-password',
        name: 'password.reset',
        component: () => import('@/views/backend/ResetPassword.vue'),
        meta: { guestOnly: true },
    },

    // Backend area
    {
        path: '/backend',
        component: () => import('@/views/backend/BackendLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'backend.home',
                component: { render: () => null },
            },
            {
                path: 'events',
                name: 'backend.events',
                component: () => import('@/views/backend/Events.vue'),
            },
            {
                path: 'events/create',
                name: 'backend.events.create',
                component: () => import('@/views/backend/EventForm.vue'),
            },
            {
                path: 'events/:id/edit',
                name: 'backend.events.edit',
                component: () => import('@/views/backend/EventForm.vue'),
            },
            {
                path: 'users',
                name: 'backend.users',
                component: () => import('@/views/backend/Users.vue'),
                meta: { requiresAdmin: true },
            },
            {
                path: 'settings',
                name: 'backend.settings',
                component: () => import('@/views/backend/Settings.vue'),
                meta: { requiresAdmin: true },
            },
            {
                path: 'profile',
                name: 'backend.profile',
                component: () => import('@/views/backend/Profile.vue'),
            },
        ],
    },

    { path: '/:pathMatch(.*)*', redirect: '/login' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const { state, isAuthenticated, isAdmin, fetchUser } = useAuth();

    if (!state.ready) {
        await fetchUser();
    }

    if (to.meta.requiresAuth && !isAuthenticated.value) {
        return { name: 'login' };
    }
    if (to.meta.requiresAdmin && !isAdmin.value) {
        return { name: 'backend.events' };
    }
    if (to.meta.guestOnly && isAuthenticated.value) {
        return { name: 'backend.events' };
    }
    if (to.name === 'backend.home') {
        return { name: isAdmin.value ? 'backend.users' : 'backend.events' };
    }
});

export default router;
