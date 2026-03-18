import { createRouter, createWebHistory } from 'vue-router';
import QueueView from '../views/QueueView.vue';
import SubmitView from '../views/SubmitView.vue';
import LoginView from '../views/LoginView.vue';
import { useAuthStore } from '@/stores/auth';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: LoginView,
        meta: { public: true },
    },
    {
        path: '/',
        name: 'Queue',
        component: QueueView,
    },
    {
        path: '/submit',
        name: 'Submit',
        component: SubmitView,
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    // Check session once on first navigation
    if (!auth.checked) {
        await auth.check();
    }

    if (!to.meta.public && !auth.user) {
        return { name: 'Login' };
    }

    if (to.name === 'Login' && auth.user) {
        return { name: 'Queue' };
    }
});

export default router;
