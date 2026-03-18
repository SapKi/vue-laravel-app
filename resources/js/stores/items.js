import { defineStore } from 'pinia';
import { ref } from 'vue';
import { fetchItems, deleteItem } from '@/api/items';

export const useItemsStore = defineStore('items', () => {
    const items = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const page = ref(1);
    const meta = ref({ total: 0, last_page: 1, per_page: 10 });

    const filters = ref({
        status: '',
        search: '',
        sort: 'created_at',
        order: 'desc',
    });

    async function loadItems(p = page.value) {
        loading.value = true;
        error.value = null;
        page.value = p;
        try {
            const res = await fetchItems({ ...filters.value, page: p });
            items.value = res.data;
            meta.value  = { total: res.total, last_page: res.last_page, per_page: res.per_page };
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load items.';
        } finally {
            loading.value = false;
        }
    }

    function goToPage(p) {
        if (p < 1 || p > meta.value.last_page) return;
        loadItems(p);
    }

    function resetPage() {
        page.value = 1;
    }

    function patchItem(updated) {
        const idx = items.value.findIndex((i) => i.id === updated.id);
        if (idx !== -1) items.value[idx] = updated;
    }

    async function removeItem(id) {
        await deleteItem(id);
        // if last item on page > 1, go back one page
        if (items.value.length === 1 && page.value > 1) {
            loadItems(page.value - 1);
        } else {
            items.value = items.value.filter((i) => i.id !== id);
            meta.value.total = Math.max(0, meta.value.total - 1);
        }
    }

    return { items, loading, error, filters, page, meta, loadItems, goToPage, resetPage, patchItem, removeItem };
});
