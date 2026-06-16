import { createRouter, createWebHistory } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const routes = [
    // Public event pages
    {
        path: '/e/:slug',
        name: 'event.show',
        component: () => import('@/views/public/EventPage.vue'),
    },
    {
        path: '/e/:slug/ride/:id/edit',
        name: 'ride.edit',
        component: () => import('@/views/public/RideEdit.vue'),
    },
    {
        path: '/e/:slug/ride/:id/delete',
        name: 'ride.delete',
        component: () => import('@/views/public/RideDelete.vue'),
    },

    // Auth
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/admin/Login.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('@/views/admin/Register.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/forgot-password',
        name: 'password.request',
        component: () => import('@/views/admin/ForgotPassword.vue'),
        meta: { guestOnly: true },
    },

    // Admin area
    {
        path: '/admin',
        component: () => import('@/views/admin/AdminLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'admin.home',
                redirect: () => ({ name: 'admin.events' }),
            },
            {
                path: 'events',
                name: 'admin.events',
                component: () => import('@/views/admin/Events.vue'),
            },
            {
                path: 'events/create',
                name: 'admin.events.create',
                component: () => import('@/views/admin/EventForm.vue'),
            },
            {
                path: 'events/:id/edit',
                name: 'admin.events.edit',
                component: () => import('@/views/admin/EventForm.vue'),
            },
            {
                path: 'users',
                name: 'admin.users',
                component: () => import('@/views/admin/Users.vue'),
                meta: { requiresAdmin: true },
            },
            {
                path: 'settings',
                name: 'admin.settings',
                component: () => import('@/views/admin/Settings.vue'),
                meta: { requiresAdmin: true },
            },
            {
                path: 'profile',
                name: 'admin.profile',
                component: () => import('@/views/admin/Profile.vue'),
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
        return { name: 'admin.events' };
    }
    if (to.meta.guestOnly && isAuthenticated.value) {
        return { name: 'admin.events' };
    }
});

export default router;
