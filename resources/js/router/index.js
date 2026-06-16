import { createRouter, createWebHistory } from 'vue-router';

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
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('@/views/admin/Register.vue'),
    },
    {
        path: '/forgot-password',
        name: 'password.request',
        component: () => import('@/views/admin/ForgotPassword.vue'),
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
                redirect: (to) => ({ name: 'admin.events' }),
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

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('auth_token');
    if (to.meta.requiresAuth && !token) {
        return next({ name: 'login' });
    }
    next();
});

export default router;
