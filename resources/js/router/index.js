import { createRouter, createWebHistory } from 'vue-router';
import QueueView from '../views/QueueView.vue';
import SubmitView from '../views/SubmitView.vue';

const routes = [
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

export default router;
