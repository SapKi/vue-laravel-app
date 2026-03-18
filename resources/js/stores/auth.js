import { defineStore } from 'pinia';
import { ref } from 'vue';
import { login as apiLogin, logout as apiLogout, fetchMe } from '@/api/auth';

export const useAuthStore = defineStore('auth', () => {
    const user    = ref(null);
    const checked = ref(false);

    async function check() {
        try {
            user.value = await fetchMe();
        } catch {
            user.value = null;
        } finally {
            checked.value = true;
        }
    }

    async function login(email, password) {
        user.value = await apiLogin(email, password);
    }

    async function logout() {
        try {
            await apiLogout();
        } finally {
            user.value    = null;
            checked.value = true;
        }
    }

    return { user, checked, check, login, logout };
});
