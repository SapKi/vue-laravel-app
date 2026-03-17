import { defineStore } from 'pinia';
import { ref } from 'vue';
import { fetchItems, reviewItem } from '@/api/items';

export const useItemsStore = defineStore('items', () => {
    const items = ref([]);
    const loading = ref(false);
    const error = ref(null);

    const filters = ref({
        status: '',
        search: '',
        sort: 'created_at',
        order: 'desc',
    });

    async function loadItems() {
        loading.value = true;
        error.value = null;
        try {
            items.value = await fetchItems(filters.value);
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load items.';
        } finally {
            loading.value = false;
        }
    }

    async function submitReview(id, status, note) {
        const updated = await reviewItem(id, { status, note });
        const idx = items.value.findIndex((i) => i.id === id);
        if (idx !== -1) items.value[idx] = updated;
        return updated;
    }

    return { items, loading, error, filters, loadItems, submitReview };
});
